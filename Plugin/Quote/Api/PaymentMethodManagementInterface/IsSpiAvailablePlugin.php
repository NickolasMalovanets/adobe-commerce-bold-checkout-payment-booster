<?php
declare(strict_types=1);

namespace Bold\CheckoutPaymentBooster\Plugin\Quote\Api\PaymentMethodManagementInterface;

use Bold\CheckoutPaymentBooster\Model\CheckoutData;
use Magento\Quote\Api\PaymentMethodManagementInterface;

class IsSpiAvailablePlugin
{
    /**
     * @var CheckoutData
     */
    private $checkoutData;

    /**
     * @param CheckoutData $checkoutData
     */
    public function __construct(
        CheckoutData $checkoutData
    ) {
        $this->checkoutData = $checkoutData;
    }

    public function afterGetList(PaymentMethodManagementInterface $subject, array $paymentMethods): array
    {
        if ($this->isSpiAvailable()) {
            return $paymentMethods;
        }
        foreach ($paymentMethods as $key => $paymentMethod) {
            if ($paymentMethod->getCode() === 'bold') {
                unset($paymentMethods[$key]);
            }
        }
        return $paymentMethods;
    }

    private function isSpiAvailable(): bool
    {
        return $this->checkoutData->getPublicOrderId() !== null;
    }
}
