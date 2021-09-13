<?php

namespace Omnipay\Stripe;

use Omnipay\Tests\GatewayTestCase;

/**
 * @property \Omnipay\Stripe\CheckoutGateway gateway
 */
class CheckoutGatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new CheckoutGateway($this->getHttpClient(), $this->getHttpRequest());
    }

    public function testPurchase()
    {
        $request = $this->gateway->purchase(['mode' => 'payment']);

        $this->assertInstanceOf('Omnipay\Stripe\Message\Checkout\PurchaseRequest', $request);
        $this->assertSame('payment', $request->getMode());
    }

    public function testFetchTransaction()
    {
        $request = $this->gateway->fetchTransaction(['transactionReference' => 'transaction-reference']);

        $this->assertInstanceOf('Omnipay\Stripe\Message\Checkout\FetchTransactionRequest', $request);
        $this->assertSame('transaction-reference', $request->getTransactionReference());
    }
}
