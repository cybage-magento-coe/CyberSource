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

namespace Cybage\Cybersource\Model\Token;

use \Cybage\Cybersource\Helper\Data;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface {

    const CODE = 'cybersourcetoken';

    /**
     * @var Config
     */
    private $cyberHelper;

    /**
     * Constructor
     *
     * @param Config $helper
     */
    public function __construct(
    Data $helper
    ) {
        $this->cyberHelper = $helper;
    }

    /**
     * Get configuration variables for checkout page related to cybersource Token module
     * @return type
     */
    public function getConfig() {

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->cyberHelper->isActive(self::CODE)
                ],
            ]
        ];
    }

}
