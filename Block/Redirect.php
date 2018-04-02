<?php
/**
 * Cybage CyberSource
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is available on the World Wide Web at:
 * http://opensource.org/licenses/osl-3.0.php
 * If you are unable to access it on the World Wide Web, please send an email
 * To: Support_ecom@cybage.com.  We will send you a copy of the source file.
 *
 * @category  CyberSource_Payment_Method
 * @package   Cybage_CyberSource
 * @author    Cybage Software Pvt. Ltd. <Support_ecom@cybage.com>
 * @copyright 1995-2017 Cybage Software Pvt. Ltd., India
 *            http://www.cybage.com/pages/centers-of-excellence/ecommerce/ecommerce.aspx
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Cybage\CyberSource\Block;

class Redirect extends \Magento\Framework\View\Element\Template {

    const cybersource_sa = "cybersourcesa";

    /**
     * @var Cybage\CyberSource\Data\FormFactory
     */
    protected $cybhelper;

    /**
     * @var Magento\Framework\Data\FormFactory
     */
    protected $formFactory;

    /**
     * Magento\Sales\Model\OrderFactory.
     */
    protected $_orderFactory;

    /**
     * Magento\Checkout\Model\Session\Proxy
     */
    protected $checkoutsession;
    protected $_order;
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Cybage\CyberSource\Helper\Data $cybhelper, 
        \Magento\Framework\Data\FormFactory $formFactory, 
        \Magento\Sales\Model\OrderFactory $orderFactory, 
        \Magento\Checkout\Model\Session $checkoutsession, 
        \Magento\Framework\Registry $coreRegistry, 
        array $data = []
    ) {

        $this->cybhelper = $cybhelper;
        $this->formFactory = $formFactory;
        $this->_orderFactory = $orderFactory;
        $this->checkoutsession = $checkoutsession;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * Prepared HTML for Redirect
     *
     * @return string
     */
    protected function _toHtml() {
        $order = $this->_coreRegistry->registry('last_order');
        $standard = $order->getPayment()->getMethodInstance();

        $form = $this->formFactory->create();
        $redirectLabel = $this->cybhelper->getRedirectLabel(self::cybersource_sa);

        $form->setAction($standard->getCybersourcesaUrl())
                ->setId('cybersourcesa_payment_checkout')
                ->setName('cybersourcesa_payment_checkout')
                ->setMethod('POST')
                ->setEnctype('application/json')
                ->setUseContainer(true);

        foreach ($standard->getFormFields($order) as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }
        $html = '<html><body>';
        $html .= $redirectLabel;
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("cybersourcesa_payment_checkout").submit();</script>';
        $html .= '</body></html>';

        return $html;
    }

    public function getCacheLifetime() {
        return null;
    }

}
