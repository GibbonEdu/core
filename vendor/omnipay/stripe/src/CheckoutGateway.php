<?php

/**
 * Stripe Payment Intents Gateway.
 */

namespace Omnipay\Stripe;

/**
 * Stripe Payment Intents Gateway.
 *
 * @see  \Omnipay\Stripe\AbstractGateway
 * @see  \Omnipay\Stripe\Message\AbstractRequest
 * @link https://stripe.com/docs/api
 * @method \Omnipay\Common\Message\NotificationInterface acceptNotification(array $options = array())
 * @method \Omnipay\Common\Message\RequestInterface refund(array $options = array())
 * @method \Omnipay\Common\Message\RequestInterface void(array $options = array())
 */
class CheckoutGateway extends AbstractGateway
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Stripe Checkout';
    }

    /**
     * @inheritdoc
     * @return \Omnipay\Stripe\Message\Checkout\PurchaseRequest
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Stripe\Message\Checkout\PurchaseRequest', $parameters);
    }

    /**
     * @inheritdoc
     * @return \Omnipay\Stripe\Message\Checkout\PurchaseRequest
     */
    public function fetchTransaction(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Stripe\Message\Checkout\FetchTransactionRequest', $parameters);
    }

    /**
     * @inheritdoc
     *
     * @return \Omnipay\Stripe\Message\AuthorizeRequest
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Stripe\Message\AuthorizeRequest', $parameters);
    }

    /**
     * @inheritdoc
     *
     * @return \Omnipay\Stripe\Message\CaptureRequest
     */
    public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Stripe\Message\CaptureRequest', $parameters);
    }
}
