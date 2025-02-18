<?php
declare(strict_types=1);

namespace Bold\CheckoutPaymentBooster\Block\Hyva\Checkout\Payment\Methods;

use Bold\CheckoutPaymentBooster\Model\CheckoutData;
use Bold\CheckoutPaymentBooster\Model\Config;
use Magento\Config\Model\Config\Source\Nooptreq as NooptreqSource;
use Magento\Directory\Model\AllowedCountries;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Spi extends Template
{
    /**
     * @var CheckoutData
     */
    private $checkoutData;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AllowedCountries
     */
    private $allowedCountries;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $countries = [];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @param Context $context
     * @param CheckoutData $checkoutData
     * @param Config $config
     * @param AllowedCountries $allowedCountries
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param Escaper $escaper
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Context $context,
        CheckoutData $checkoutData,
        Config $config,
        AllowedCountries $allowedCountries,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        Escaper $escaper,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutData = $checkoutData;
        $this->config = $config;
        $this->allowedCountries = $allowedCountries;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->escaper = $escaper;
        if (!$this->checkoutData->getPublicOrderId()) {
            $this->setTemplate('');
        }
        $this->json = $json;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    public function getBoldConfigJson(): string
    {
        if (!$this->checkoutData->getPublicOrderId()) {
            $errorMsg = 'No public order ID.';
            $this->logger->critical('Error in PaymentBoosterConfigProvider->getConfig(): ' . $errorMsg);
            return $this->json->serialize([]);
        }

        $quote = $this->checkoutData->getQuote();
        $websiteId = (int)$quote->getStore()->getWebsiteId();
        $shopId = $this->config->getShopId($websiteId);
        $publicOrderId = $this->checkoutData->getPublicOrderId();
        $jwtToken = $this->checkoutData->getJwtToken();
        $epsAuthToken = $this->checkoutData->getEpsAuthToken();
        $epsGatewayId = $this->checkoutData->getEpsGatewayId();
        $currency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        if ($jwtToken === null || $epsAuthToken === null || $epsGatewayId === null) {
            $errorMsgs = [];
            if ($jwtToken === null) {
                $errorMsgs[] = '$jwtToken is null.';
            }

            if ($epsAuthToken === null) {
                $errorMsgs[] = '$epsAuthToken is null.';
            }

            if ($epsGatewayId === null) {
                $errorMsgs[] = '$epsGatewayId is null.';
            }

            $this->logger->critical('Error in PaymentBoosterConfigProvider->getConfig(): ' . implode(', ', $errorMsgs));
            return $this->json->serialize([]);
        }

        $configurationGroupLabel = $this->config->getConfigurationGroupLabel($websiteId);
        if (empty($configurationGroupLabel)) {
            $configurationGroupLabel = parse_url($quote->getStore()->getBaseUrl())['host'] ?? '';
        }
        $showTelephone = $quote->getStore()->getConfig('customer/address/telephone_show');
        return $this->json->serialize(
            [
                'epsAuthToken' => $epsAuthToken,
                'isFastlaneAvailable' => false, //todo: implement fastlane
                'configurationGroupLabel' => $configurationGroupLabel,
                'epsStaticUrl' => $this->config->getStaticEpsUrl($websiteId),
                'gatewayId' => $epsGatewayId,
                'jwtToken' => $jwtToken,
                'url' => $this->getBoldStorefrontUrl($websiteId, $publicOrderId),
                'shopId' => $shopId,
                'publicOrderId' => $publicOrderId,
                'countries' => $this->getAllowedCountries(),
                'origin' => rtrim($this->config->getApiUrl($websiteId), '/'),
                'epsUrl' => rtrim($this->config->getEpsUrl($websiteId), '/'),
                'shopUrl' => $quote->getStore()->getBaseUrl(),
                'shopName' => $quote->getStore()->getFrontendName(),
                'isPhoneRequired' => $showTelephone === NooptreqSource::VALUE_REQUIRED,
                'isExpressPayEnabled' => $this->config->isExpressPayEnabled($websiteId),
                'isCartWalletPayEnabled' => $this->config->isCartWalletPayEnabled($websiteId),
                'isTaxIncludedInPrices' => $this->config->isTaxIncludedInPrices($websiteId),
                'isTaxIncludedInShipping' => $this->config->isTaxIncludedInShipping($websiteId),
                'currency' => $currency,
                'cart' => $this->getCartData($quote),
            ]
        );
    }

    /**
     * Get Bold Storefront URL.
     *
     * @param int $websiteId
     * @param string $publicOrderId
     * @return string
     */
    private function getBoldStorefrontUrl(int $websiteId, string $publicOrderId): string
    {
        $apiUrl = $this->config->getApiUrl($websiteId) . 'checkout/storefront/';
        return $apiUrl . $this->config->getShopId($websiteId) . '/' . $publicOrderId . '/';
    }

    /**
     * Get allowed countries for Billing address mapping.
     *
     * @return Country[]
     */
    private function getAllowedCountries(): array
    {
        if ($this->countries) {
            return $this->countries;
        }
        $allowedCountries = $this->allowedCountries->getAllowedCountries();
        $countriesCollection = $this->collectionFactory->create()->addFieldToFilter(
            'country_id',
            ['in' => $allowedCountries]
        );
        $this->countries = $countriesCollection->toOptionArray(false);

        return $this->countries;
    }

    private function getDefaultSuccessPageUrl(): string
    {
        return $this->urlBuilder->getUrl('checkout/onepage/success/');
    }

    private function getShippingPolicy(): array
    {
        $policyContent = $this->scopeConfig->getValue(
            'shipping/shipping_policy/shipping_policy_content',
            ScopeInterface::SCOPE_STORE
        );
        $policyContent = $this->escaper->escapeHtml($policyContent);
        $result = [
            'isEnabled' => $this->scopeConfig->isSetFlag(
                'shipping/shipping_policy/enable_shipping_policy',
                ScopeInterface::SCOPE_STORE
            ),
            'shippingPolicyContent' => $policyContent ? nl2br($policyContent) : '',
        ];

        return $result;
    }

    private function getCartData(CartInterface $quote): array
    {
        $quoteId = $quote->getId();
        if (!$quote->getCustomer()->getId()) {
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            $quoteId = $quoteIdMask->load($quoteId, 'quote_id')->getMaskedId();
        }
        return [
            'id' => $quoteId,
            'customer' => $this->getCustomerData($quote),
            'billing_address' => $this->getAddressData($quote, 'billing'),
            'shipping_address' => $this->getAddressData($quote, 'shipping'),
            'items' => $this->getItemsData($quote),
            'shipping_options' => $this->getShippingOptionsData($quote),
            'totals' => $this->getTotalsData($quote),
        ];
    }

    private function getTotalsData(CartInterface $quote): array
    {
        $shippingTotals = $quote->getIsVirtual() ? 0 : $quote->getShippingAddress()->getShippingAmount();
        $discountsTotal = $quote->getIsVirtual()
            ? $quote->getBillingAddress()->getDiscountAmount()
            : $quote->getShippingAddress()->getDiscountAmount();
        $feesTotal = $quote->getIsVirtual()
            ? $quote->getBillingAddress()->getTotals()['fee']['value'] ?? 0
            : $quote->getShippingAddress()->getTotals()['fee']['value'] ?? 0;
        $taxesTotal = $quote->getIsVirtual()
            ? $quote->getBillingAddress()->getTaxAmount()
            : $quote->getShippingAddress()->getTaxAmount();
        return [
            'order_total' => $this->convertToCents($quote->getGrandTotal()),
            'shipping_total' => $this->convertToCents($shippingTotals),
            'discounts_total' => $this->convertToCents($discountsTotal),
            'fees_total' => $this->convertToCents($feesTotal),
            'taxes_total' => $this->convertToCents($taxesTotal),
        ];
    }

    private function getCustomerData(CartInterface $quote): array
    {
        $address = $quote->getIsVirtual()
            ? $quote->getBillingAddress()
            : $quote->getShippingAddress();
        $firstName = $quote->getCustomerFirstname() ?? (string)$address->getFirstname();
        $lastName = $quote->getCustomerLastname() ?? (string)$address->getLastname();
        $email = $quote->getCustomerEmail() ?? (string)$address->getEmail();
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email_address' => $email,
        ];
    }

    private function getItemsData(CartInterface $quote): array
    {
        $items = [];
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $items[] = [
                'label' => $quoteItem->getName(),
                'amount' => $this->convertToCents($quoteItem->getBaseCalculationPrice()),
            ];
        }
        return $items;
    }

    private function getShippingOptionsData(CartInterface $quote): array
    {
        if ($quote->getIsVirtual()) {
            return [];
        }
        $shippingOptions = [];
        /** @var Rate $rate */
        foreach ($quote->getShippingAddress()->getShippingRatesCollection() as $rate) {
            $shippingOptions[] = [
                'id' => $rate->getCarrier() . '_' . $rate->getMethod(),
                'label' => $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle(),
                'amount' => $this->convertToCents($rate->getPrice()),
                'is_selected' => $rate->getCode() === $quote->getShippingAddress()->getShippingMethod(),
            ];
        }
        return $shippingOptions;
    }

    private function getAddressData(CartInterface $quote, string $type): array
    {
        $address = $type === 'billing' ? $quote->getBillingAddress() : $quote->getShippingAddress();
        return [
            'email' => (string)$address->getEmail(),
            'country_code' => (string)$address->getCountryId(),
            'city' => (string)$address->getCity(),
            'first_name' => (string)$address->getFirstname(),
            'last_name' => (string)$address->getLastname(),
            'phone_number' => (string)$address->getTelephone(),
            'postal_code' => (string)$address->getPostcode(),
            'province' => (string)$address->getRegion(),
            'province_code' => (string)$address->getRegionCode(),
            'address_line_1' => (string)$address->getStreetLine(1),
            'address_line_2' => (string)$address->getStreetLine(2),
        ];
    }

    /**
     * Converts a dollar amount to cents
     *
     * @param float|string $dollars
     * @return integer
     */
    private function convertToCents($dollars): int
    {
        return (int)round(floatval($dollars) * 100);
    }
}
