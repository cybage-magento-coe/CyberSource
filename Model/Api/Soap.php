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

namespace Cybage\Cybersource\Model\Api;

class Soap
{
    protected $_orderRequest = array();

    protected $_request = null;

    /**
     * @var Magento\Customer\Model\Session
     */
    protected $_customer;

    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Magento\Framework\Math\Random
     */
    protected $_random;

    /**
     * @var Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remotaddress;

    /**
     * @var Magento\Framework\App\RequestInterface
     */
    protected $_requestHttp;

    /**
     * @var Magento\Framework\Registry
     */
    protected $regitstry;

    /**
     * @var Magento\Checkout\Model\Session
     */
    protected $checkoutsession;
    /**
     *
     * @var Cybage\Cybersource\Helper\Data 
     */
    protected $_cybhelper;
    /**
     * @param \Magento\Customer\Model\Session                      $customer
     * @param \Magento\Store\Model\StoreManagerInterface           $storeManager
     * @param \Magento\Framework\Math\Random                       $random
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remotaddress
     * @param \Magento\Framework\App\RequestInterface              $requestHttp
     
     * @param array                                                $data
     */
    public function __construct(
        \Magento\Customer\Model\Session $customer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Math\Random $random,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remotaddress,
        \Cybage\Cybersource\Helper\Data $cybhelper,
        \Magento\Framework\App\RequestInterface $requestHttp,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutsession
    ) {
        $this->_customer = $customer;
        $this->_storeManager = $storeManager;
        $this->_random = $random;
        $this->remotaddress = $remotaddress;
        $this->_requestHttp = $requestHttp;
        $this->_cybhelper = $cybhelper;
        $this->registry = $registry;
        $this->checkoutsession = $checkoutsession;
        $this->_merchantId = $this->_cybhelper->getMerchantId();
        $this->_transKey = $this->_cybhelper->getTransKey();
        $this->_apiGatewayUrl = $this->_cybhelper->getGatewayUrl();
    }

    public function getCustomer()
    {
        return $this->_customer;
    }


    /**
     * prepare capture request for pre-authorize.
     *
     * @param Varien_Object $payment
     * @param type          $amount
     */
    public function prepareAuthorizeCaptureResponse(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_request = $this->prepareAuthorizeCaptureRequest($payment, $amount);
        $response = $this->_postRequest($this->_request);
        return $response;
    }

    /**
     * prepare capture request for pre-authorize.
     *
     * @param Varien_Object $payment
     * @param type          $amount
     */
    public function prepareAuthorizeCaptureRequest(\Magento\Payment\Model\InfoInterface  $payment, $amount)
    {
        $this->_request = new \stdClass();
        $billingAddress = $payment->getOrder()->getBillingAddress();
        $shippingAddress = $payment->getOrder()->getShippingAddress();
        $this->_request->merchantID = $this->_merchantId;
        $this->_request->merchantReferenceCode = $this->_generateMerchantReferenceCode();
        $csCaptureService = new \stdClass();
        $csCaptureService->run = 'true';
        $csCaptureService->authRequestToken = $payment->getCybersourceToken();
        $csCaptureService->authRequestID = $payment->getTransactionId();
        $this->_request->ccCaptureService = $csCaptureService;
        $item0 = new \stdClass();
        $item0->unitPrice = $amount;
        $item0->id = 0;
        $this->_request->item = array($item0);
        $customeremail = $payment->getOrder()->getCustomerEmail();
        $this->createBillingAddressRequest($customeremail, $billingAddress);
        $this->createShippingAddressRequest($shippingAddress);
        $this->createItemInfoRequest($payment);
        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $payment->getOrder()->getBaseCurrencyCode();
        $purchaseTotals->grandTotalAmount = $amount;
        if ($payment->getBaseShippingAmount()) {
            $purchaseTotals->additionalAmount0 = (string) round($payment->getBaseShippingAmount(), 4);
            $purchaseTotals->additionalAmountType0 = (string) '055';
        }

        $this->_request->purchaseTotals = $purchaseTotals;
        return $this->_request;
    }

    /**
     * prepare credit memo request.
     *
     * @param Varien_Object $payment
     * @param type          $amount
     * @param type          $realCaptureTransactionId
     */
    public function prepareRefundResponse(\Magento\Payment\Model\InfoInterface $payment, $amount, $realCaptureTransactionId)
    {
        $this->_request = $this->prepareRefundRequest($payment, $amount, $realCaptureTransactionId);
        $response = $this->_postRequest($this->_request);

        return $response;
    }

    /**
     * prepare credit memo request.
     *
     * @param Varien_Object $payment
     * @param type          $amount
     * @param type          $realCaptureTransactionId
     */
    protected function prepareRefundRequest(\Magento\Payment\Model\InfoInterface $payment, $amount, $realCaptureTransactionId)
    {
        $billingAddress = $payment->getOrder()->getBillingAddress();
        $shippingAddress = $payment->getOrder()->getShippingAddress();
        if (empty($realCaptureTransactionId)) {
            $credit_memo = $this->registry->registry('current_creditmemo');
            $captureTransactionId = $credit_memo->getInvoice()->getTransactionId();
            $captureTransaction = $payment->getTransaction($captureTransactionId);
            $realCaptureTransactionId = $captureTransaction->getAdditionalInformation('real_transaction_id');
        }
        $this->_request = new \stdClass();
        $this->_request->merchantID = $this->_merchantId;
        $this->_request->merchantReferenceCode = $this->_generateMerchantReferenceCode();
        $ccCreditService = new \stdClass();
        $ccCreditService->run = (string) 'true';
        $ccCreditService->captureRequestID = (string) $realCaptureTransactionId;
        $ccCreditService->captureRequestToken = (string) $payment->getCybersourceToken();
        $this->_request->ccCreditService = $ccCreditService;
        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $payment->getOrder()->getBaseCurrencyCode();
        $purchaseTotals->grandTotalAmount = $amount;
        $this->_request->purchaseTotals = $purchaseTotals;

        $customeremail = $payment->getOrder()->getCustomerEmail();

        $this->createBillingAddressRequest($customeremail, $billingAddress);

        $this->createShippingAddressRequest($shippingAddress);

        $this->createItemInfoRequest($payment);

        return $this->_request;
    }

    /**
     * prepare billing field for API.
     *
     * @param type $customeremail
     * @param type $billing
     */
    protected function createBillingAddressRequest($customeremail, $billing)
    {
        $customerId = false;
        if (!$customeremail) {
            $customeremail = $this->checkoutsession->getQuote()->getBillingAddress()->getEmail();
        }
        if ($this->_customer->isLoggedIn()) {
            $customerData = $this->_customer->getCustomer();
            $customerId = $customerData->getId();
        }
        $billTo = new \stdClass();
        $billTo->firstName = $billing->getFirstname();
        $billTo->lastName = $billing->getLastname();
        $billTo->company = $billing->getCompany();
        $billTo->street1 = (is_array($billing->getStreet(1))) ? implode(',', $billing->getStreet(1)) : $billing->getStreet(1);
        $billTo->street2 = (is_array($billing->getStreet(2))) ? implode(',', $billing->getStreet(2)) : $billing->getStreet(2);
        $billTo->city = $billing->getCity();
        $billTo->state = $billing->getRegion();
        $billTo->postalCode = $billing->getPostcode();
        $billTo->country = $billing->getCountry();
        $billTo->phoneNumber = $billing->getTelephone();
        $billTo->email = $customeremail;
        $billTo->ipAddress = $this->getIpAddress();
        if ($customerId) {
            $billTo->customerID = $customerId;
        }
        $this->_request->billTo = $billTo;
    }

    /**
     * * prepare shipping field for API.
     *
     * @param type $shipping
     */
    protected function createShippingAddressRequest($shipping)
    {
        if ($shipping) {
            $shipTo = new \stdClass();
            $shipTo->firstName = $shipping->getFirstname();
            $shipTo->lastName = $shipping->getLastname();
            $shipTo->company = $shipping->getCompany();
            $shipTo->street1 = (is_array($shipping->getStreet(1))) ? implode(',', $shipping->getStreet(1)) : $shipping->getStreet(1);
            $shipTo->street2 = (is_array($shipping->getStreet(2))) ? implode(',', $shipping->getStreet(2)) : $shipping->getStreet(2);
            $shipTo->city = $shipping->getCity();
            $shipTo->state = $shipping->getRegion();
            $shipTo->postalCode = $shipping->getPostcode();
            $shipTo->country = $shipping->getCountry();
            $shipTo->phoneNumber = $shipping->getTelephone();
            $this->_request->shipTo = $shipTo;
        }
    }

    public function prepareVoidResponse(\Magento\Payment\Model\InfoInterface $payment, $card)
    {
        $this->_request = new \stdClass();
        $billingAddress = $payment->getOrder()->getBillingAddress();
        $this->_request->merchantID = $this->_merchantId;
        $this->_request->merchantReferenceCode = $this->_generateMerchantReferenceCode();
        $ccAuthReversalService = new \stdClass();
        $ccAuthReversalService->run = 'true';
        $ccAuthReversalService->authRequestID = (string) $payment->getParentTransactionId();
        $ccAuthReversalService->authRequestToken = (string) $payment->getCybersourceToken();
        $this->_request->ccAuthReversalService = $ccAuthReversalService;
        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $payment->getOrder()->getBaseCurrencyCode();
        $purchaseTotals->grandTotalAmount = $payment->getBaseAmountAuthorized();
        $this->_request->purchaseTotals = $purchaseTotals;
        $customeremail = $payment->getOrder()->getCustomerEmail();
        $this->createBillingAddressRequest($customeremail, $billingAddress);
        $response = $this->_postRequest($this->_request);

        return $response;
    }
    /**
     * prepare item request for API.
     *
     * @param Varien_Object $payment
     * @param type          $quantity
     */
    protected function createItemInfoRequest(\Magento\Payment\Model\InfoInterface $payment, $quantity = false)
    {
        if (is_object($payment)) {
            $order = $payment->getOrder();
            if ($order instanceof \Magento\Sales\Model\Order) {
                $i = 0;
                foreach ($order->getAllVisibleItems() as $_item) {
                    $item = new \stdClass();
                    $item->unitPrice = round($_item->getBasePrice(), 2);
                    $item->taxAmount = round($_item->getData('tax_amount'), 2);
                    $quantity == false ? $item->quantity = (int) $_item->getQtyOrdered() : '';
                    $item->productName = substr($_item->getName(), 0, 30);
                    $item->productSKU = $_item->getSku();
                    $item->id = $i;
                    $this->_request->item[$i] = $item;
                    ++$i;
                }
            }
        }
    }
    protected function _generateMerchantReferenceCode()
    {
        return $this->_random->getUniqueHash();
    }

    protected function _getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }

    protected function _getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    public function _postRequest($request, $type = null)
    {
        $client = new \Cybage\Cybersource\Model\Api\SoapClient($this->_cybhelper);
        try {
            $response = $client->runTransaction($request);
        } catch (\SoapFault $sf) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Soap request error due to invalid configuration.'), $sf);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new \Magento\Framework\Exception\LocalizedException(__($message), $e);
        }

        return $response;
    }

    /**
     * @return type
     */
    protected function getIpAddress()
    {
        return $this->remotaddress->getRemoteAddress();
    }
}
