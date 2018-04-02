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
            'jquery',
            'ko',
            'Magento_Checkout/js/view/payment/default',
            'Magento_Checkout/js/action/place-order',
            'Magento_Checkout/js/action/select-payment-method',
            'Magento_Customer/js/model/customer',
            'Magento_Checkout/js/checkout-data',
            'Magento_Checkout/js/model/payment/additional-validators',
            'mage/url',
        ],
        function (
                $,
                ko,
                Component,
                placeOrderAction,
                selectPaymentMethodAction,
                customer,
                checkoutData,
                additionalValidators,
                url) {
            'use strict';

            return Component.extend({
                defaults: {
                    template: 'Cybage_Cybersource/payment/cybersourcesa'
                },
                newCardVisible: ko.observable(false),
                newCardVisibleSave: ko.observable(false),
                savechecked: ko.observable(true),
                noSavedCardAvail: ko.observable(false),
                placeOrder2: function (data, event) {
                    if (event) {
                        event.preventDefault();
                    }
                    var self = this,
                            placeOrder,
                            emailValidationResult = customer.isLoggedIn(),
                            loginFormSelector = 'form[data-role=email-with-possible-login]';
                    if (!customer.isLoggedIn()) {
                        $(loginFormSelector).validation();
                        emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                    }
                    if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                        this.isPlaceOrderActionAllowed(false);
                        placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                        $.when(placeOrder).fail(function () {
                            self.isPlaceOrderActionAllowed(true);
                        }).done(this.afterPlaceOrder.bind(this));
                        return true;
                    }
                    return false;
                },
                afterPlaceOrder: function () {
                    window.location.replace(url.build('cybersource/index/redirect/'));
                }
            });
        }
);