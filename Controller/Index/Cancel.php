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

class Cancel extends \Cybage\Cybersource\Controller\Index\Cybersourcesa {

    public function execute() {
        $order = $this->getOrder();
        if (!$order->getId()) {
            $this->_redirect('checkout/cart');
            return false;
        }
        $order->cancel();
        $order->addStatusToHistory(
                $order->getStatus(), __('Payment was canceled.')
        );
        $order->save();
        $this->messageManager->addError(__('Payment was canceled.'));
        $this->_redirect('checkout/cart');
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
