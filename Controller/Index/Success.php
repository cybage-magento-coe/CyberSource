<?php
/**
 * Cybage Cybersource
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is available on the World Wide Web at:
 * http://opensource.org/licenses/osl-3.0.php
 * If you are unable to access it on the World Wide Web, please send an email
 * To: Support_ecom@cybage.com.  We will send you a copy of the source file.
 *
 * @category  Cybersource_Payment_Method
 * @package   Cybage_Cybersource
 * @author    Cybage Software Pvt. Ltd. <Support_ecom@cybage.com>
 * @copyright 1995-2017 Cybage Software Pvt. Ltd., India
 *            http://www.cybage.com/pages/centers-of-excellence/ecommerce/ecommerce.aspx
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Cybage\Cybersource\Controller\Index;

class Success extends \Cybage\Cybersource\Controller\Index\Cybersourcesa {
    
    const PAYMENT_CYBERSOURCE_ORDER_STATUS = 'payment/cybersourcesa/order_status';

    public function execute() {
        $order = $this->getOrder();
        if (!$order->getId()) {
            $this->_redirect('checkout/cart');
            return false;
        }
        $successUrl = $this->_urlBuilder->getUrl('checkout/onepage/success');
        $failUrl = $this->_urlBuilder->getUrl('checkout/cart');
        $responseParams = $this->getRequest()->getParams();
        $review = $this->checkoutsession->getCybreview();
        $this->checkoutsession->unsCybreview();
        $this->checkoutsession->setCybreview(false);
        $validateResponse = true;
        if ($validateResponse || $review) {
            if ($review) {
                $order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                $order->addStatusToHistory(
                        $order->getStatus(), __('Order is currently under review.')
                );
            } else {
                $order->setStatus($this->scopeConfig->getValue(self::PAYMENT_CYBERSOURCE_ORDER_STATUS));
                $order->addStatusToHistory(
                        $order->getStatus(), __('Customer successfully returned from Cybersource.')
                );
            }
            $order->save();
            $this->_redirect($successUrl);
            return;
        } else {
            $comment = '';
            if (isset($responseParams['message'])) {
                $comment .= '<br />Error: ';
                $comment .= "'" . $responseParams['message'] . "'";
            }
            $order->cancel();
            $order->addStatusToHistory(
                    $order->getStatus(), __('Customer successfully returned from Cybersource but the payment is DECLINED.') . $comment
            );
            $order->save();
            $this->messageManager->addError(__('There is an error processing your payment.' . $comment));
            $this->_redirect($failUrl);
            return;
        }
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder() {
        if ($this->_order == null) {
            $this->_order = $this->_orderFactory->create();
            $this->_order->loadByIncrementId($this->checkoutsession->getLastRealOrderId());
        }
        return $this->_order;
    }

}
