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

namespace Cybage\Cybersource\Block;

class Info extends \Magento\Payment\Block\Info\Cc {

    /**
     *
     * @var boolean 
     */
    protected $_isProgressBlockFlag = true;

    /**
     *
     * @var String 
     */
    protected $_template = 'Cybage_Cybersource::info.phtml';

    /**
     * @var Magento\Payment\Model\Config
     */
    protected $_paymentConfig;

    /**
     * @var Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var Cybage\Cybersource\Model\Payment\Cards
     */
    protected $cardpayment;

    /**
     * @var Cybage\Cybersource\Helper\Data
     */
    protected $cybersourceHelper;

    /**
     * @var Magento\Framework\Pricing\Helper\Data
     */
    protected $currencyHelper;

    /**
     *
     * @var Magento\Framework\App\State
     */
    protected $_state;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Store\Model\StoreManager $storeManager,
        \Cybage\Cybersource\Model\Sa\Cards $cardpayment,
        \Cybage\Cybersource\Helper\Data $cybersourceHelper,
        \Magento\Framework\Pricing\Helper\Data $currencyHelper,
        \Magento\Framework\App\State $state,
        array $data = []
    ) {
        $this->_paymentConfig = $paymentConfig;
        $this->storeManager = $storeManager;
        $this->cardpayment = $cardpayment;
        $this->cybersourceHelper = $cybersourceHelper;
        $this->currencyHelper = $currencyHelper;
        $this->_state = $state;
        parent::__construct($context, $paymentConfig, $data);
    }

    /**
     * Set chout progress block
     * 
     * @param type $flag
     * @return $this
     */
    public function setCheckoutProgressBlock($flag) {
        $this->_isProgressBlockFlag = $flag;

        return $this;
    }

    /**
     * Return payment method specification information
     * @return type
     */
    public function getSpecificInformation() {
        return $this->_prepareSpecificInformation()->getData();
    }

    /**
     * It returns used cards details for an order.
     *
     * @return array
     */
    public function getCards() {
        $this->cardpayment->setPayment($this->getInfo());
        $cardInfo = $this->cardpayment->getCards();
        $card = array();
        $data = array();
        $lastTransactionId = $this->getData('info')->getData('cc_trans_id');
        $cardTransactionId = $cardInfo->getTransactionId();
        if ($lastTransactionId == $cardTransactionId) {
            if ($cardInfo->getProcessedAmount()) {
                $amount = $this->currencyHelper->currency($cardInfo->getProcessedAmount(), true, false);
                $data['Processed Amount'] = $amount;
            }
            if ($cardInfo->getBalanceOnCard() && is_numeric($cardInfo->getBalanceOnCard())) {
                $balance = $this->currencyHelper->currency($cardInfo->getBalanceOnCard(), true, false);
                $data['Remaining Balance'] = $balance;
            }
            if ($this->_state->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
                
                if ($cardInfo->getCVNResultCode() && is_string($cardInfo->getCVNResultCode())) {
                    $data['CVN Response'] = $this->cybersourceHelper->getCvnLabel($cardInfo->getCVNResultCode());
                }
                
                 if ($cardInfo->getCardCodeResponseCode() && is_string($cardInfo->getreconciliationID())) {
                    $data['CCV Response'] = $cardInfo->getCardCodeResponseCode();
                }
                
                if ($cardInfo->getApprovalCode() && is_string($cardInfo->getApprovalCode())) {
                    $data['Approval Code'] = $cardInfo->getApprovalCode();
                }

                if ($cardInfo->getMethod() && is_numeric($cardInfo->getMethod())) {
                    $data['Method'] = ($cardInfo->getMethod() == 'CC') ? __('Credit Card') : __('eCheck');
                }

                if ($cardInfo->getLastTransId() && $cardInfo->getLastTransId()) {
                    $data['Transaction Id'] = str_replace(array('-capture', '-void', '-refund'), '', $cardInfo->getLastTransId());
                }
            }

            $this->setCardInfoObject($cardInfo);

            $card = array_merge($this->getSpecificInformation(), $data);
            $this->unsCardInfoObject();
            $this->_paymentSpecificInformation = null;
        }

        if ($this->getInfo()->getCcType() && $this->_isProgressBlockFlag && !empty($card) == 0) {
            $card = $this->getSpecificInformation();
        }
        return $card;
    }

}
