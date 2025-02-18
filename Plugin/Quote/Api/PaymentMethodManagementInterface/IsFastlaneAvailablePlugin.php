<?php
declare(strict_types=1);

namespace Bold\CheckoutPaymentBooster\Plugin\Quote\Api\PaymentMethodManagementInterface;

use Bold\CheckoutPaymentBooster\Model\CheckoutData;
use Bold\CheckoutPaymentBooster\Model\Config;
use Magento\Quote\Api\PaymentMethodManagementInterface;

class IsFastlaneAvailablePlugin
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
     * @param CheckoutData $checkoutData
     * @param Config $config
     */
    public function __construct(
        CheckoutData $checkoutData,
        Config $config
    ) {
        $this->checkoutData = $checkoutData;
        $this->config = $config;
    }

    public function afterGetList(PaymentMethodManagementInterface $subject, array $paymentMethods): array
    {
        if ($this->isFastlaneAvailable()) {
            return $paymentMethods;
        }
        foreach ($paymentMethods as $key => $paymentMethod) {
            if ($paymentMethod->getCode() === 'bold_fastlane') {
                unset($paymentMethods[$key]);
            }
        }
        return $paymentMethods;
    }

    private function isFastlaneAvailable(): bool
    {
        $websiteId = (int)$this->checkoutData->getQuote()->getStore()->getWebsiteId();
        $isGuestCart = $this->checkoutData->getQuote()->getCustomerIsGuest();
        return $this->checkoutData->getPublicOrderId() !== null
            && $this->config->isFastlaneEnabled($websiteId)
            && $isGuestCart;
    }
}
