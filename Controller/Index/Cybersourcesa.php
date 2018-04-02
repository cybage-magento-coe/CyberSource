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

abstract class Cybersourcesa extends \Magento\Framework\App\Action\Action {

    const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const REQUEST_TYPE_AUTH_ONLY = 'AUTH_ONLY';
    const REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURE_ONLY';
    const REQUEST_TYPE_CREDIT = 'CREDIT';
    const REQUEST_TYPE_VOID = 'VOID';
    const REQUEST_TYPE_PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';
    const STATUS_UNKNOWN = 'UNKNOWN';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_ERROR = 'ERROR';
    const STATUS_DECLINED = 'DECLINED';
    const STATUS_VOID = 'VOID';
    const STATUS_SUCCESS = 'SUCCESS';
    protected $_order;
    protected $_isTransactionFraud = 'is_transaction_fraud';
    protected $_realTransactionIdKey = 'real_transaction_id';
    protected $_isGatewayActionsLockedKey = 'is_gateway_actions_locked';

    protected $_cardCode = array(
        'VI' => '001',
        'MC' => '002',
        'AE' => '003',
        'DI' => '004',
        'DC' => '005',
        'JCB' => '007',
        'MAESTRO' => '042',
        'SWITCH' => '024',
    );

    /**
     * Magento\Checkout\Model\Session.
     */
    protected $checkoutsession;

    /**
     * Magento\Sales\Model\OrderFactory.
     */
    protected $_orderFactory;

    /**
     * @var Cybage\Cybersourcesa\Helper\Data
     */
    protected $_cybsaHelper;

    /**
     * @var Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var Cybage\Cybersourcesa\Model\CardsFactory
     */
    protected $_cardModelFactory;

    /**
     * @var Cybage\Cybersourcesa\Model\Payment
     */
    protected $cybrcaPaymentModel;

    /**
     * @var Magento\Framework\DB\Transaction
     */
    protected $transaction;

    /**
     * @var Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var Magento\Sales\Model\Order\PaymentFactory
     */
    protected $paymentFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context, 
        \Magento\Checkout\Model\Session $checkoutsession, 
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory, 
        \Magento\Framework\DB\Transaction $transaction, 
        \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig, 
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender, 
        \Magento\Sales\Model\Order\PaymentFactory $paymentFactory, 
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Cybage\Cybersource\Helper\Data $cybsaHelper,
        \Cybage\Cybersource\Model\Cybersourcesa $cybrcaPaymentModel
    ) {
        $this->checkoutsession = $checkoutsession;
        $this->_orderFactory = $orderFactory;
        $this->_cybsaHelper = $cybsaHelper;
        $this->regionFactory = $regionFactory;
        $this->cybrcaPaymentModel = $cybrcaPaymentModel;
        $this->transaction = $transaction;
        $this->_urlBuilder = $context->getUrl();
        $this->scopeConfig = $_scopeConfig;
        $this->orderSender = $orderSender;
        $this->paymentFactory = $paymentFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->_isScopePrivate = true;
        parent::__construct($context);
    }

}
