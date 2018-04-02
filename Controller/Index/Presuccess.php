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

class Presuccess extends \Cybage\Cybersource\Controller\Index\Cybersourcesa {

    public function execute() {
        $request = $this->getRequest();
        $params = $request->getParams();
        $orderId = isset($params['req_reference_number']) ? $params['req_reference_number'] : null;
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
        $payment = $order->getPayment();
        $validateResponse = $this->_validateResponse($params);
        if ($validateResponse) {
            $paymentAction = $params['req_transaction_type'];
            $cardType = array_flip($this->_cardCode);
            $cardExpData = explode('-', $params['req_card_expiry_date']);
            $cardExpMonth = $cardExpData[0];
            $cardExpYear = $cardExpData[1];
            $cardLastFour = substr($params['req_card_number'], -4);
            $payment->setCcType($cardType[$params['req_card_type']])
                    ->setCcLast4($cardLastFour)
                    ->setCcExpMonth($cardExpMonth)
                    ->setCcOwner($params['req_bill_to_forename'])
                    ->setCcExpYear($cardExpYear);
            $authorize = true;
            if (strpos($paymentAction, 'authorization') !== false) {
                $csToRequestMap = self::REQUEST_TYPE_AUTH_ONLY;
                $newTransactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
            } else {
                $csToRequestMap = self::REQUEST_TYPE_AUTH_CAPTURE;
                $newTransactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                $payment->setIsTransactionClosed(0);
                $authorize = false;
            }

            if (strpos($paymentAction, 'create_payment_token') !== false) {
                try {
                    $card_type = $cardType[$params['req_card_type']];
                    $cardExpData = explode('-', $params['req_card_expiry_date']);
                    $cardExpMonth = $cardExpData[0];
                    $cardExpYear = $cardExpData[1];
                    $cardLastFour = substr($params['req_card_number'], -4);
                    $customerId = $params['req_consumer_id'];
                    if (empty($customerId)) {
                        $customerId = $order->getCustomerId();
                    }
                    $country = $params['req_bill_to_address_country'];
                    $regionName = null;
                    if (isset($params['req_bill_to_address_state'])) {
                        $regionName = $params['req_bill_to_address_state'];
                        if ($country == 'US' || $country == 'CA') {
                            $region = $this->regionFactory->create()->loadByCode($regionName, $country);
                            $regionName = $region->getId();
                        }
                    }
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $this->messageManager->addError($message);
                }
            }
            $payment->setAnetTransType($csToRequestMap);
            $payment->setAmount($params['auth_amount']);

            $card = $this->_initCard($params, $payment, $authorize);

            $this->_addTransaction(
                    $payment, $params['transaction_id'], $newTransactionType, array('is_transaction_closed' => 0), array($this->_realTransactionIdKey => $params['transaction_id']), $this->_cybsaHelper->getTransactionMessage(
                            $payment, $csToRequestMap, $params['transaction_id'], $card, $params['auth_amount']
                    )
            );
            $card->setLastTransId($params['transaction_id']);
            $payment->setLastTransId($params['transaction_id'])
                    ->setCcTransId($params['transaction_id'])
                    ->setTransactionId($params['transaction_id'])
                    ->setCybersourceToken($params['request_token'])
                    ->setIsTransactionClosed(0)
                    ->setStatus(self::STATUS_APPROVED)
                    ->setCcAvsStatus(isset($params['auth_avs_code']) ? $params['auth_avs_code'] : null);
            if (isset($params['auth_cv_result'])) {
                $payment->setCcCidStatus($params['auth_cv_result']);
            }
            $payment->setSkipTransactionCreation(true);
            $payment->save();
        }
        if ($validateResponse) {
            if (strpos($paymentAction, 'sale') !== false) {
                if ($order) {
                    $invoices = $order->getInvoiceCollection();
                    foreach ($invoices as $invoice) {
                        $invoice->pay();
                    }
                    $this->transaction
                            ->addObject($invoice)
                            ->addObject($invoice->getOrder())
                            ->save();
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                }
            }
            $order->getPayment()->setLastTransId($params['transaction_id']);
            $this->orderSender->send($order);
            $order->setEmailSent(true);
            $order->save();
            $this->_redirect('cybersource/index/success');
        }
        if (!$validateResponse) {
            if (isset($params['reason_code'])) {
                if (isset($params['decision']) && $params['decision'] == 'REVIEW') {
                    $this->messageManager->addError('Your current order is in under review');
                    $this->checkoutsession->setCybreview(true);
                    $this->_redirect('cybersource/process/success');
                } else {
                    $errorMessage = 'Unable to complete your transaction';
                    $errorCode = $params['reason_code'];
                    if (isset($errorCode) && !in_array($errorCode, array(100, 110))) {
                        $invalidfields = '';
                        if (isset($params['invalid_fields'])) {
                            $invalidfields = $params['invalid_fields'];
                        }

                        $errorMessage = $this->_cybsaHelper->_errorMessage[$errorCode];
                        if (isset($errorMessage) && !empty($errorMessage)) {
                            $this->messageManager->addError('Error code: ' . $errorCode . ' : ' . $errorMessage . ' ' . $invalidfields);
                        } else {
                            $this->messageManager->addError('Error code: ' . $errorCode);
                        }
                        $this->_redirect('cybersource/index/failed');

                        return;
                    }
                }
                $this->_redirect('cybersource/index/success');
            }
        }
    }

    /**
     * 
     * @param type $params
     * @return boolean
     */
    protected function _validateResponse($params) {
        $orderId = isset($params['req_reference_number']) ? $params['req_reference_number'] : null;
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
        if (!$order) {
            return false;
        }
        $errors = array();
        if (isset($params['decision']) && $params['decision'] != 'ACCEPT') {
            $errors[] = 'decision is not ACCEPT';
        }
        if (isset($params['reason_code']) && !in_array($params['reason_code'], array(100, 110))) {
            $errors[] = 'reason_code is not 100, 110';
        }

        if (count($errors) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $params
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param type $authorize
     * @return card
     */
    protected function _initCard($params, \Magento\Sales\Model\Order\Payment $payment, $authorize) {
        $cardsStorage = $this->cybrcaPaymentModel->getCardsStorage($payment);
        $card = $cardsStorage->initCard();

        $cardType = array_flip($this->_cardCode);
        $cardExpData = explode('-', $params['req_card_expiry_date']);
        $cardExpMonth = $cardExpData[0];
        $cardExpYear = $cardExpData[1];
        $cardLastFour = substr($params['req_card_number'], -4);
        $card->setCcType($cardType[$params['req_card_type']])
            ->setCcLast4($cardLastFour)
            ->setCcExpMonth($cardExpMonth)
            ->setCcOwner($params['req_bill_to_forename'])
            ->setCcExpYear($cardExpYear);
        $card->setRequestedAmount($params['auth_amount'])
            ->setLastTransId($params['transaction_id'])
            ->setProcessedAmount($params['auth_amount'])
            ->setMerchantReferenceCode($params['req_reference_number'])
            ->setreconciliationID(isset($params['bill_trans_ref_no']) ? $params['bill_trans_ref_no'] : null)
            ->setauthorizationCode(isset($params['auth_code']) ? $params['auth_code'] : null)
            ->setAvsResultCode(isset($params['auth_avs_code']) ? $params['auth_avs_code'] : null)
            ->setCVNResultCode(isset($params['auth_cv_result']) ? $params['auth_cv_result'] : null)
            ->setTransactionId($params['transaction_id']);
        if ($authorize == false) {
            $card->setCapturedAmount($params['auth_amount']);
            $card->setLastCapturedTransactionId($params['transaction_id']);
        }
        $cardsStorage->updateCard($card);
        return $card;
    }

    protected function _addTransaction(
    \Magento\Sales\Model\Order\Payment $payment, $transactionId, $transactionType, array $transactionDetails = array(), array $transactionAdditionalInfo = array(), $message = false
    ) {
        $payment->resetTransactionAdditionalInfo();
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

}
