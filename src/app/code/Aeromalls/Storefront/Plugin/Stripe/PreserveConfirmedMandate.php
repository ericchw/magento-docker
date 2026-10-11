<?php
declare(strict_types=1);

namespace Aeromalls\Storefront\Plugin\Stripe;

use StripeIntegration\Payments\Helper\PaymentIntent;

/** Stripe forbids resending ACSS mandate options after the first confirmation. */
class PreserveConfirmedMandate
{
    public function afterGetConfirmParams(
        PaymentIntent $subject,
        array $result,
        $order,
        $paymentIntent
    ): array {
        if (empty($paymentIntent) || !str_starts_with((string)$paymentIntent->id, 'pi_')) {
            return $result;
        }

        $previouslyConfirmed = in_array($paymentIntent->status, [
            'requires_action', 'processing', 'requires_capture', 'succeeded',
        ], true) || !empty($paymentIntent->last_payment_error) || !empty($paymentIntent->latest_charge);

        if ($previouslyConfirmed) {
            unset($result['payment_method_options']['acss_debit']['mandate_options']);
            if (isset($result['payment_method_options']['acss_debit'])
                && $result['payment_method_options']['acss_debit'] === []) {
                unset($result['payment_method_options']['acss_debit']);
            }
            if (isset($result['payment_method_options']) && $result['payment_method_options'] === []) {
                unset($result['payment_method_options']);
            }
        }

        return $result;
    }
}
