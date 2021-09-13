<?php

/**
 * Stripe Checkout Session Request.
 */

namespace Omnipay\Stripe\Message\Checkout;

/**
 * Stripe Checkout Session Request
 *
 * @see \Omnipay\Stripe\Gateway
 * @link https://stripe.com/docs/api/checkout/sessions
 */
class PurchaseRequest extends AbstractRequest
{
    /**
     * Set the success url
     *
     * @param string $value
     *
     * @return \Omnipay\Common\Message\AbstractRequest|PurchaseRequest
     */
    public function setSuccessUrl($value)
    {
        return $this->setParameter('success_url', $value);
    }

    /**
     * Get the success url
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->getParameter('success_url');
    }
    /**
     * Set the cancel url
     *
     * @param string $value
     *
     * @return \Omnipay\Common\Message\AbstractRequest|PurchaseRequest
     */
    public function setCancelUrl($value)
    {
        return $this->setParameter('cancel_url', $value);
    }

    /**
     * Get the success url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getParameter('cancel_url');
    }

    /**
     * Set the payment method types accepted url
     *
     * @param array $value
     *
     * @return \Omnipay\Common\Message\AbstractRequest|PurchaseRequest
     */
    public function setPaymentMethodTypes($value)
    {
        return $this->setParameter('payment_method_types', $value);
    }

    /**
     * Get the success url
     *
     * @return string
     */
    public function getPaymentMethodTypes()
    {
        return $this->getParameter('payment_method_types');
    }

    /**
     * Set the payment method types accepted url
     *
     * @param string $value
     *
     * @return \Omnipay\Common\Message\AbstractRequest|PurchaseRequest
     */
    public function setMode($value)
    {
        return $this->setParameter('mode', $value);
    }

    /**
     * Get the success url
     *
     * @return string
     */
    public function getMode()
    {
        return $this->getParameter('mode');
    }

    /**
     * Set the payment method types accepted url
     *
     * @param array $value
     *
     * @return \Omnipay\Common\Message\AbstractRequest|PurchaseRequest
     */
    public function setLineItems($value)
    {
        return $this->setParameter('line_items', $value);
    }

    /**
     * Get the success url
     *
     * @return array
     */
    public function getLineItems()
    {
        return $this->getParameter('line_items');
    }

    /**
     * Set the payment method types accepted url
     *
     * @param string $value
     *
     * @return \Omnipay\Common\Message\AbstractRequest|PurchaseRequest
     */
    public function setClientReferenceId($value)
    {
        return $this->setParameter('client_reference_id', $value);
    }

    /**
     * Get the success url
     *
     * @return string
     */
    public function getClientReferenceId()
    {
        return $this->getParameter('client_reference_id');
    }


    public function getData()
    {
        $data = array(
            'success_url' => $this->getSuccessUrl(),
            'cancel_url' => $this->getCancelUrl(),
            'payment_method_types' => $this->getPaymentMethodTypes(),
            'mode' => $this->getMode(),
            'line_items' => $this->getLineItems()
        );

        return $data;
    }

    public function getEndpoint()
    {
        return $this->endpoint.'/checkout/sessions';
    }
}
