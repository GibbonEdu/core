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

namespace Gibbon\Auth\Passkey;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\User\UserGateway;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * WebAuthn ceremony helpers for passkey registration and passwordless login.
 */
class PasskeyService
{
    protected const SESSION_CREATION = 'gibbonPasskeyCreation';
    protected const SESSION_REQUEST = 'gibbonPasskeyRequest';
    protected const CHALLENGE_TTL = 300;

    /** @var PasskeyRepository */
    protected $repository;

    /** @var Session */
    protected $session;

    /** @var UserGateway */
    protected $userGateway;

    public function __construct(PasskeyRepository $repository, Session $session, UserGateway $userGateway)
    {
        $this->repository = $repository;
        $this->session = $session;
        $this->userGateway = $userGateway;
    }

    public function createCreationOptions(int $gibbonPersonID): array
    {
        $user = $this->getUserEntity($gibbonPersonID);
        $rp = PublicKeyCredentialRpEntity::create($this->getRpName(), $this->getRpId());

        $parameters = [
            PublicKeyCredentialParameters::createPk(-7),   // ES256
            PublicKeyCredentialParameters::createPk(-257), // RS256
        ];

        $selection = AuthenticatorSelectionCriteria::create(
            null,
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            true
        );

        $excludeCredentials = [];
        foreach ($this->repository->findAllForUserEntity($user) as $source) {
            $excludeCredentials[] = PublicKeyCredentialDescriptor::create('public-key', $source->getPublicKeyCredentialId());
        }

        $options = PublicKeyCredentialCreationOptions::create(
            $rp,
            $user,
            random_bytes(32),
            $parameters,
            $selection,
            'none',
            $excludeCredentials,
            60000
        );

        $optionsJson = $options->jsonSerialize();
        $nonce = bin2hex(random_bytes(16));
        $this->session->set(self::SESSION_CREATION, [
            'options' => json_decode(json_encode($optionsJson), true),
            'nonce'   => $nonce,
            'expires' => time() + self::CHALLENGE_TTL,
            'personID'=> $gibbonPersonID,
        ]);

        return ['options' => $optionsJson, 'nonce' => $nonce];
    }

    public function verifyAttestation(array $response, string $nonce, int $gibbonPersonID): PublicKeyCredentialSource
    {
        $stored = $this->session->get(self::SESSION_CREATION);
        $this->session->forget(self::SESSION_CREATION);

        if (empty($stored) || !is_array($stored)) {
            throw new \RuntimeException(__('Passkey registration session expired.'));
        }
        if (!hash_equals((string) ($stored['nonce'] ?? ''), $nonce)) {
            throw new \RuntimeException(__('Invalid passkey registration request.'));
        }
        if ((int) ($stored['personID'] ?? 0) !== $gibbonPersonID) {
            throw new \RuntimeException(__('Invalid passkey registration request.'));
        }
        if (($stored['expires'] ?? 0) < time()) {
            throw new \RuntimeException(__('Passkey registration session expired.'));
        }

        $options = PublicKeyCredentialCreationOptions::createFromArray($stored['options']);
        $credential = $this->loadCredential($response);
        $attestationResponse = $credential->getResponse();

        if (!($attestationResponse instanceof AuthenticatorAttestationResponse)) {
            throw new \RuntimeException(__('Invalid passkey registration response.'));
        }

        $validator = AuthenticatorAttestationResponseValidator::create();

        return $validator->check($attestationResponse, $options, $this->getHost(), ['localhost', '127.0.0.1']);
    }

    public function createRequestOptions(?int $gibbonPersonID = null): array
    {
        $allowCredentials = [];

        if (!empty($gibbonPersonID)) {
            $user = $this->getUserEntity($gibbonPersonID);
            foreach ($this->repository->findAllForUserEntity($user) as $source) {
                $allowCredentials[] = PublicKeyCredentialDescriptor::create('public-key', $source->getPublicKeyCredentialId());
            }
        }

        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $this->getRpId(),
            $allowCredentials,
            'required',
            60000
        );

        $optionsJson = $options->jsonSerialize();
        $nonce = bin2hex(random_bytes(16));
        $this->session->set(self::SESSION_REQUEST, [
            'options' => json_decode(json_encode($optionsJson), true),
            'nonce'   => $nonce,
            'expires' => time() + self::CHALLENGE_TTL,
        ]);

        return ['options' => $optionsJson, 'nonce' => $nonce];
    }

    public function verifyAssertion(array $response, string $nonce): int
    {
        $stored = $this->session->get(self::SESSION_REQUEST);
        $this->session->forget(self::SESSION_REQUEST);

        if (empty($stored) || !is_array($stored)) {
            throw new \RuntimeException(__('Passkey sign-in session expired.'));
        }
        if (!hash_equals((string) ($stored['nonce'] ?? ''), $nonce)) {
            throw new \RuntimeException(__('Invalid passkey sign-in request.'));
        }
        if (($stored['expires'] ?? 0) < time()) {
            throw new \RuntimeException(__('Passkey sign-in session expired.'));
        }

        $options = PublicKeyCredentialRequestOptions::createFromArray($stored['options']);
        $credential = $this->loadCredential($response);
        $assertionResponse = $credential->getResponse();

        if (!($assertionResponse instanceof AuthenticatorAssertionResponse)) {
            throw new \RuntimeException(__('Invalid passkey sign-in response.'));
        }

        $source = $this->repository->findOneByCredentialId($credential->getRawId());
        if (empty($source)) {
            throw new \RuntimeException(__('Unknown passkey.'));
        }

        $validator = AuthenticatorAssertionResponseValidator::create();
        $source = $validator->check(
            $source,
            $assertionResponse,
            $options,
            $this->getHost(),
            $source->getUserHandle(),
            ['localhost', '127.0.0.1']
        );

        $this->repository->saveCredentialSource($source);
        $this->repository->markUsed($credential->getRawId());

        return (int) $source->getUserHandle();
    }

    public function getPasskeysForPerson(int $gibbonPersonID): array
    {
        return $this->repository->selectByPerson($gibbonPersonID);
    }

    public function savePasskey(int $gibbonPersonID, string $label, PublicKeyCredentialSource $source): bool
    {
        return $this->repository->saveForPerson($gibbonPersonID, $label, $source);
    }

    public function countForPerson(int $gibbonPersonID): int
    {
        return $this->repository->countForPerson($gibbonPersonID);
    }

    public function deletePasskey(int $gibbonPersonPasskeyID, int $gibbonPersonID): bool
    {
        return $this->repository->deleteForPerson($gibbonPersonPasskeyID, $gibbonPersonID);
    }

    public function resetForPerson(int $gibbonPersonID): bool
    {
        return $this->repository->deleteAllForPerson($gibbonPersonID);
    }

    protected function getUserEntity(int $gibbonPersonID): PublicKeyCredentialUserEntity
    {
        $user = $this->userGateway->getByID($gibbonPersonID, ['username', 'preferredName', 'surname']);
        $username = $user['username'] ?? (string) $gibbonPersonID;
        $displayName = trim(($user['preferredName'] ?? '').' '.($user['surname'] ?? ''));

        return PublicKeyCredentialUserEntity::create(
            $username,
            (string) $gibbonPersonID,
            $displayName !== '' ? $displayName : $username
        );
    }

    protected function loadCredential(array $response): \Webauthn\PublicKeyCredential
    {
        $attestationManager = AttestationStatementSupportManager::create([
            new NoneAttestationStatementSupport(),
        ]);

        $loader = PublicKeyCredentialLoader::create(
            AttestationObjectLoader::create($attestationManager)
        );

        return $loader->loadArray($response);
    }

    protected function getRpName(): string
    {
        return $this->session->get('systemName') ?: 'Gibbon';
    }

    protected function getRpId(): string
    {
        $host = parse_url((string) $this->session->get('absoluteURL'), PHP_URL_HOST);
        if (empty($host)) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        }

        return preg_replace('/:\d+$/', '', $host);
    }

    protected function getHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? $this->getRpId();

        return preg_replace('/:\d+$/', '', $host);
    }
}
