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

class SoapClient extends \SoapClient
{
    private $merchantId;

    private $transactionKey;


    public function __construct(
        $cybhelper
    ) {
        $options = array();
        $propertiesWsdl = $cybhelper->getGatewayUrl();
        parent::__construct($propertiesWsdl, $options);
        $this->merchantId = $cybhelper->getMerchantId();
        $this->transactionKey = ''.$cybhelper->getTransKey().'';
        $nameSpace = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

        $soapUsername = new \SoapVar(
            $this->merchantId,
            XSD_STRING,
            null,
            $nameSpace,
            null,
            $nameSpace
        );
        $soapPassword = new \SoapVar(
            $this->transactionKey,
            XSD_STRING,
            null,
            $nameSpace,
            null,
            $nameSpace
        );
        $auth = new \stdClass();
        $auth->Username = $soapUsername;
        $auth->Password = $soapPassword;
        $soapAuth = new \SoapVar(
            $auth,
            SOAP_ENC_OBJECT,
            null, $nameSpace,
            'UsernameToken',
            $nameSpace
        );
        $token = new \stdClass();
        $token->UsernameToken = $soapAuth;
        $soapToken = new \SoapVar(
            $token,
            SOAP_ENC_OBJECT,
            null,
            $nameSpace,
            'UsernameToken',
            $nameSpace
        );
        $security = new \SoapVar(
            $soapToken,
            SOAP_ENC_OBJECT,
            null,
            $nameSpace,
            'Security',
            $nameSpace
        );

        $header = new \SoapHeader($nameSpace, 'Security', $security, true);

        $this->__setSoapHeaders(array($header));
    }

}
