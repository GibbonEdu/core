<?php

/**
 * Stripe Fetch Transaction Request.
 */

namespace Omnipay\Stripe\Message\Checkout;

/**
 * Stripe Fetch Transaction Request.
 * Example -- note this example assumes that the purchase has been successful
 * and that the transaction ID returned from the purchase is held in $sale_id.
 * See PurchaseRequest for the first part of this example transaction:
 * <code>
 *   // Fetch the transaction so that details can be found for refund, etc.
 *   $transaction = $gateway->fetchTransaction();
 *   $transaction->setTransactionReference($sale_id);
 *   $response = $transaction->send();
 *   $data = $response->getData();
 *   echo "Gateway fetchTransaction response data == " . print_r($data, true) . "\n";
 * </code>
 *
 * @see  PurchaseRequest
 * @see  Omnipay\Stripe\CheckoutGateway
 * @link https://stripe.com/docs/api/checkout/sessions/retrieve
 */
class FetchTransactionRequest extends AbstractRequest
{
    public function getData()
    {
        $this->validate('transactionReference');

        $data = [];

        return $data;
    }

    public function getEndpoint()
    {
        return $this->endpoint.'/checkout/sessions/'. $this->getTransactionReference();
    }

    public function getHttpMethod()
    {
        return 'GET';
    }
}
