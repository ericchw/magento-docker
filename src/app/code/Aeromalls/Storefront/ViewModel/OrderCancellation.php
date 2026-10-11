<?php
declare(strict_types=1);
namespace Aeromalls\Storefront\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order;

class OrderCancellation implements ArgumentInterface
{
    public function __construct(private \StripeIntegration\Payments\Helper\Payment $stripePayment) {}

    public function isEligible(Order $order): bool
    {
        $eligible = in_array($order->getState(), [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT], true)
            && in_array($order->getPayment()->getMethod(), ['stripe_payments', 'checkmo', 'banktransfer', 'cashondelivery', 'free'], true)
            && (float)$order->getTotalPaid() === 0.0
            && !$order->hasInvoices()
            && !$order->hasShipments()
            && $order->canCancel();
        if (!$eligible || $order->getPayment()->getMethod() !== 'stripe_payments') {
            return $eligible;
        }
        try {
            $intent = $this->stripePayment->getStripePaymentIntentModel($order)->getStripeObject();
            return $intent && in_array($intent->status, [
                'requires_payment_method', 'requires_confirmation', 'requires_action', 'canceled',
            ], true) && (empty($intent->metadata->{'Order #'})
                || $intent->metadata->{'Order #'} === $order->getIncrementId());
        } catch (\Throwable $e) {
            // Hide the action when the remote payment cannot be verified.
            return false;
        }
    }
}
