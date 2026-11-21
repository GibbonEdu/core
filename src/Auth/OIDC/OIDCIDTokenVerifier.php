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
use Firebase\JWT\JWK;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;

/**
 * Validates the signed OpenID Connect ID Token before an account is resolved.
 */
class OIDCIDTokenVerifier
{
    protected const ALLOWED_ALGORITHMS = [
        'RS256',
        'RS384',
        'RS512',
        'ES256',
        'ES384',
        'ES512',
        'EdDSA',
    ];

    protected OIDCDiscoveryClient $discoveryClient;

    public function __construct(OIDCDiscoveryClient $discoveryClient)
    {
        $this->discoveryClient = $discoveryClient;
    }

    public function verify(string $idToken, array $settings, string $expectedNonce): array
    {
        $issuer = trim((string) ($settings['issuer'] ?? ''));
        $clientID = trim((string) ($settings['clientID'] ?? ''));
        $jwksUri = trim((string) ($settings['jwksUri'] ?? ''));

        if ($issuer === '' || $clientID === '') {
            throw new RuntimeException('OIDC issuer and client ID are required');
        }
        if ($jwksUri === '' || !OIDCDiscoveryClient::isSecureUrl($jwksUri)) {
            throw new RuntimeException('OIDC JWKS URI is missing or insecure');
        }
        if ($expectedNonce === '') {
            throw new RuntimeException('OIDC nonce is missing');
        }

        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new RuntimeException('OIDC ID token is malformed');
        }

        try {
            $header = json_decode(Base64UrlSafe::decodeNoPadding($parts[0]), true, 16, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new RuntimeException('OIDC ID token header is invalid');
        }

        $algorithm = is_array($header) ? (string) ($header['alg'] ?? '') : '';
        $keyID = is_array($header) ? (string) ($header['kid'] ?? '') : '';
        if (!in_array($algorithm, self::ALLOWED_ALGORITHMS, true) || $keyID === '') {
            throw new RuntimeException('OIDC ID token algorithm or key ID is invalid');
        }

        try {
            $keySet = JWK::parseKeySet($this->discoveryClient->fetchJson($jwksUri), $algorithm);
        } catch (\Throwable $e) {
            throw new RuntimeException('OIDC signing keys could not be loaded');
        }
        if (empty($keySet[$keyID])) {
            throw new RuntimeException('OIDC signing key was not found');
        }

        $previousLeeway = JWT::$leeway;
        JWT::$leeway = 60;
        try {
            $claims = (array) JWT::decode($idToken, $keySet[$keyID]);
        } catch (\Throwable $e) {
            throw new RuntimeException('OIDC ID token signature or lifetime is invalid');
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        if (!isset($claims['exp'], $claims['iat']) || !is_numeric($claims['exp']) || !is_numeric($claims['iat'])) {
            throw new RuntimeException('OIDC ID token lifetime claims are missing');
        }
        if (!is_string($claims['iss'] ?? null) || !hash_equals($issuer, $claims['iss'])) {
            throw new RuntimeException('OIDC ID token issuer does not match');
        }
        if (!is_string($claims['sub'] ?? null) || $claims['sub'] === '') {
            throw new RuntimeException('OIDC ID token subject is missing');
        }
        if (!is_string($claims['nonce'] ?? null) || !hash_equals($expectedNonce, $claims['nonce'])) {
            throw new RuntimeException('OIDC ID token nonce does not match');
        }
        $audience = $claims['aud'] ?? null;
        if (!$this->hasAudience($audience, $clientID)) {
            throw new RuntimeException('OIDC ID token audience does not match');
        }
        if (is_array($audience) && count($audience) > 1 && !isset($claims['azp'])) {
            throw new RuntimeException('OIDC ID token authorized party is missing');
        }
        if (isset($claims['azp']) && (!is_string($claims['azp']) || !hash_equals($clientID, $claims['azp']))) {
            throw new RuntimeException('OIDC ID token authorized party does not match');
        }

        return $claims;
    }

    protected function hasAudience($audience, string $clientID): bool
    {
        if (is_string($audience)) {
            return hash_equals($clientID, $audience);
        }
        if (!is_array($audience)) {
            return false;
        }

        foreach ($audience as $value) {
            if (is_string($value) && hash_equals($clientID, $value)) {
                return true;
            }
        }

        return false;
    }
}
