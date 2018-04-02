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

define(
        [
            'uiComponent',
            'Magento_Checkout/js/model/payment/renderer-list'
        ],
        function (
                Component,
                rendererList
                ) {
            'use strict';

            var config = window.checkoutConfig.payment,
                cybSaType = 'cybersourcesa',
                cybTokenType = 'cybersourcetoken';

            if (config[cybSaType].isActive) {
                rendererList.push(
                        {
                            type: cybSaType,
                            component: 'Cybage_Cybersource/js/view/payment/method-renderer/cybersourcesa'
                        }
                );
            }

            if (config[cybTokenType].isActive) {
                rendererList.push(
                        {
                            type: cybTokenType,
                            component: 'Cybage_Cybersource/js/view/payment/method-renderer/cybersourcetoken'
                        }
                );
            }
            return Component.extend({});
        }
);