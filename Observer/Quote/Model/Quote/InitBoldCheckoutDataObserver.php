<?php

declare(strict_types=1);

namespace Bold\CheckoutPaymentBooster\Observer\Quote\Model\Quote;

use Bold\CheckoutPaymentBooster\Model\CheckoutData;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Initialize Bold Checkout Data Observer.
 */
class InitBoldCheckoutDataObserver implements ObserverInterface
{
    /**
     * @var CheckoutData
     */
    private $boldCheckoutData;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CheckoutData $boldCheckoutData
     * @param LoggerInterface $logger
     */
    public function __construct(CheckoutData $boldCheckoutData, LoggerInterface $logger)
    {
        $this->boldCheckoutData = $boldCheckoutData;
        $this->logger = $logger;
    }

    /**
     * Initialize/Refresh Bold Order after product has been added to cart.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        try {
            $this->boldCheckoutData->initCheckoutData();
        } catch (LocalizedException $e) {
            $this->logger->error('Cannot initialize Bold Order: ' . $e->getMessage());
        }
    }
}
