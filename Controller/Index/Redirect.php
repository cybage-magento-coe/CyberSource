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

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
class Redirect extends \Magento\Framework\App\Action\Action {

    /**
     *
     * @var Mage_Sales_Model_Order 
     */
    protected $_order;

    /**
     * Magento\Checkout\Model\Session.
     */
    protected $checkoutsession;

    /**
     * Magento\Sales\Model\OrderFactory.
     */
    protected $_orderFactory;

    /**
     * @var Cybage\Cybersource\Helper\Data
     */
    protected $_cybsaHelper;

    /**
     * @var Magento\Framework\Data\FormFactory
     */
    protected $formFactory;
    /**
     * Constructor
     * 
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context, 
        \Magento\Checkout\Model\Session $checkoutsession, 
        \Magento\Sales\Model\OrderFactory $orderFactory, 
        \Cybage\Cybersource\Helper\Data $cybsaHelper,
        \Magento\Framework\Data\FormFactory $formFactory,
        RequestInterface $request,
        Repository $repository
    ) {
        $this->checkoutsession = $checkoutsession;
        $this->_orderFactory = $orderFactory;
        $this->_cybsaHelper = $cybsaHelper;
        $this->_isScopePrivate = true;
        $this->formFactory = $formFactory;
        $this->request = $request;
        $this->assetRepo = $repository;
        parent::__construct($context);
    }

    /**
     * 
     * @return type
     */
    public function execute() {
        $order = $this->getOrder();

        if (!$order->getId()) {
            $this->_redirect($this->_url->getUrl('cms/noroute/index'));
            return;
        }
        $order->addStatusToHistory(
                $order->getStatus(), __('Customer was redirected to Cybersource.')
        );
        $order->save();
        echo $this->_toHtml($order);
        exit(0);
    }

    /**
     * Prepared HTML for Redirect
     *
     * @return string
     */
    protected function _toHtml($order) {
        $standard = $order->getPayment()->getMethodInstance();
        $form = $this->formFactory->create();
        $redirectLabel = $this->_cybsaHelper->getRedirectLabel(\Cybage\Cybersource\Model\Cybersourcesa::CODE);
        $params = array('_secure' => $this->request->isSecure());
        $this->assetRepo->getUrlWithParams('Nitesh_Module::images/image.png', $params);
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
        $html .= '<div style="text-align: center; font-size: 20px;">';
        $html .= '<div class="loading-mask" style="bottom: 68%; left: 0; margin: auto; position: fixed; right: 0; top: 0; z-index: 100; background: rgba(255,255,255,0.5);">';
        $html .= '<div class="loader">';
        $html .= '<img src="http://anilka.hppub.com/pub/static/version1522315207/frontend/Magento/luma/en_US/images/loader-1.gif" alt="Loading..." style="position: absolute; bottom: 0; left: 0; margin: auto; right: 0; top: 0; z-index: 100;">';
        $html .= '</div></div>';
        $html .= $redirectLabel;
        $html .= '</div>';
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("cybersourcesa_payment_checkout").submit();</script>';
        $html .= '</body></html>';
        return $html;
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
