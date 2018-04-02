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

namespace Cybage\Cybersource\Model;

class Cybersourcesa extends \Magento\Payment\Model\Method\AbstractMethod {
    
    
    const CODE = 'cybersourcesa';
    const PAYMENT_LIVE_URL = 'https://secureacceptance.cybersource.com/pay';
    const PAYMENT_TEST_URL = 'https://testsecureacceptance.cybersource.com/pay';
    const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
    const REQUEST_TYPE_AUTH_ONLY = 'AUTH_ONLY';
    const REQUEST_TYPE_CREDIT = 'CREDIT';
    const REQUEST_TYPE_VOID = 'VOID';
    const REQUEST_TYPE_PRIOR_AUTH_CAPTURE = 'PRIOR_AUTH_CAPTURE';
    const RESPONSE_CODE_SUCCESS = 100;
    
    protected $_objectManager;
    protected $cardpayment;
   
    protected $_cardsStorage = null;
    protected $_code = 'cybersourcesa';
    protected $_isOffline = false;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCancel = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;
    protected $_infoBlockType = 'Cybage\Cybersource\Block\Info';
    protected $_realTransactionIdKey = 'real_transaction_id';
    protected $_isGatewayActionsLockedKey = 'is_gateway_actions_locked';
    
    /**
     * @var Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;

 

    /**
     * @var Magento\Checkout\Model\Session.
     */
    protected $checkoutsession;

    /**
     * @var Magento\Sales\Model\OrderFactory.
     */
    protected $_orderFactory;

    /**
     * @var Cybage\Cybersource\Helper\Data
     */
    protected $cybsaHelper;

    /**
     * @var Magento\Framework\Math\Random
     */
    protected $_random;

    /**
     * @var Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Magento\Quote\Api\Data\CartInterface
     */
    protected $cardFactory;

    /**
     * @var Magedelight\Cybersourcesa\Model\Api\Soap
     */
    protected $soapmodel;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Checkout\Model\Session $checkoutsession,
        \Magento\Framework\Math\Random $random,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Cybage\Cybersource\Model\Api\Soap $soapmodel,
        \Cybage\Cybersource\Helper\Data $cybsaHelper,
        \Cybage\Cybersource\Model\Sa\Cards $cardpayment,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,array $data = []
    ) {
        $this->_remoteAddress = $remoteAddress;
        $this->checkoutsession = $checkoutsession;
        $this->_random = $random;
        $this->customerSession = $customerSession;
        $this->regionFactory = $regionFactory;
        $this->storeManager = $storeManager;
        $this->cybsaHelper = $cybsaHelper;
        $this->_objectManager = $objectManager;
        $this->cardpayment = $cardpayment;
        $this->soapmodel = $soapmodel;
        parent::__construct(
                $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data
        );
        $this->_backend = ($this->storeManager->getStore()->getId() == 0) ? true : false;
        if ($this->_backend && $this->_registry->registry('current_order')) {
            $this->setStore($this->_registry->registry('current_order')->getStoreId());
        } elseif ($this->_backend && $this->_registry->registry('current_invoice')) {
            $this->setStore($this->_registry->registry('current_invoice')->getStoreId());
        } elseif ($this->_backend && $this->_registry->registry('current_creditmemo')) {
            $this->setStore($this->_registry->registry('current_creditmemo')->getStoreId());
        } elseif ($this->_backend && $this->_registry->registry('current_customer') != false) {
            $this->setStore($this->_registry->registry('current_customer')->getStoreId());
        } elseif ($this->_backend && $this->_objectManager->get('Magento\Backend\Model\Session\Quote')->getStoreId() > 0) {
            $this->setStore($this->_objectManager->get('Magento\Backend\Model\Session\Quote')->getStoreId());
        } else {
            $this->setStore($this->storeManager->getStore()->getId());
        }
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $exceptionMessage = false;
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase(__('Invalid amount for authorization.')));
        }
        $this->_initCardsStorage($payment);
        if (empty($this->_postData)) {
            $this->_postData = $this->_registry->registry('postdata');
        }
        try {
            $csToRequestMap = self::REQUEST_TYPE_AUTH_ONLY;
            $payment->setAnetTransType($csToRequestMap);
            $payment->setAmount($amount);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase(__('Cybersource Gateway request error: ' . $e->getMessage())));
        }
        if ($exceptionMessage !== false) {
            throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase($exceptionMessage));
        }
        $payment->setSkipTransactionCreation(true);
        parent::authorize($payment, $amount);
        return $this;
    }

    /**
     * Return order API type depends on config.
     *
     * @return string
     */
    public function getIssuerUrls() {
        return ['live' => self::PAYMENT_LIVE_URL,
            'test' => self::PAYMENT_TEST_URL,];
    }

    /**
     * Return order API URL type depends on config.
     *
     * @return string
     */
    public function getCybersourcesaUrl() {
        $setIssuerUrls = $this->getIssuerUrls();
        if ($this->getConfigData('test')) {
            return $setIssuerUrls['test'];
        } else {
            return $setIssuerUrls['live'];
        }
    }

    /**
     * Prepare order API fields.
     *
     * @return array
     */
    public function getFormFields($order) {

        $payment = $this->checkoutsession->getQuote()->getPayment();
        $paymentAction = $this->cybsaHelper->getPaymentAction();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $customeremail = $order->getCustomerEmail();
        $transactionType = 'sale';
        if ($paymentAction == 'authorize') {
            $transactionType = 'authorization';
        }
        $this->_formFields['access_key'] = $this->cybsaHelper->getAccessKey();
        $this->_formFields['profile_id'] = $this->cybsaHelper->getProfileId();
        $this->_formFields['transaction_uuid'] = $this->_random->getUniqueHash();
        $this->_formFields['signed_field_names'] = 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency,bill_to_address_city,bill_to_address_country,bill_to_address_line1,bill_to_address_line2,bill_to_address_postal_code,bill_to_address_state,bill_to_company_name,bill_to_email,bill_to_forename,bill_to_surname,bill_to_phone,customer_ip_address,customer_cookies_accepted,skip_decision_manager,device_fingerprint_id,';
        if ($this->customerSession->isLoggedIn()) {
            $this->_formFields['signed_field_names'] .= 'consumer_id,';
        }
        $this->_formFields['unsigned_field_names'] = '';
        $this->_formFields['signed_date_time'] = gmdate("Y-m-d\TH:i:s\Z");
        $this->_formFields['locale'] = 'en';
        $this->_formFields['transaction_type'] = $transactionType;
        $this->_formFields['reference_number'] = $order->getRealOrderId();
        $this->_formFields['amount'] = sprintf('%.2f', $order->getGrandTotal());
        $this->_formFields['currency'] = $this->getOrderCurrency($order);
        $this->_formFields['customer_cookies_accepted'] = 'true';
        $this->_formFields['skip_decision_manager'] = 'false';
        $this->_formFields['device_fingerprint_id'] = $order->getRealOrderId();
        $this->createItemInfoRequest($payment, $order);
        $this->createBillingAddressRequest($customeremail, $billingAddress);
        $this->createShippingAddressRequest($shippingAddress);
        $this->_formFields['signature'] = $this->cybsaHelper->getHashSign($this->_formFields);

        return $this->_formFields;
    }

    /**
     * prepare order item request for order API.
     *
     * @param Varien_Object $payment
     * @param type          $quantity
     */
    protected function createItemInfoRequest(\Magento\Framework\DataObject $payment, $order, $quantity = false) {
        if (is_object($payment)) {
            $hasVirtual = false;
            if ($order instanceof \Magento\Sales\Model\Order) {
                $i = 0;
                foreach ($order->getAllVisibleItems() as $_item) {
                    if ($_item->getIsVirtual()) {
                        $hasVirtual = true;
                    }
                    $this->_formFields["item_{$i}_name"] = substr($_item->getName(), 0, 30);
                    $this->_formFields["item_{$i}_sku"] = $_item->getSku();
                    $this->_formFields["item_{$i}_unit_price"] = round($_item->getBasePrice(), 2);
                    $this->_formFields["item_{$i}_tax_amount"] = round($_item->getData('tax_amount'), 2);
                    $this->_formFields["item_{$i}_quantity"] = (int) $_item->getQtyOrdered();
                    $this->_formFields['signed_field_names'] .= "item_{$i}_name,item_{$i}_sku,item_{$i}_unit_price,item_{$i}_tax_amount,item_{$i}_quantity,";
                    ++$i;
                }
            }
            $this->_formFields['signed_field_names'] .= 'line_item_count';
            if (!$hasVirtual) {
                $this->_formFields['signed_field_names'] .= ',ship_to_forename,ship_to_surname,ship_to_company_name,ship_to_address_line1,ship_to_address_line2,ship_to_address_city,ship_to_address_state,ship_to_address_postal_code,ship_to_address_country,';
                $this->_formFields['signed_field_names'] .= 'ship_to_phone';
            }
            $this->_formFields['line_item_count'] = $i;
        }
    }

    /**
     * prepare billing details for order API.
     *
     * @param type $customeremail
     * @param type $billing
     */
    protected function createBillingAddressRequest($customeremail, $billing) {
        if (!$customeremail) {
            $customeremail = $this->checkoutsession->getQuote()->getBillingAddress()->getEmail();
        }
        if ($this->customerSession->isLoggedIn()) {
            $customerData = $this->customerSession->getCustomer();
            $customerId = $customerData->getId();
        }
        $billCountry = $billing->getCountryId();
        $regionName = $billing->getRegion();
        if ($billCountry == 'US' || $billCountry == 'CA') {
            $region = $this->regionFactory->create()->loadByName($billing->getRegion(), $billCountry);
            $regionName = $region->getCode();
        }
        $this->_formFields['bill_to_forename'] = $billing->getFirstname();
        $this->_formFields['bill_to_surname'] = $billing->getLastname();
        $this->_formFields['bill_to_company_name'] = $billing->getCompany();
        $this->_formFields['bill_to_address_line1'] = (is_array($billing->getStreet(1))) ? implode(',', $billing->getStreet(1)) : $billing->getStreet(1);
        $this->_formFields['bill_to_address_line2'] = (is_array($billing->getStreet(2))) ? implode(',', $billing->getStreet(2)) : $billing->getStreet(2);
        $this->_formFields['bill_to_address_city'] = $billing->getCity();
        $this->_formFields['bill_to_address_state'] = substr($regionName, 0, 20);
        $this->_formFields['bill_to_address_postal_code'] = $billing->getPostcode();
        $this->_formFields['bill_to_address_country'] = $billCountry;
        $this->_formFields['bill_to_phone'] = $billing->getTelephone();
        $this->_formFields['bill_to_email'] = $customeremail;
        $this->_formFields['customer_ip_address'] = $this->_remoteAddress->getRemoteAddress();
        if (isset($customerId)) {
            $this->_formFields['consumer_id'] = $customerId;
        }
    }

    /**
     * prepare shipping detail for order API.
     *
     * @param type $shipping
     */
    protected function createShippingAddressRequest($shipping) {
        if ($shipping) {
            $shipCountry = $shipping->getCountryId();
            $regionName = $shipping->getRegion();
            if ($shipCountry == 'US' || $shipCountry == 'CA') {
                $region = $this->regionFactory->create()->loadByName($shipping->getRegion(), $shipCountry);
                $regionName = $region->getCode();
            }
            $this->_formFields['ship_to_forename'] = $shipping->getFirstname();
            $this->_formFields['ship_to_surname'] = $shipping->getLastname();
            $this->_formFields['ship_to_company_name'] = $shipping->getCompany();
            $this->_formFields['ship_to_address_line1'] = (is_array($shipping->getStreet(1))) ? implode(',', $shipping->getStreet(1)) : $shipping->getStreet(1);
            $this->_formFields['ship_to_address_line2'] = (is_array($shipping->getStreet(2))) ? implode(',', $shipping->getStreet(2)) : $shipping->getStreet(2);
            $this->_formFields['ship_to_address_city'] = $shipping->getCity();
            $this->_formFields['ship_to_address_state'] = substr($regionName, 0, 20);
            $this->_formFields['ship_to_address_postal_code'] = $shipping->getPostcode();
            $this->_formFields['ship_to_address_country'] = $shipCountry;
            $this->_formFields['ship_to_phone'] = $shipping->getTelephone();
        }
    }

    /**
     * @return string
     */
    public function getOrderCurrency($order) {
        $currency = $order->getOrderCurrency();
        if (is_object($currency)) {
            $currency = $currency->getCurrencyCode();
        }

        return $currency;
    }

    /**
     * get card storage for payment.
     *
     * @param type $payment
     *
     * @return type
     */
    public function getCardsStorage($payment = null) {
        if (is_null($payment)) {
            $payment = $this->getInfoInstance();
        }
        if (is_null($this->_cardsStorage)) {
            $this->_initCardsStorage($payment);
        }

        return $this->_cardsStorage;
    }

    /**
     * Init cards storage model.
     *
     * @param Mage_Payment_Model_Info $payment
     */
    protected function _initCardsStorage($payment) {
        $this->_cardsStorage = $this->cardpayment->setPayment($payment);
    }

    /**
     * @param type $data
     *
     * @return \Cybage_Cybersource_Model_Cybersourcesa
     */
    public function assignData(\Magento\Framework\DataObject $data) {
        parent::assignData($data);
        $post = $data->getData()['additional_data'];
        if (empty($this->_postData)) {
            $this->_postData = $post;
        }
        $this->_registry->register('postdata', $this->_postData);

        return $this;
    }

    /**
     * capture order amount.
     *
     * @param Varien_Object $payment
     * @param type          $amount
     *
     * @return Cybersourcesa
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase(__('Invalid amount for capture.')));
        }
        $this->_initCardsStorage($payment);
        if (empty($this->_postData)) {
            $this->_postData = $this->_registry->registry('postdata');
        }
        try {
            if ($this->_isPreauthorizeCapture($payment)) {
                $this->_preCaptureAuthorizePayment($payment, $amount);
                $payment->setSkipTransactionCreation(true);
            } else {

                $csToRequestMap = self::REQUEST_TYPE_AUTH_CAPTURE;
                $payment->setAnetTransType($csToRequestMap);
                $payment->setAmount($amount);
                $payment->setStatus(self::STATUS_APPROVED)
                        ->setLastTransId($this->getTransactionId());

                $payment->setIsTransactionPending(true);
            }
        } catch (\Exception $ex) {
            throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase('Gateway request error: ' . $ex->getMessage()));
        }
        return $this;
    }

    /**
     * capture preauthorize payment.
     *
     * @param type $payment
     *
     * @return bool
     */
    protected function _isPreauthorizeCapture(\Magento\Sales\Model\Order\Payment $payment) {
        $card = $this->getCardsStorage()->getCards();
        $lastTransactionId = $payment->getData('cc_trans_id');
        $cardTransactionId = $card->getTransactionId();
        if ($lastTransactionId == $cardTransactionId) {
            if ($payment->getCcTransId()) {
                return true;
            }
            return false;
        }
    }

    /**
     * capture pre authorize payment.
     *
     * @param type $payment
     * @param type $requestedAmount
     */
    protected function _preCaptureAuthorizePayment($payment, $requestedAmount) {
        $cardsStorage = $this->getCardsStorage($payment);
        if ($this->_formatAmount(
                        $cardsStorage->getProcessedAmount() - $cardsStorage->getCapturedAmount()
                ) < $requestedAmount
        ) {
            throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase(__('Invalid amount for capture.')));
        }
        $messages = array();
        $isSuccessful = false;
        $isFiled = false;
        $isFiled = false;
        $card = $cardsStorage->getCards();
        $lastTransactionId = $payment->getData('cc_trans_id');
        $cardTransactionId = $card->getTransactionId();
        if ($lastTransactionId == $cardTransactionId) {
            if ($requestedAmount > 0) {
                $prevCaptureAmount = $card->getCapturedAmount();
                $cardAmountForCapture = $card->getProcessedAmount();
                if ($cardAmountForCapture > $requestedAmount) {
                    $cardAmountForCapture = $requestedAmount;
                }
                try {
                    $newTransaction = $this->_capturePreauthorizePayment(
                            $payment, $cardAmountForCapture, $card
                    );
                    if ($newTransaction != null) {
                        $messages[] = $newTransaction->getMessage();
                        $isSuccessful = true;
                    }
                } catch (\Exception $e) {
                    $messages[] = $e->getMessage();
                    $isFiled = true;
                }
                $newCapturedAmount = $prevCaptureAmount + $cardAmountForCapture;
                $card->setCapturedAmount($newCapturedAmount);
                $cardsStorage->updateCard($card);
                $requestedAmount = $this->_formatAmount($requestedAmount - $cardAmountForCapture);
                if ($isSuccessful) {
                    $balance = $card->getProcessedAmount() - $card->getCapturedAmount();
                    if ($balance > 0) {
                        $payment->setAnetTransType(self::REQUEST_TYPE_AUTH_ONLY);
                        $payment->setAmount($balance);
                    }
                }
            }
        }
        if ($isFiled) {
            $this->_processFailureAction($payment, $messages, $isSuccessful);
        }
    }

    /**
     * capture pre authorize payment.
     *
     * @param type $payment
     * @param type $amount
     * @param type $card
     *
     * @return type
     */
    protected function _capturePreauthorizePayment($payment, $amount, $card) {
        $authTransactionId = $card->getLastTransId();
        if ($payment->getCcTransId()) {
            $newTransactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
            $payment->setAnetTransType(self::REQUEST_TYPE_PRIOR_AUTH_CAPTURE);
            $payment->setAmount($amount);
            $response = $this->soapmodel
                    ->prepareAuthorizeCaptureResponse($payment, $amount);
            if ($response->reasonCode == self::RESPONSE_CODE_SUCCESS) {
                $captureTransactionId = $response->requestID . '-capture';
                $card->setLastCapturedTransactionId($captureTransactionId);

                $this->_addTransaction(
                        $payment, $captureTransactionId, $newTransactionType, array(
                    'is_transaction_closed' => 0,
                    'parent_transaction_id' => $authTransactionId,
                        ), array($this->_realTransactionIdKey => $response->requestID), $this->cybsaHelper->getTransactionMessage(
                                $payment, self::REQUEST_TYPE_PRIOR_AUTH_CAPTURE, $response->requestID, $card, $amount
                        )
                );
            } else {
                $resonCode = $response->reasonCode;
                $exceptionMsg = __('Gateway error:' . $this->cybsaHelper->_errorMessage[$resonCode]);
                $exceptionMessage = $this->cybsaHelper->getTransactionMessage(
                        $payment, self::REQUEST_TYPE_PRIOR_AUTH_CAPTURE, $authTransactionId, $card, $amount, $exceptionMsg
                );
                throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase($exceptionMessage));
            }
        } else {
            return;
        }
    }

    /**
     * Add transaction comment to order.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param type $transactionId
     * @param type $transactionType
     * @param array $transactionDetails
     * @param array $transactionAdditionalInfo
     * @param type $message
     */
    protected function _addTransaction(\Magento\Sales\Model\Order\Payment $payment, $transactionId, $transactionType, array $transactionDetails = array(), array $transactionAdditionalInfo = array(), $message = false
    ) {
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);

        foreach ($transactionDetails as $key => $value) {
            $payment->setData($key, $value);
        }
        foreach ($transactionAdditionalInfo as $key => $value) {
            $payment->setTransactionAdditionalInfo($key, $value);
        }
        $transaction = $payment->addTransaction($transactionType, null, false, $message);

        $transaction->setMessage($message);

        return $transaction;
    }

    /**
     * format amount .
     *
     * @param type $amount
     * @param type $asFloat
     *
     * @return string
     */
    protected function _formatAmount($amount, $asFloat = false) {
        $amount = sprintf('%.2F', $amount);
        return $asFloat ? (float) $amount : $amount;
    }

    /**
     * handle failure transaction.
     *
     * @param type $payment
     * @param type $messages
     * @param type $isSuccessfulTransactions
     */
    protected function _processFailureAction($payment, $messages, $isSuccessfulTransactions) {
        if ($isSuccessfulTransactions) {
            $messages[] = __('Gateway actions are locked. Please log in to your Cybersource account to manually resolve the issue(s).');
            $currentOrderId = $payment->getOrder()->getId();
            $copyOrder = $this->ordermodel->load($currentOrderId);
            $copyOrder->getPayment()->setAdditionalInformation($this->_isGatewayActionsLockedKey, 1);
            foreach ($messages as $message) {
                $copyOrder->addStatusHistoryComment($message);
            }
            $copyOrder->save();
        }
        throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase(implode(' | ', $messages)));
    }
    
    protected function _isGatewayActionsLocked($payment)
    {
        return $payment->getAdditionalInformation($this->_isGatewayActionsLockedKey);
    }
    
    /**
     * we are doing any further processing if we need for invoice.
     * @param type $invoice
     * @param type $payment
     * @return $this
     */
    public function processInvoice($invoice, $payment)
    {
        $lastCaptureTransId = '';
        $cardsStorage = $this->getCardsStorage($payment);
        $card = $cardsStorage->getCards();
        $lastTransactionId = $payment->getData('cc_trans_id');
        $cardTransactionId = $card->getTransactionId();
        if ($lastTransactionId == $cardTransactionId) {
            $lastCapId = $card->getData('last_captured_transaction_id');
            if ($lastCapId && !empty($lastCapId) && !is_null($lastCapId)) {
                $lastCaptureTransId = $lastCapId;
            }
        }
        $invoice->setTransactionId($lastCaptureTransId);
        return $this;
    }

    /**
     * we are doing any further processing if we need for creditmemo
     * @param type $creditmemo
     * @param type $payment
     * @return $this
     */
    public function processCreditmemo($creditmemo, $payment)
    {
        $lastRefundedTransId = '';
        $cardsStorage = $this->getCardsStorage($payment);
        $card = $cardsStorage->getCards();
        $lastTransactionId = $payment->getData('cc_trans_id');
        $cardTransactionId = $card->getTransactionId();
        if ($lastTransactionId == $cardTransactionId) {
            $lastCardTransId = $card->getData('last_refunded_transaction_id');
            if ($lastCardTransId && !empty($lastCardTransId) && !is_null($lastCardTransId)) {
                $lastRefundedTransId = $lastCardTransId;
            }
        } 
        $creditmemo->setTransactionId($lastRefundedTransId);
        return $this;
    }
    
    
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $cardsStorage = $this->getCardsStorage($payment);
        if ($this->_formatAmount(
                $cardsStorage->getCapturedAmount() - $cardsStorage->getRefundedAmount()
                ) < $amount
            ) {
            throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase(__('Invalid amount for refund.')));
        }
        $messages = array();
        $isSuccessful = false;
        $isFiled = false;
            // Grab the invoice in case partial invoicing
            $creditmemo = $this->_registry->registry('current_creditmemo');
        if (!is_null($creditmemo)) {
            $this->_invoice = $creditmemo->getInvoice();
        }
        $card = $cardsStorage->getCards();
        $lastTransactionId = $payment->getData('cc_trans_id');
        $cardTransactionId = $card->getTransactionId();

        if ($lastTransactionId == $cardTransactionId) {
            if ($amount > 0) {
                $cardAmountForRefund = $this->_formatAmount($card->getCapturedAmount() - $card->getRefundedAmount());
                if ($cardAmountForRefund <= 0) {
                }
                if ($cardAmountForRefund > $amount) {
                    $cardAmountForRefund = $amount;
                }
                try {
                    $newTransaction = $this->_refundCardTransaction($payment, $cardAmountForRefund, $card);
                    if ($newTransaction != null) {
                        $messages[] = $newTransaction->getMessage();
                        $isSuccessful = true;
                    }
                } catch (\Exception $e) {
                    $messages[] = $e->getMessage();
                    $isFiled = true;
                }
                $card->setRefundedAmount($this->_formatAmount($card->getRefundedAmount() + $cardAmountForRefund));
                $cardsStorage->updateCard($card);
                $amount = $this->_formatAmount($amount - $cardAmountForRefund);
            } else {
                $payment->setSkipTransactionCreation(true);

                return $this;
            }
        }
        
        if ($isFiled) {
            $this->_processFailureAction($payment, $messages, $isSuccessful);
        }
        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * refund card detail.
     *
     * @param type $payment
     * @param type $amount
     * @param type $card
     *
     * @return type
     */
    protected function _refundCardTransaction($payment, $amount, $card)
    {
        $credit_memo = $this->_registry->registry('current_creditmemo');
        $captureTransactionId = $credit_memo->getInvoice()->getTransactionId();
        if ($payment->getCcTransId()) {
            $payment->setAnetTransType(self::REQUEST_TYPE_CREDIT);
            $payment->setXTransId($payment->getTransactionId());
            $payment->setAmount($amount);

            $response = $this->soapmodel
            ->prepareRefundResponse($payment, $amount, $payment->getTransactionId());

            if ($response->reasonCode == self::RESPONSE_CODE_SUCCESS) {
                $refundTransactionId = $response->requestID.'-refund';
                $shouldCloseCaptureTransaction = 0;

                if ($this->_formatAmount($card->getCapturedAmount() - $card->getRefundedAmount()) == $amount) {
                    $card->setLastTransId($refundTransactionId);
                    $shouldCloseCaptureTransaction = 1;
                }
                $this->_addTransaction(
                        $payment,
                        $refundTransactionId,
                        \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND,
                        array(
                            'is_transaction_closed' => 1,
                            'should_close_parent_transaction' => $shouldCloseCaptureTransaction,
                            'parent_transaction_id' => $captureTransactionId,
                        ),
                        array($this->_realTransactionIdKey => $response->requestID),
                        $this->cybsaHelper->getTransactionMessage(
                            $payment, self::REQUEST_TYPE_CREDIT, $response->requestID, $card, $amount
                        )
                    );
            } else {
                $code = $response->reasonCode;
                $errorMessage = $this->cybsaHelper->_errorMessage[$code];
                $exceptionMessage = $this->cybsaHelper->getTransactionMessage(
                    $payment, self::REQUEST_TYPE_CREDIT, $captureTransactionId, $card, $amount, $errorMessage
                );
                throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase($exceptionMessage));
            }
        } else {
            return;
        }
    }

    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $cardsStorage = $this->getCardsStorage($payment);
        $messages = array();
        $isSuccessful = false;
        $isFiled = false;
        $card = $cardsStorage->getCards();
        $lastTransactionId = $payment->getData('cc_trans_id');
        $cardTransactionId = $card->getTransactionId();
        if ($lastTransactionId == $cardTransactionId) {
            try {
                $newTransaction = $this->_voidCardTransaction($payment, $card);
                if ($newTransaction != null) {
                    $messages[] = $newTransaction->getMessage();
                    $isSuccessful = true;
                }
            } catch (\Exception $e) {
                $messages[] = $e->getMessage();
                $isFiled = true;
            }
            $cardsStorage->updateCard($card);
        }
        
        if ($isFiled) {
            $this->_processFailureAction($payment, $messages, $isSuccessful);
        }
        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    protected function _voidCardTransaction($payment, $card)
    {
        $authTransactionId = $card->getLastTransId();
        if ($payment->getCcTransId()) {
            $realAuthTransactionId = $payment->getTransactionId();
            $payment->setAnetTransType(self::REQUEST_TYPE_VOID);
            $payment->setTransId($realAuthTransactionId);
            $response = $this->soapmodel
                ->prepareVoidResponse($payment, $card);
            if ($response->reasonCode == self::RESPONSE_CODE_SUCCESS) {
                $voidTransactionId = $response->requestID.'-void';
                $card->setLastTransId($voidTransactionId);
                $payment->setTransactionId($response->requestID)
                    ->setCybersourceToken($response->requestToken)
                    ->setIsTransactionClosed(1);

                $this->_addTransaction(
                                $payment,
                                $voidTransactionId,
                                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID,
                                array(
                                    'is_transaction_closed' => 1,
                                    'should_close_parent_transaction' => 1,
                                    'parent_transaction_id' => $authTransactionId,
                                ),
                                array($this->_realTransactionIdKey => $response->requestID),
                                $this->cybsaHelper->getTransactionMessage(
                                    $payment, self::REQUEST_TYPE_VOID, $response->requestID, $card
                                )
                            );
            } else {
                $code = $response->reasonCode;
                $errorMessage = $this->cybsaHelper->_errorMessage[$code];
                $exceptionMessage = $this->cybsaHelper->getTransactionMessage(
                            $payment, self::REQUEST_TYPE_VOID, $realAuthTransactionId, $card, false, $errorMessage
                        );
                throw new \Magento\Framework\Exception\LocalizedException(new \Magento\Framework\Phrase($exceptionMessage));
            }
        } else {
            return;
        }
    }
}
