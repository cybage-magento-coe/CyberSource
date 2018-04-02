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

namespace Cybage\Cybersource\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper {

    const REQUEST_TYPE_AUTH_ONLY = 'AUTH_ONLY';
    const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const REQUEST_TYPE_PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';
    const REQUEST_TYPE_CREDIT = 'CREDIT';
    const REQUEST_TYPE_VOID = 'VOID';

    
    

    /**
     * @var \Magento\Framework\Encryption\Encryptor
     */
    protected $encryptor;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    protected $_backend = false;
    /**
     *
     * @var Magento\Backend\Model\Session
     */
    protected $_adminsession;
    /**
     *
     * @var Magento\Framework\Registry 
     */
    protected $_registry;
    
    /**
     *
     * @var Magento\Store\Model\StoreManagerInterface 
     */
    protected $_storeManager;
    
    /**
     * Cybersource SA config path
     */
    const XML_PATH_SA_ACTIVE = 'payment/cybersourcesa/active';
    /**
     * Cybersource SA Tital
     */
    const XML_PATH_SA_TITLE = 'payment/cybersourcesa/title';

    /**
     * Cybersource SA Redirect message
     */
    const XML_PATH_SA_RDIRECT_LABEL = 'payment/cybersourcesa/redirect_label';

    /**
     * Cybersource SA Merchant ID
     */
    const XML_PATH_SA_MERCHANT_ID = 'payment/cybersourcesa/merchantid';

    /**
     * Cybersource SA Merchant ID
     */
    const XML_PATH_SA_TRANS_KEY = 'payment/cybersourcesa/trans_key';

    /**
     * Cybersource SA access key
     */
    const XML_PATH_SA_ACCESS_KEY = 'payment/cybersourcesa/access_key';

    /**
     * Cybersource SA Profile ID
     */
    const XML_PATH_SA_PROFILE_ID = 'payment/cybersourcesa/profile_id';

    /**
     * Cybersource SA Secret key
     */
    const XML_PATH_SA_SECRET_KEY = 'payment/cybersourcesa/secret_key';

    /**
     * Cybersource SA test mode
     */
    const XML_PATH_SA_TEST = 'payment/cybersourcesa/test';

    /**
     * Cybersource SA Merchant ID
     */
    const XML_PATH_SA_PAYMENT_ACTION = 'payment/cybersourcesa/payment_action';

    /**
     * Cybersource SA cc type
     */
    const XML_PATH_SA_CCTYPES = 'payment/cybersourcesa/cctypes';

    /**
     * Cybersource SA soap gateway url
     */
    const XML_PATH_SOAP_GATEWAY_URL = 'payment/cybersourcesa/soap_gateway_url';

    /**
     * Cybersource SA Test gateway Url
     */
    const XML_PATH_SOAP_TEST_GATEWAY_URL = 'payment/cybersourcesa/test_soap_gateway_url';

    /**
     * Cybersource SA Order Status
     */
    const XML_PATH_NEW_ORDER_STATUS = 'payment/cybersourcesa/order_status';
    
    /**
     * 
     */ 
    const CUSTOMER_ADDRESS_TEMPLATES_HTML = 'customer/address_templates/html';

    /**
     * Cybersource token config path
     */
    const XML_PATH_TOKEN_ACTIVE = 'payment/cybersourcetoken/active';

    /**
     * Cybersource token config path
     */
    const XML_PATH_TOKEN_RDIRECT_LABEL = 'payment/cybersourcetoken/redirect_label';
    
    
    
    
    protected $_errorMessage = array(
        '100' => 'Successful transaction.',
        '102' => 'One or more fields in the request contain invalid data.
Possible action: see the reply field invalid_fields to ascertain which fields are invalid. Resend the request with the correct information.',
        '104' => 'The access_key and transaction_uuid fields for this authorization request match the access_key and transaction_uuid fields of another authorization request that you sent within the past 15 minutes.
Possible action: resend the request with unique access_key and transaction_uuid fields.
A duplicate transaction was detected. The transaction might have already been processed.
Possible action: before resubmitting the transaction, use the single transaction query or search for the transaction using the Business Center (see Viewing Transactions in the Business Center) to confirm that the transaction has not yet been processed.',
        '110' => 'Only a partial amount was approved.',
        '150' => 'General system failure.
Possible action: To avoid duplicating the transaction, do not resend the request until you have reviewed the transaction status either directly in the Business Center or programmatically through the Single Transaction Query.',
        '151' => 'The request was received but a server timeout occurred. This error does not include timeouts between the client and the server.
Possible action: To avoid duplicating the transaction, do not resend the request until you have reviewed the transaction status either directly in the Business Center or programmatically through the Single Transaction Query.',
        '152' => 'The request was received, but a service timeout occurred.
Possible action: To avoid duplicating the transaction, do not resend the request until you have reviewed the transaction status either directly in the Business Center or programmatically through the Single Transaction Query.',
        '200' => 'The authorization request was approved by the issuing bank but declined by Cybersource because it did not pass the Address Verification System (AVS) check.
Possible action: you can capture the authorization, but consider reviewing the order for fraud.',
        '201' => 'The issuing bank has questions about the request. You do not receive an authorization code programmatically, but you might receive one verbally by calling the processor.
Possible action: call your processor to possibly receive a verbal authorization. For contact phone numbers, refer to your merchant bank information.',
        '202'  => 'Expired card. You might also receive this value if the expiration date you provided does not match the date the issuing bank has on file.
Possible action: request a different card or other form of payment.',
        '203' => 'General decline of the card. No other information was provided by the issuing bank.
Possible action: request a different card or other form of payment.',
        '204' => 'Insufficient funds in the account.
Possible action: request a different card or other form of payment.',
        '205' => 'Stolen or lost card.
Possible action: review this transaction manually to ensure that you submitted the correct information.',
        '207' => 'Issuing bank unavailable.
Possible action: To avoid duplicating the transaction, do not resend the request until you have reviewed the transaction status either directly in the Business Center or programmatically through the Single Transaction Query.',
        '208' => 'Inactive card or card not authorized for card-not-present transactions.
Possible action: request a different card or other form of payment.',
        '210' => 'The card has reached the credit limit.
Possible action: request a different card or other form of payment.',
        '211' => 'Invalid CVN.
Possible action: request a different card or other form of payment.',
        '221' => 'The customer matched an entry on the processor’s negative file.
Possible action: review the order and contact the payment processor.',
        '222' => 'Account frozen.',
        '230' => 'The authorization request was approved by the issuing bank but declined by Cybersource because it did not pass the CVN check.
Possible action: you can capture the authorization, but consider reviewing the order for the possibility of fraud.',
        '231' => 'Invalid account number.
Possible action: request a different card or other form of payment.',
        '232' => 'The card type is not accepted by the payment processor.
Possible action: contact your merchant bank to confirm that your account is set up to receive the card in question.',
        '233' => 'General decline by the processor.
Possible action: request a different card or other form of payment.',
        '234' => 'There is a problem with the information in your Cybersource account.
Possible action: do not resend the request. Contact Cybersource Customer Support to correct the information in your account.',
        '236' => 'Processor failure.
Possible action: To avoid duplicating the transaction, do not resend the request until you have reviewed the transaction status either directly in the Business Center or programmatically through the Single Transaction Query.',
        '240' =>  'The card type sent is invalid or does not correlate with the payment card number.
Possible action: confirm that the card type correlates with the payment card number specified in the request; then resend the request.',
        '475' => 'The cardholder is enrolled for payer authentication.
Possible action: authenticate cardholder before proceeding.',
        '476' => 'Payer authentication could not be authenticated.',
        '481' => 'Transaction declined based on your payment settings for the profile.
Possible action: review the risk score settings for the profile.',
        '520' => 'The authorization request was approved by the issuing bank but declined by Cybersource based on your legacy Smart Authorization settings.
Possible action: review the authorization request.');

    public function __construct(Context $context, 
        ScopeConfigInterface $scopeConfig, 
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\Session $adminsession
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->_storeManager = $storeManager;
        $this->_registry = $registry;
        $this->_adminsession = $adminsession;
        $this->setStore();
        parent::__construct($context);
    }

    /**
     * Set Store for Admin action to get configuration value.
     */
    public function setStore(){
        $this->_backend = ($this->_storeManager->getStore()->getId() == 0) ? true : false;
        if ($this->_backend && $this->_registry->registry('current_order') != false) {
            $this->setStoreId($this->_registry->registry('current_order')->getStoreId());
            $this->_adminsession->setCustomerStoreId(null);
        } elseif ($this->_backend && $this->_registry->registry('current_invoice') != false) {
            $this->setStoreId($this->_registry->registry('current_invoice')->getStoreId());
            $this->_adminsession->setCustomerStoreId(null);
        } elseif ($this->_backend && $this->_registry->registry('current_creditmemo') != false) {
            $this->setStoreId($this->_registry->registry('current_creditmemo')->getStoreId());
            $this->_adminsession->setCustomerStoreId(null);
        } elseif ($this->_backend && $this->_registry->registry('current_customer') != false) {
            $this->setStoreId($this->_registry->registry('current_customer')->getStoreId());
            $this->_adminsession->setCustomerStoreId($this->_registry->registry('current_customer')->getStoreId());
        } elseif ($this->_backend && $this->_session->getStore()->getId() > 0) {
            $this->setStoreId($this->_session->getStore()->getId());
            $this->_adminsession->setCustomerStoreId(null);
        } else {
            $customerStoreSessionId = $this->_adminsession->getCustomerStoreId();
            if ($this->_backend && $customerStoreSessionId != null) {
                $this->setStoreId($customerStoreSessionId);
            } else {
                $this->setStoreId($this->_storeManager->getStore()->getId());
            }
        }
    }

    /**
     * 
     * @param type $storeId
     * @return $this
     */
    public function setStoreId($storeId = 0)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    /**
     * 
     * @param type $code
     * @return type
     */
    public function isActive($code) {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if ($code == 'cybersourcesa') {
            return $this->_scopeConfig->getValue(self::XML_PATH_SA_ACTIVE, $storeScope);
        }
        if ($code == 'cybersourcetoken') {
            return $this->_scopeConfig->getValue(self::XML_PATH_TOKEN_ACTIVE, $storeScope);
        }
    }

    /**
     * Return the Message on redirection page
     * @param type $code
     * @return type
     */
    public function getRedirectLabel($code) {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if ($code == 'cybersourcesa') {
            return $this->_scopeConfig->getValue(self::XML_PATH_SA_RDIRECT_LABEL, $storeScope);
        }
        if ($code == 'cybersourcetoken') {
            return $this->_scopeConfig->getValue(self::XML_PATH_TOKEN_RDIRECT_LABEL, $storeScope);
        }
    }

    /**
     * Returns the transaction message to show on payment info for order, invoice, creditmemo, etc.
     * 
     * @param type $payment
     * @param type $requestType
     * @param type $lastTransactionId
     * @param type $card
     * @param type $amount
     * @param type $exception
     * @return boolean
     */
    public function getTransactionMessage($payment, $requestType, $lastTransactionId, $card, $amount = false, $exception = false ) {
        $additionalMessage = false;
        $operation = $this->_getOperation($requestType);
        if (!$operation) {
            return false;
        }

        if ($amount) {
            $amount = sprintf('amount %s', $this->_formatPrice($payment, $amount));
        }

        if ($exception) {
            $result = sprintf('failed');
        } else {
            $result = sprintf('successful');
        }

        $card = sprintf('Credit Card: xxxx-%s', $card->getCcLast4());

        $pattern = '%s %s %s - %s.';
        $texts = array($card, $amount, $operation, $result);

        if (!is_null($lastTransactionId)) {
            $pattern .= ' %s.';
            $texts[] = sprintf('Cybersourcesa Transaction ID %s', $lastTransactionId);
        }

        if ($additionalMessage) {
            $pattern .= ' %s.';
            $texts[] = $additionalMessage;
        }
        $pattern .= ' %s';
        $texts[] = $exception;

        return call_user_func_array('sprintf', array_merge(array($pattern), $texts));
    }

    /**
     * @param type $requestType
     *
     * @return bool
     */
    protected function _getOperation($requestType) {
        switch ($requestType) {
            case self::REQUEST_TYPE_AUTH_ONLY:
                return __('authorize');
            case self::REQUEST_TYPE_AUTH_CAPTURE:
                return __('authorize and capture');
            case self::REQUEST_TYPE_PRIOR_AUTH_CAPTURE:
                return __('capture');
            case self::REQUEST_TYPE_CREDIT:
                return __('refund');
            case self::REQUEST_TYPE_VOID:
                return __('void');
            default:
                return false;
        }
    }

    /**
     * @param type $payment
     * @param type $amount
     *
     * @return type
     */
    protected function _formatPrice($payment, $amount) {
        return $payment->getOrder()->getBaseCurrency()->formatTxt($amount);
    }

    public function getCvnLabel($cvn)
    {
        if (isset($this->_cvnResponses[ $cvn ])) {
            return __(sprintf('%s (%s)', $cvn, $this->_cvnResponses[ $cvn ]));
        }
        return $cvn;
    }
    
    public function getConfigData($field, $storeId = null)
    {
        return $this->_scopeConfig->getValue($field, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getIsActive()
    {
        return $this->getConfigData(self::XML_PATH_SA_ACTIVE, $this->_storeId);
    }

    /**
     * This method will return whether test mode is enabled or not.
     *
     * @return bool
     */
    public function getIsTestMode()
    {
        return $this->getConfigData(self::XML_PATH_SA_TEST, $this->_storeId);
    }

    /**
     * This metod will return CYBERSOURCESA Gateway url depending on test mode enabled or not.
     *
     * @return string
     */
    public function getGatewayUrl()
    {
        $isTestMode = $this->getIsTestMode();
        $gatewayUrl = ($isTestMode) ? $this->getConfigData(self::XML_PATH_SOAP_TEST_GATEWAY_URL, $this->_storeId) : $this->getConfigData(self::XML_PATH_SOAP_GATEWAY_URL, $this->_storeId);
        return trim($gatewayUrl);
    }

    /**
     * This methos will return Firstdata payment method title set by admin to display on onepage checkout payment step.
     *
     * @return string
     */
    public function getMethodTitle()
    {
        return (string) $this->getConfigData(self::XML_PATH_SA_TITLE, $this->_storeId);
    }

    /**
     * This method will return merchant api login id set by admin in configuration.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->encryptor->decrypt($this->getConfigData(self::XML_PATH_SA_MERCHANT_ID, $this->_storeId));
    }

    /**
     * This method will return merchant api transaction key set by admin in configuration.
     *
     * @return string
     */
    public function getTransKey()
    {
        return $this->encryptor->decrypt($this->getConfigData(self::XML_PATH_SA_TRANS_KEY, $this->_storeId));
    }

    /**
     * Return merchant access key set by admin in configuration.
     *
     * @return string
     */
    public function getAccessKey()
    {
        return $this->encryptor->decrypt($this->getConfigData(self::XML_PATH_SA_ACCESS_KEY, $this->_storeId));
    }

    /**
     * Return merchant profile id set by admin in configuration.
     *
     * @return string
     */
    public function getProfileId()
    {
        return $this->encryptor->decrypt($this->getConfigData(self::XML_PATH_SA_PROFILE_ID, $this->_storeId));
    }

    /**
     * Return merchant secret key set by admin in configuration.
     *
     * @return string
     */
    public function getSecretKey()
    {
        return $this->encryptor->decrypt($this->getConfigData(self::XML_PATH_SA_SECRET_KEY, $this->_storeId));
    }
    /**
     * This will returne payment action whether it is authorized or authorize and capture.
     *
     * @return string
     */
    public function getPaymentAction()
    {
        return (string) $this->getConfigData(self::XML_PATH_SA_PAYMENT_ACTION, $this->_storeId);
    }

    /**
     * Method which will return whether customer must save credit card as profile of not.
     *
     * @return bool
     */
    public function getSaveCardOptional()
    {
        return (boolean) $this->getConfigData(self::CYBERSOURCESA_CARD_SAVE_OPTIONAL, $this->_storeId);
    }

    /**
     * @return config value
     */
    public function getCcTypes()
    {
        return $this->getConfigData(self::XML_PATH_SA_CCTYPES, $this->_storeId);
    }

    /**
     * @return bool
     */
    public function getDefaultFormat()
    {
        return $this->getConfigData(self::CUSTOMER_ADDRESS_TEMPLATES_HTML, $this->_storeId);
    }
    
    /**
     * Create hash sign for security and API params.
     *
     * @param type $params
     * @param type $signedField
     *
     * @return string
     */
    public function getHashSign($params, $signedField = 'signed_field_names')
    {
        $signedFieldNames = explode(',', $params[$signedField]);
        foreach ($signedFieldNames as &$field) {
            $dataToSign[] = $field.'='.$params[$field];
        }
        $data = implode(',', $dataToSign);
        $secretKey = $this->getSecretKey();
        $hashSign = base64_encode(hash_hmac('sha256', $data, $secretKey, true));

        return $hashSign;
    }
    
}
