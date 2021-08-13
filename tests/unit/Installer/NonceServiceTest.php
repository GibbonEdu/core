<?php

namespace Gibbon\Tests\UnitTest\Installer;

use PHPUnit\Framework\TestCase;
use Gibbon\Install\NonceService;

class NonceServiceTest extends TestCase {

    private $token;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        $this->token = sha1(time());
    }

    /**
     * @test
     */
    public function testCreateVerify()
    {
        $nonceService = new NonceService($this->token);
        $nonce = $nonceService->create();
        $this->assertTrue($nonceService->verify($nonce), 'Can verify its own nonce.');

        // another nonceService with a slightly different token
        $nonceService = new NonceService($this->token . 'abcd');
        $this->assertFalse($nonceService->verify($nonce), 'Cannot verify nonce from another nonce service.');
    }
}

