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

use RuntimeException;

/**
 * Fetches and normalises OpenID Connect discovery metadata.
 *
 * The client accepts either an issuer base URL or an explicit discovery URL,
 * so it works with providers that use the standard well-known path as well as
 * providers that expose a custom discovery location.
 */
class OIDCDiscoveryClient
{
    public function discover(string $issuer, string $discoveryUrl = ''): array
    {
        $issuer = rtrim(trim($issuer), '/');
        $discoveryUrl = trim($discoveryUrl);

        if ($discoveryUrl === '') {
            if ($issuer === '') {
                throw new RuntimeException('OIDC issuer or discovery URL is required');
            }

            $discoveryUrl = $issuer;
        }

        $discoveryUrl = rtrim($discoveryUrl, '/');
        if (!preg_match('#/\.well-known/openid-configuration$#i', $discoveryUrl)) {
            $path = (string) parse_url($discoveryUrl, PHP_URL_PATH);
            // Treat a URL without a file-like path as a base URL and append the
            // standard well-known path. Full/custom discovery URLs are kept as-is.
            if (!preg_match('/\.[a-z0-9]+$/i', $path)) {
                $discoveryUrl .= '/.well-known/openid-configuration';
            }
        }

        if (!filter_var($discoveryUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $discoveryUrl)) {
            throw new RuntimeException('Invalid OIDC discovery URL');
        }

        $metadata = $this->fetchJson($discoveryUrl);

        foreach (['authorization_endpoint', 'token_endpoint', 'userinfo_endpoint'] as $endpoint) {
            if (empty($metadata[$endpoint]) || !filter_var($metadata[$endpoint], FILTER_VALIDATE_URL)) {
                throw new RuntimeException('OIDC discovery document is missing '.$endpoint);
            }
        }

        $metadata['issuer'] = !empty($metadata['issuer']) ? rtrim((string) $metadata['issuer'], '/') : $issuer;
        $metadata['jwks_uri'] = !empty($metadata['jwks_uri']) ? (string) $metadata['jwks_uri'] : '';
        $metadata['scopes_supported'] = !empty($metadata['scopes_supported']) && is_array($metadata['scopes_supported'])
            ? array_values(array_filter($metadata['scopes_supported'], 'is_string'))
            : [];

        return $metadata;
    }

    private function fetchJson(string $url): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('The PHP cURL extension is required for OIDC discovery');
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'Gibbon OIDC Discovery',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Unable to fetch OIDC discovery document (HTTP '.$status.')'.($error !== '' ? ': '.$error : ''));
        }

        $metadata = json_decode($body, true);
        if (!is_array($metadata)) {
            throw new RuntimeException('OIDC discovery response is not valid JSON');
        }

        return $metadata;
    }
}
