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

namespace Gibbon\Auth\Adapter;

use Gibbon\Http\Url;
use Gibbon\Auth\Exception;
use Gibbon\Auth\OIDC\OIDCLinkRepository;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserGateway;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * OpenID Connect adapter with configurable account linking.
 *
 * Manual binding is the default: an unknown OIDC subject is held as a pending
 * identity until the user signs in with their existing Gibbon credentials.
 * Administrators may opt into automatic matching by email or username; matching
 * only succeeds when exactly one existing full account is found. Later logins
 * resolve the stored issuer + subject link without repeating account matching.
 *
 * @version v1
 * @since   v31
 */
class OIDCAdapter extends AuthenticationAdapter implements OAuthAdapterInterface
{
    public function login(array $input)
    {
        $session = $this->getContainer()->get(Session::class);
        $session->forget('oAuthMethod');

        $settings = $this->getSettings();
        if (empty($settings['enabled']) || $settings['enabled'] !== 'Y') {
            throw new Exception\OAuthLoginError('OIDC provider is not enabled');
        }

        if (isset($_GET['error'])) {
            $errorDescription = is_scalar($_GET['error_description'] ?? null)
                ? trim((string) $_GET['error_description'])
                : '';
            $error = is_scalar($_GET['error']) ? trim((string) $_GET['error']) : 'OIDC authentication failed';
            throw new Exception\OAuthLoginError($errorDescription !== '' ? $errorDescription : $error);
        }

        if (!$this->hasOAuthCode()) {
            throw new Exception\OAuthLoginError('Missing code');
        }

        $expectedState = (string) $session->get('oAuthStateOIDC', '');
        $receivedState = is_scalar($_GET['state'] ?? null) ? (string) $_GET['state'] : '';
        if ($expectedState === '' || $receivedState === '' || !hash_equals($expectedState, $receivedState)) {
            throw new Exception\OAuthLoginError('Invalid OIDC redirect state');
        }
        $session->forget('oAuthStateOIDC');

        $provider = $this->getProvider();
        $code = trim((string) $_GET['code']);

        try {
            $accessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);
            $accessTokenString = is_object($accessToken) && method_exists($accessToken, 'getToken')
                ? (string) $accessToken->getToken()
                : (string) $accessToken;
        } catch (\InvalidArgumentException | \UnexpectedValueException | IdentityProviderException $e) {
            throw new Exception\OAuthLoginError($e->getCode().': '.$e->getMessage());
        }

        if (empty($accessTokenString)) {
            throw new Exception\OAuthLoginError('Missing access token');
        }

        try {
            $resourceOwner = $provider->getResourceOwner($accessToken);
            $user = $resourceOwner->toArray();
        } catch (\Throwable $e) {
            throw new Exception\OAuthLoginError('Unable to read OIDC user profile');
        }

        $subjectAttribute = trim((string) ($settings['subjectAttribute'] ?? '')) ?: 'sub';
        $subject = $user[$subjectAttribute] ?? '';
        if (!is_scalar($subject) || (string) $subject === '') {
            throw new Exception\OAuthLoginError('OIDC subject claim is missing');
        }
        $subject = (string) $subject;

        $issuer = trim((string) ($settings['issuer'] ?? ''));
        if ($issuer === '') {
            $issuer = trim((string) ($settings['clientID'] ?? ''));
        }

        $email = $this->firstScalarClaim($user, ['email', 'emailAddress', 'email_address']);
        $matchAttribute = trim((string) ($settings['matchAttribute'] ?? '')) ?: 'preferred_username';
        $username = $this->firstScalarClaim($user, [$matchAttribute, 'preferred_username', 'username']);
        $_POST['usernameOAuth'] = $email !== '' ? $email : $username;

        $displayName = $this->firstScalarClaim($user, ['name', 'preferred_username', 'given_name', 'email']);
        $links = $this->getContainer()->get(OIDCLinkRepository::class);
        $personID = $this->resolvePersonID($settings, $user, $links, $issuer, $subject, $email, $displayName);

        if ($personID === '') {
            // Automatic matching must never silently downgrade to manual binding.
            if (!$this->shouldOfferManualBinding($settings)) {
                throw new Exception\OAuthUserNotFound;
            }

            $session->set('oidcPending', [
                'issuer'      => $issuer,
                'subject'     => $subject,
                'email'       => $email,
                'displayName' => $displayName,
            ]);
            throw new Exception\OIDCLinkRequired;
        }

        $this->userGateway = $this->getContainer()->get(UserGateway::class);
        $result = $this->userGateway->selectLoginDetailsByID($personID);
        if ($result->rowCount() != 1) {
            throw new Exception\DatabaseLoginError;
        }

        $userData = $result->fetch();
        $_POST['gibbonPersonIDLoginAttempt'] = $userData['gibbonPersonID'] ?? null;

        // Keep only a non-reversible binding value if this account also has TOTP enabled.
        $mfaCheck = $this->userGateway->getByID($personID, ['mfaSecret']);
        if (!empty($mfaCheck['mfaSecret'])) {
            $session->set('oidcMFABinding', hash('sha256', $accessTokenString));
        }
        $session->forget('oidcPending');

        if ($session->has('oAuthOptions')) {
            list($gibbonSchoolYearID, $gibboni18nID) = array_pad(explode(':', $session->get('oAuthOptions')), 2, '');
            $_POST['gibbonSchoolYearID'] = $gibbonSchoolYearID;
            $_POST['gibboni18nID'] = $gibboni18nID;
            $session->forget('oAuthOptions');
        }

        try {
            return parent::verifyLogin($userData);
        } catch (\Throwable $e) {
            if (!($e instanceof Exception\MFATokenRequired)) {
                $session->forget('oidcMFABinding');
            }
            throw $e;
        }
    }

    public function hasOAuthCode(): bool
    {
        return isset($_GET['code']) && is_scalar($_GET['code']) && trim((string) $_GET['code']) !== '';
    }

    public function getAuthorizationUrl(): string
    {
        $settings = $this->getSettings();
        if (empty($settings['enabled']) || $settings['enabled'] !== 'Y') {
            throw new Exception\OAuthLoginError('OIDC provider is not enabled');
        }

        $provider = $this->getProvider();
        $authUrl = $provider->getAuthorizationUrl();
        $this->getContainer()->get(Session::class)->set('oAuthStateOIDC', $provider->getState());

        return $authUrl;
    }

    public function getRedirectUrl(): string
    {
        return Url::fromRoute('login')->withQueryParam('method', 'oidc');
    }

    private function getProvider()
    {
        return $this->getContainer()->get('OIDC_Auth');
    }

    private function resolvePersonID(
        array $settings,
        array $user,
        OIDCLinkRepository $links,
        string $issuer,
        string $subject,
        string $email,
        string $displayName
    ): string {
        $link = $links->findBySubject($issuer, $subject);
        if (!empty($link)) {
            return (string) $link['gibbonPersonID'];
        }

        $matchMode = (string) ($settings['accountMatching'] ?? 'manual');
        if ($matchMode === 'manual') {
            return '';
        }

        $matchClaims = $this->parseMatchClaims((string) ($settings['matchClaims'] ?? ''));
        if (empty($matchClaims)) {
            // Backwards compatibility with the original email/username modes.
            $matchClaims = $matchMode === 'username'
                ? [['field' => 'username', 'claim' => (string) ($settings['matchAttribute'] ?? 'preferred_username')]]
                : [['field' => 'email', 'claim' => 'email']];
        }

        $requireVerified = ($settings['requireVerifiedEmail'] ?? 'Y') !== 'N';
        $matchedIDs = [];
        $ambiguous = false;

        foreach ($matchClaims as $mapping) {
            $value = $this->firstScalarClaim($user, [$mapping['claim']]);
            if ($value === '') {
                continue;
            }

            if ($mapping['field'] === 'email' && $requireVerified && !$this->isEmailVerified($user)) {
                continue;
            }

            $candidateIDs = $links->findPersonIDsByField($mapping['field'], $value);
            if (count($candidateIDs) > 1) {
                $ambiguous = true;
                break;
            }
            if (count($candidateIDs) === 1) {
                $matchedIDs[] = (string) $candidateIDs[0];
            }
        }

        $matchedIDs = array_values(array_unique($matchedIDs));
        if ($ambiguous || count($matchedIDs) !== 1) {
            return '';
        }

        $candidateID = $matchedIDs[0];
        try {
            return $links->link($candidateID, $issuer, $subject, $email, $displayName)
                ? $candidateID
                : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function parseMatchClaims(string $value): array
    {
        $mappings = [];
        foreach (preg_split('/[,;\n]+/', $value) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (strpos($part, '=') !== false) {
                [$field, $claim] = array_map('trim', explode('=', $part, 2));
            } elseif (strpos($part, ':') !== false) {
                [$field, $claim] = array_map('trim', explode(':', $part, 2));
            } else {
                $field = $part;
                $claim = $part;
            }

            $field = strtolower($field);
            if (!in_array($field, ['email', 'username'], true) || $claim === '') {
                continue;
            }

            $mappings[$field] = $claim;
        }

        $result = [];
        foreach ($mappings as $field => $claim) {
            $result[] = ['field' => $field, 'claim' => $claim];
        }

        return $result;
    }

    private function shouldOfferManualBinding(array $settings): bool
    {
        $matchMode = (string) ($settings['accountMatching'] ?? 'manual');
        $automatic = in_array($matchMode, ['auto', 'email', 'username'], true);

        return !$automatic || ($settings['fallbackToManual'] ?? 'N') === 'Y';
    }

    private function getSettings(): array
    {
        $settingGateway = $this->getContainer()->get(SettingGateway::class);
        $settings = json_decode((string) $settingGateway->getSettingByScope('System Admin', 'ssoOIDC'), true);

        return is_array($settings) ? $settings : [];
    }

    private function firstScalarClaim(array $claims, array $keys): string
    {
        foreach ($keys as $key) {
            if ($key !== '' && isset($claims[$key]) && is_scalar($claims[$key])) {
                $value = trim((string) $claims[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function isEmailVerified(array $user): bool
    {
        $value = $user['email_verified'] ?? null;

        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'y', 'yes'], true);
        }

        return false;
    }
}
