<?php
declare(strict_types=1);
namespace Aeromalls\Storefront\Controller\Order;

use Aeromalls\Storefront\ViewModel\OrderCancellation;
use Magento\Customer\Controller\AccountInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use StripeIntegration\Payments\Helper\Payment;
use StripeIntegration\Payments\Model\Config;

class Cancel implements AccountInterface, HttpPostActionInterface
{
    public function __construct(
        private Session $session,
        private RequestInterface $request,
        private RedirectFactory $redirectFactory,
        private Validator $formKeyValidator,
        private ManagerInterface $messages,
        private OrderRepositoryInterface $orders,
        private OrderManagementInterface $orderManagement,
        private OrderCancellation $eligibility,
        private Payment $stripePayment,
        private Config $stripeConfig,
        private LoggerInterface $logger
    ) {}

    public function execute()
    {
        $redirect = $this->redirectFactory->create()->setPath('sales/order/history');
        if (!$this->session->isLoggedIn() || !$this->formKeyValidator->validate($this->request)) {
            $this->messages->addErrorMessage(__('Please sign in and refresh the page before trying again.'));
            return $redirect;
        }
        try {
            $order = $this->orders->get((int)$this->request->getParam('order_id'));
            if ((int)$order->getCustomerId() !== (int)$this->session->getCustomerId()) {
                throw new LocalizedException(__('This order is not available.'));
            }
            if (!$this->eligibility->isEligible($order)) {
                throw new LocalizedException(__('This order can no longer be canceled online. Please contact us.'));
            }

            // Cancel the unpaid Stripe intent first, preventing a pending redirect from charging later.
            // A remote failure blocks cancellation; paid/processing intents must never be refunded here.
            if ($order->getPayment()->getMethod() === 'stripe_payments') {
                $intent = $this->stripePayment->getStripePaymentIntentModel($order)->getStripeObject();
                if (!$intent || !in_array($intent->status, [
                    'requires_payment_method', 'requires_confirmation', 'requires_action', 'canceled',
                ], true)) {
                    throw new LocalizedException(__('Payment may already be processing. Please contact us to cancel this order.'));
                }
                if (!empty($intent->metadata->{'Order #'})
                    && $intent->metadata->{'Order #'} !== $order->getIncrementId()) {
                    throw new LocalizedException(__('The payment is linked to a different order. Please contact us.'));
                }
                if ($intent->status !== 'canceled') {
                    $this->stripeConfig->getStripeClient()->paymentIntents->cancel($intent->id, [
                        'cancellation_reason' => 'requested_by_customer',
                    ]);
                }
            } elseif (str_starts_with((string)$order->getPayment()->getMethod(), 'stripe_')) {
                throw new LocalizedException(__('Please contact us to cancel this payment method.'));
            }

            if (!$this->orderManagement->cancel((int)$order->getId())) {
                throw new LocalizedException(__('This order can no longer be canceled online. Please contact us.'));
            }
            $this->messages->addSuccessMessage(__('Order %1 has been canceled.', $order->getIncrementId()));
        } catch (LocalizedException $e) {
            $this->messages->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Customer order cancellation failed.', ['exception' => $e]);
            $this->messages->addErrorMessage(__('We could not cancel the order. Please try again or contact us.'));
        }
        return $redirect;
    }
}
