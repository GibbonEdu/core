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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserGateway;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * WebAuthn ceremony helpers for passkey registration and passwordless login.
 */
class PasskeyService
{
    protected const SESSION_CREATION = 'gibbonPasskeyCreation';
    protected const SESSION_REQUEST = 'gibbonPasskeyRequest';
    protected const SESSION_ROTATION = 'gibbonPasskeyRotation';
    protected const SESSION_ROTATION_OPTIONS = 'gibbonPasskeyRotationOptions';
    protected const CHALLENGE_TTL = 300;
    protected const ROTATION_DEFER_DAYS = 7;

    /** @var PasskeyRepository */
    protected $repository;

    /** @var Session */
    protected $session;

    /** @var UserGateway */
    protected $userGateway;

    /** @var SettingGateway */
    protected $settingGateway;

    /** @var AttestationStatementSupportManager|null */
    protected $attestationManager;

    /** @var SerializerInterface|null */
    protected $serializer;

    /** @var AuthenticatorAttestationResponseValidator|null */
    protected $attestationValidator;

    /** @var AuthenticatorAssertionResponseValidator|null */
    protected $assertionValidator;

    public function __construct(PasskeyRepository $repository, Session $session, UserGateway $userGateway, SettingGateway $settingGateway)
    {
        $this->repository = $repository;
        $this->session = $session;
        $this->userGateway = $userGateway;
        $this->settingGateway = $settingGateway;
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
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
        );

        $excludeCredentials = [];
        foreach ($this->repository->findAllForUserEntity($user) as $source) {
            $excludeCredentials[] = PublicKeyCredentialDescriptor::create('public-key', $source->publicKeyCredentialId);
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

        $optionsJson = json_decode($this->getSerializer()->serialize($options, 'json'), true);
        $nonce = bin2hex(random_bytes(16));
        $this->session->set(self::SESSION_CREATION, [
            'options' => $optionsJson,
            'nonce'   => $nonce,
            'expires' => time() + self::CHALLENGE_TTL,
            'personID'=> $gibbonPersonID,
        ]);

        return ['options' => $optionsJson, 'nonce' => $nonce];
    }

    public function verifyAttestation(array $response, string $nonce, int $gibbonPersonID): CredentialRecord
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

        $options = $this->getSerializer()->deserialize(
            json_encode($stored['options']),
            PublicKeyCredentialCreationOptions::class,
            'json'
        );
        $credential = $this->loadCredential($response);
        $attestationResponse = $credential->response;

        if (!($attestationResponse instanceof AuthenticatorAttestationResponse)) {
            throw new \RuntimeException(__('Invalid passkey registration response.'));
        }

        return $this->getAttestationValidator()->check($attestationResponse, $options, $this->getHost());
    }

    public function createRequestOptions(?int $gibbonPersonID = null): array
    {
        $allowCredentials = [];

        if (!empty($gibbonPersonID)) {
            $user = $this->getUserEntity($gibbonPersonID);
            foreach ($this->repository->findAllForUserEntity($user) as $source) {
                $allowCredentials[] = PublicKeyCredentialDescriptor::create('public-key', $source->publicKeyCredentialId);
            }
        }

        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $this->getRpId(),
            $allowCredentials,
            'required',
            60000
        );

        $optionsJson = json_decode($this->getSerializer()->serialize($options, 'json'), true);
        $nonce = bin2hex(random_bytes(16));
        $this->session->set(self::SESSION_REQUEST, [
            'options' => $optionsJson,
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

        $options = $this->getSerializer()->deserialize(
            json_encode($stored['options']),
            PublicKeyCredentialRequestOptions::class,
            'json'
        );
        $credential = $this->loadCredential($response);
        $assertionResponse = $credential->response;

        if (!($assertionResponse instanceof AuthenticatorAssertionResponse)) {
            throw new \RuntimeException(__('Invalid passkey sign-in response.'));
        }

        $source = $this->repository->findOneByCredentialId($credential->rawId);
        if (empty($source)) {
            throw new \RuntimeException(__('Unknown passkey.'));
        }

        $source = $this->getAssertionValidator()->check(
            $source,
            $assertionResponse,
            $options,
            $this->getHost(),
            $source->userHandle
        );

        $this->repository->saveCredentialSource($source);
        $this->repository->markUsed($credential->rawId);

        try {
            $this->scheduleRotation((int) $source->userHandle, $credential->rawId);
        } catch (\Throwable $e) {
            // Rotation must never block a valid passkey login.
        }

        return (int) $source->userHandle;
    }

    public function getPendingRotation(int $gibbonPersonID): ?array
    {
        $rotation = $this->session->get(self::SESSION_ROTATION);

        if (empty($rotation) || !is_array($rotation) || (int) ($rotation['personID'] ?? 0) !== $gibbonPersonID) {
            return null;
        }

        return $rotation;
    }

    public function createRotationOptions(int $gibbonPersonID): array
    {
        $rotation = $this->getPendingRotation($gibbonPersonID);
        if (empty($rotation)) {
            throw new \RuntimeException(__('No passkey rotation is pending.'));
        }

        $user = $this->getUserEntity($gibbonPersonID);
        $rp = PublicKeyCredentialRpEntity::create($this->getRpName(), $this->getRpId());

        $parameters = [
            PublicKeyCredentialParameters::createPk(-7),
            PublicKeyCredentialParameters::createPk(-257),
        ];

        $selection = AuthenticatorSelectionCriteria::create(
            null,
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
        );

        $options = PublicKeyCredentialCreationOptions::create(
            $rp,
            $user,
            random_bytes(32),
            $parameters,
            $selection,
            'none',
            [],
            60000
        );

        $optionsJson = json_decode($this->getSerializer()->serialize($options, 'json'), true);
        $nonce = bin2hex(random_bytes(16));
        $this->session->set(self::SESSION_ROTATION_OPTIONS, [
            'options'   => $optionsJson,
            'nonce'     => $nonce,
            'expires'   => time() + self::CHALLENGE_TTL,
            'personID'  => $gibbonPersonID,
            'credentialId' => $rotation['credentialId'],
            'label'     => $rotation['label'] ?? '',
        ]);

        return ['options' => $optionsJson, 'nonce' => $nonce];
    }

    public function verifyRotationAttestation(array $response, string $nonce, int $gibbonPersonID): CredentialRecord
    {
        $stored = $this->session->get(self::SESSION_ROTATION_OPTIONS);
        $this->session->forget(self::SESSION_ROTATION_OPTIONS);

        if (empty($stored) || !is_array($stored)) {
            throw new \RuntimeException(__('Passkey rotation session expired.'));
        }
        if (!hash_equals((string) ($stored['nonce'] ?? ''), $nonce)
            || (int) ($stored['personID'] ?? 0) !== $gibbonPersonID
            || ($stored['expires'] ?? 0) < time()
            || empty($stored['credentialId'])) {
            throw new \RuntimeException(__('Invalid passkey rotation request.'));
        }

        $options = $this->getSerializer()->deserialize(
            json_encode($stored['options']),
            PublicKeyCredentialCreationOptions::class,
            'json'
        );
        $credential = $this->loadCredential($response);
        $attestationResponse = $credential->response;

        if (!($attestationResponse instanceof AuthenticatorAttestationResponse)) {
            throw new \RuntimeException(__('Invalid passkey rotation response.'));
        }

        return $this->getAttestationValidator()->check($attestationResponse, $options, $this->getHost());
    }

    public function completeRotation(int $gibbonPersonID, CredentialRecord $source): bool
    {
        $rotation = $this->getPendingRotation($gibbonPersonID);
        if (empty($rotation) || empty($rotation['credentialId'])) {
            return false;
        }

        $rotated = $this->repository->rotateForPerson(
            $gibbonPersonID,
            (string) ($rotation['label'] ?? ''),
            $source,
            (string) $rotation['credentialId']
        );

        if ($rotated) {
            $this->session->forget([self::SESSION_ROTATION, self::SESSION_ROTATION_OPTIONS]);
        }

        return $rotated;
    }

    public function deferRotation(int $gibbonPersonID): bool
    {
        $rotation = $this->getPendingRotation($gibbonPersonID);
        if (empty($rotation) || empty($rotation['credentialId'])) {
            return false;
        }

        $deferred = $this->repository->deferRotationByCredentialId(
            (string) $rotation['credentialId'],
            self::ROTATION_DEFER_DAYS
        );
        $this->session->forget([self::SESSION_ROTATION, self::SESSION_ROTATION_OPTIONS]);

        return $deferred;
    }

    protected function scheduleRotation(int $gibbonPersonID, string $rawCredentialId): void
    {
        $days = $this->getRotationDays();
        if ($days <= 0) {
            return;
        }

        $metadata = $this->repository->findMetadataByEncodedCredentialId(Base64UrlSafe::encodeUnpadded($rawCredentialId));
        if (empty($metadata) || empty($metadata['createdAt'])) {
            return;
        }

        $now = time();
        if (!empty($metadata['rotationDeferredUntil']) && strtotime($metadata['rotationDeferredUntil']) > $now) {
            return;
        }
        if (strtotime($metadata['createdAt']) + ($days * 86400) > $now) {
            return;
        }

        $this->session->set(self::SESSION_ROTATION, [
            'personID'     => $gibbonPersonID,
            'credentialId' => Base64UrlSafe::encodeUnpadded($rawCredentialId),
            'label'        => (string) ($metadata['label'] ?? ''),
            'createdAt'    => (string) $metadata['createdAt'],
        ]);
    }

    protected function getRotationDays(): int
    {
        $days = (int) $this->settingGateway->getSettingByScope('System', 'passkeyRotationDays');

        return max(0, min(3650, $days));
    }

    public function getPasskeysForPerson(int $gibbonPersonID): array
    {
        return $this->repository->selectByPerson($gibbonPersonID);
    }

    public function savePasskey(int $gibbonPersonID, string $label, CredentialRecord $source): bool
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

    protected function loadCredential(array $response): PublicKeyCredential
    {
        return $this->getSerializer()->deserialize(
            json_encode($response),
            PublicKeyCredential::class,
            'json'
        );
    }

    protected function getAttestationManager(): AttestationStatementSupportManager
    {
        if (!$this->attestationManager) {
            $this->attestationManager = AttestationStatementSupportManager::create([
                new NoneAttestationStatementSupport(),
            ]);
        }

        return $this->attestationManager;
    }

    protected function getSerializer(): SerializerInterface
    {
        if (!$this->serializer) {
            $this->serializer = (new WebauthnSerializerFactory($this->getAttestationManager()))->create();
        }

        return $this->serializer;
    }

    protected function getAttestationValidator(): AuthenticatorAttestationResponseValidator
    {
        if (!$this->attestationValidator) {
            $factory = new CeremonyStepManagerFactory();
            $factory->setAllowedOrigins([$this->getAllowedOrigin()]);
            $factory->setAttestationStatementSupportManager($this->getAttestationManager());
            $this->attestationValidator = AuthenticatorAttestationResponseValidator::create($factory->creationCeremony());
        }

        return $this->attestationValidator;
    }

    protected function getAssertionValidator(): AuthenticatorAssertionResponseValidator
    {
        if (!$this->assertionValidator) {
            $factory = new CeremonyStepManagerFactory();
            $factory->setAllowedOrigins([$this->getAllowedOrigin()]);
            $factory->setAttestationStatementSupportManager($this->getAttestationManager());
            $this->assertionValidator = AuthenticatorAssertionResponseValidator::create($factory->requestCeremony());
        }

        return $this->assertionValidator;
    }

    protected function getRpName(): string
    {
        return $this->session->get('systemName') ?: 'Gibbon';
    }

    protected function getRpId(): string
    {
        $host = parse_url((string) $this->session->get('absoluteURL'), PHP_URL_HOST);
        if (empty($host)) {
            throw new \RuntimeException(__('Passkey relying party host is not configured.'));
        }

        return preg_replace('/:\d+$/', '', $host);
    }

    protected function getHost(): string
    {
        return $this->getRpId();
    }

    protected function getAllowedOrigin(): string
    {
        $url = (string) $this->session->get('absoluteURL');
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        if (!in_array($scheme, ['https', 'http'], true) || $host === '') {
            throw new \RuntimeException(__('Passkey relying party origin is not configured.'));
        }
        if ($scheme === 'http' && !in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
            throw new \RuntimeException(__('Passkeys require HTTPS outside local development.'));
        }

        $origin = $scheme.'://'.$host;
        if (isset($parts['port'])) {
            $origin .= ':'.(int) $parts['port'];
        }

        return $origin;
    }
}
