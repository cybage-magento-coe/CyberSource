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

namespace Cybage\Cybersource\Model\Sa;

class Cards
{
    const CARDS_NAMESPACE = 'cyb_cybersa_cards';
    const CARD_ID_KEY = 'id';
    const CARD_PROCESSED_AMOUNT_KEY = 'processed_amount';
    const CARD_CAPTURED_AMOUNT_KEY = 'captured_amount';
    const CARD_REFUNDED_AMOUNT_KEY = 'refunded_amount';

    /**
     * Cards information.
     *
     * @var mixed
     */
    protected $_cards = array();

    /**
     * Payment instance.
     */
    protected $_payment = null;

    /**
     * Set payment instance for storing credit card information and partial authorizations.
     */
    public function setPayment(\Magento\Sales\Model\Order\Payment $payment)
    {
       $this->_payment = $payment;
        $paymentCardsInformation = $this->_payment->getAdditionalInformation(self::CARDS_NAMESPACE);
        if ($paymentCardsInformation) {
            $this->_cards = $paymentCardsInformation;
        }
        else
        {
           $additionalInformations = $this->_payment->getAdditionalInformation();
            foreach ($additionalInformations as $key => $value) {
                $additionalInfo[$key] = $value;
            }
            $paymentCardsInformation = $additionalInfo;
            if ($paymentCardsInformation) {
                $this->_cards = $paymentCardsInformation;
            }
        }
        return $this;
    }

    public function initCard($cardInfo = array())
    {
        $this->_isPaymentValid();
        $this->_cards = $cardInfo;
        $this->_payment->setAdditionalInformation(self::CARDS_NAMESPACE, $this->_cards);
        return $this->getCard();
    }

    /**
     * Save data from card object in cards storage.
     */
    public function updateCard($card)
    {
        $this->_cards = $card->getData();
        $this->_payment->setAdditionalInformation(self::CARDS_NAMESPACE, $this->_cards);
        return $this;
    }

    /**
     * Retrieve card by ID.
     *
     * @param string $cardId
     *
     * @return Varien_Object|bool
     */
    public function getCard()
    {
        if (isset($this->_cards)) {
            $card = new \Magento\Framework\DataObject($this->_cards);
            return $card;
        }
        return false;
    }

    /**
     * Get all stored cards.
     *
     * @return array
     */
    public function getCards()
    {
        $this->_isPaymentValid();
        $_cards = array();
        foreach (array_keys($this->_cards) as $key) {
            $_cards[$key] = $this->getCard($key);
        }
        return $this->getCard();
    }

    /**
     * Return processed amount for all cards.
     *
     * @return float
     */
    public function getProcessedAmount()
    {
        return $this->_getAmount(self::CARD_PROCESSED_AMOUNT_KEY);
    }

    /**
     * Return captured amount for all cards.
     *
     * @return float
     */
    public function getCapturedAmount()
    {
        return $this->_getAmount(self::CARD_CAPTURED_AMOUNT_KEY);
    }

    /**
     * Return refunded amount for all cards.
     *
     * @return float
     */
    public function getRefundedAmount()
    {
        return $this->_getAmount(self::CARD_REFUNDED_AMOUNT_KEY);
    }

    /**
     * Remove all cards from payment instance.
     */
    public function flushCards()
    {
        $this->_cards = array();
        $this->_payment->setAdditionalInformation(self::CARDS_NAMESPACE, null);

        return $this;
    }

    /**
     * Check for payment instace present.
     *
     * @throws Exception
     */
    protected function _isPaymentValid()
    {
        if (!$this->_payment) {
            throw new \Exception('Payment instance is not set');
        }
    }
    /**
     * Return total for cards data fields.
     *
     * $param string $key
     *
     * @return float
     */
    public function _getAmount($key)
    {
        $amount = 0;
        if (isset($this->_cards[$key])) {
                $amount += $this->_cards[$key];
            }
        return $amount;
    }
}
