<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Auth\OIDC;

use Firebase\JWT\JWT;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\TestCase;

spl_autoload_register(function ($class) {
    $prefix = 'Gibbon\\Auth\\OIDC\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $file = dirname(__DIR__, 3).'/src/Auth/OIDC/'.substr($class, strlen($prefix)).'.php';
    if (is_file($file)) {
        require_once $file;
    }
}, true, true);

/**
 * @covers \Gibbon\Auth\OIDC\OIDCIDTokenVerifier
 */
class OIDCIDTokenVerifierTest extends TestCase
{
    private $privateKey;
    private array $jwks;
    private array $settings;

    public function setUp(): void
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($key);
        $this->assertTrue(openssl_pkey_export($key, $this->privateKey));

        $details = openssl_pkey_get_details($key);
        $this->jwks = [
            'keys' => [[
                'kty' => 'RSA',
                'kid' => 'test-key',
                'alg' => 'RS256',
                'use' => 'sig',
                'n'   => Base64UrlSafe::encodeUnpadded($details['rsa']['n']),
                'e'   => Base64UrlSafe::encodeUnpadded($details['rsa']['e']),
            ]],
        ];
        $this->settings = [
            'issuer'   => 'https://issuer.example.com',
            'clientID' => 'gibbon-client',
            'jwksUri'  => 'https://issuer.example.com/jwks',
        ];
    }

    public function testAcceptsValidSignedIdToken()
    {
        $token = $this->createToken();

        $claims = $this->createVerifier()->verify($token, $this->settings, 'expected-nonce');

        $this->assertSame('person-123', $claims['sub']);
        $this->assertSame('verified@example.com', $claims['email']);
    }

    public function testRejectsNonceMismatch()
    {
        $this->expectException(\RuntimeException::class);
        $this->createVerifier()->verify($this->createToken(), $this->settings, 'other-nonce');
    }

    public function testRejectsWrongIssuer()
    {
        $this->expectException(\RuntimeException::class);
        $this->createVerifier()->verify(
            $this->createToken(['iss' => 'https://attacker.example.com']),
            $this->settings,
            'expected-nonce'
        );
    }

    public function testRejectsMultipleAudiencesWithoutAuthorizedParty()
    {
        $this->expectException(\RuntimeException::class);
        $this->createVerifier()->verify(
            $this->createToken(['aud' => ['gibbon-client', 'another-client']]),
            $this->settings,
            'expected-nonce'
        );
    }

    public function testRejectsUnsignedAlgorithm()
    {
        $header = Base64UrlSafe::encodeUnpadded(json_encode(['alg' => 'none', 'kid' => 'test-key']));
        $claims = Base64UrlSafe::encodeUnpadded(json_encode($this->claims()));

        $this->expectException(\RuntimeException::class);
        $this->createVerifier()->verify($header.'.'.$claims.'.', $this->settings, 'expected-nonce');
    }

    private function createVerifier(): OIDCIDTokenVerifier
    {
        $discoveryClient = new class($this->jwks) extends OIDCDiscoveryClient {
            private array $jwks;

            public function __construct(array $jwks)
            {
                $this->jwks = $jwks;
            }

            public function fetchJson(string $url): array
            {
                return $this->jwks;
            }
        };

        return new OIDCIDTokenVerifier($discoveryClient);
    }

    private function createToken(array $overrides = []): string
    {
        return JWT::encode(array_merge($this->claims(), $overrides), $this->privateKey, 'RS256', 'test-key');
    }

    private function claims(): array
    {
        return [
            'iss'            => 'https://issuer.example.com',
            'aud'            => 'gibbon-client',
            'sub'            => 'person-123',
            'nonce'          => 'expected-nonce',
            'iat'            => time(),
            'exp'            => time() + 300,
            'email'          => 'verified@example.com',
            'email_verified' => true,
        ];
    }
}
