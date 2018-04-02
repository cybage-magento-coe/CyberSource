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

class Cybersourcetoken extends AbstractHelper {

    /**
     * Cybersource token config path
     */
    const XML_PATH_TOKEN_ACTIVE = 'payment/cybersourcetoken/active';
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(Context $context, ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function isActive($code) {
        return $this->scopeConfig->getValue(self::XML_PATH_TOKEN_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getRedirectLabel($code) {
        return $this->scopeConfig->getValue(self::XML_PATH_TOKEN_RDIRECT_LABEL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}
