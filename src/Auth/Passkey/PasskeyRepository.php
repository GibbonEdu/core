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

use Gibbon\Contracts\Database\Connection;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Database-backed WebAuthn credential repository.
 */
class PasskeyRepository
{
    /** @var \PDO */
    protected $db;

    /** @var SerializerInterface|null */
    protected $serializer;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getConnection();
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?CredentialRecord
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredentialId);

        try {
            $stmt = $this->db->prepare('SELECT credentialSource FROM gibbonPersonPasskey WHERE credentialId=:credentialId');
            $stmt->execute(['credentialId' => $credentialId]);
            $json = $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return null;
        }

        return $this->decodeSource($json);
    }

    /**
     * @return CredentialRecord[]
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return $this->findAllForPerson((int) $publicKeyCredentialUserEntity->id);
    }

    /**
     * @return CredentialRecord[]
     */
    public function findAllForPerson(int $gibbonPersonID): array
    {
        try {
            $stmt = $this->db->prepare('SELECT credentialSource FROM gibbonPersonPasskey WHERE gibbonPersonID=:gibbonPersonID');
            $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);
            $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }

        $sources = [];
        foreach ($rows as $json) {
            $source = $this->decodeSource($json);
            if ($source !== null) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    public function saveCredentialSource(CredentialRecord $credentialRecord): void
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($credentialRecord->publicKeyCredentialId);

        $stmt = $this->db->prepare('UPDATE gibbonPersonPasskey SET credentialSource=:credentialSource WHERE credentialId=:credentialId');
        $stmt->execute([
            'credentialSource' => $this->getSerializer()->serialize($credentialRecord, 'json'),
            'credentialId'     => $credentialId,
        ]);
    }

    public function saveForPerson(int $gibbonPersonID, string $label, CredentialRecord $source): bool
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId);
        $label = trim($label) !== '' ? mb_substr(trim($label), 0, 60) : __('Passkey');

        try {
            $existing = $this->db->prepare('SELECT gibbonPersonID FROM gibbonPersonPasskey WHERE credentialId=:credentialId');
            $existing->execute(['credentialId' => $credentialId]);
            $existingPersonID = $existing->fetchColumn();
            if ($existingPersonID !== false && (int) $existingPersonID !== $gibbonPersonID) {
                return false;
            }

            $sql = 'INSERT INTO gibbonPersonPasskey
                        (gibbonPersonID, label, credentialId, credentialSource)
                    VALUES
                        (:gibbonPersonID, :label, :credentialId, :credentialSource)';
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'gibbonPersonID'  => $gibbonPersonID,
                'label'           => $label,
                'credentialId'    => $credentialId,
                'credentialSource'=> $this->getSerializer()->serialize($source, 'json'),
            ]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function findMetadataByEncodedCredentialId(string $credentialId): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT gibbonPersonPasskeyID, gibbonPersonID, label, credentialId, createdAt, lastUsedAt, rotationDeferredUntil FROM gibbonPersonPasskey WHERE credentialId=:credentialId');
            $stmt->execute(['credentialId' => $credentialId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return null;
        }

        return $row ?: null;
    }

    public function deferRotationByCredentialId(string $credentialId, int $days = 7): bool
    {
        $days = max(1, min(3650, $days));

        try {
            $stmt = $this->db->prepare('UPDATE gibbonPersonPasskey SET rotationDeferredUntil=DATE_ADD(NOW(), INTERVAL '.$days.' DAY) WHERE credentialId=:credentialId');
            return $stmt->execute(['credentialId' => $credentialId]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function rotateForPerson(int $gibbonPersonID, string $label, CredentialRecord $source, string $oldEncodedCredentialId): bool
    {
        $newCredentialId = Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId);

        try {
            $this->db->beginTransaction();

            if (!$this->saveForPerson($gibbonPersonID, $label, $source)) {
                $this->db->rollBack();
                return false;
            }

            if ($newCredentialId !== $oldEncodedCredentialId) {
                $delete = $this->db->prepare('DELETE FROM gibbonPersonPasskey WHERE gibbonPersonID=:gibbonPersonID AND credentialId=:credentialId');
                $delete->execute([
                    'gibbonPersonID' => $gibbonPersonID,
                    'credentialId'   => $oldEncodedCredentialId,
                ]);
            }

            $reset = $this->db->prepare('UPDATE gibbonPersonPasskey SET rotationDeferredUntil=NULL WHERE gibbonPersonID=:gibbonPersonID AND credentialId=:credentialId');
            $reset->execute([
                'gibbonPersonID' => $gibbonPersonID,
                'credentialId'   => $newCredentialId,
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function selectByPerson(int $gibbonPersonID): array
    {
        try {
            $stmt = $this->db->prepare('SELECT gibbonPersonPasskeyID, label, createdAt, lastUsedAt FROM gibbonPersonPasskey WHERE gibbonPersonID=:gibbonPersonID ORDER BY createdAt DESC');
            $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function countForPerson(int $gibbonPersonID): int
    {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM gibbonPersonPasskey WHERE gibbonPersonID=:gibbonPersonID');
            $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    public function deleteForPerson(int $gibbonPersonPasskeyID, int $gibbonPersonID): bool
    {
        $stmt = $this->db->prepare('DELETE FROM gibbonPersonPasskey WHERE gibbonPersonPasskeyID=:id AND gibbonPersonID=:gibbonPersonID');
        $stmt->execute([
            'id' => $gibbonPersonPasskeyID,
            'gibbonPersonID' => $gibbonPersonID,
        ]);

        return $stmt->rowCount() === 1;
    }

    public function deleteAllForPerson(int $gibbonPersonID): bool
    {
        $stmt = $this->db->prepare('DELETE FROM gibbonPersonPasskey WHERE gibbonPersonID=:gibbonPersonID');
        return $stmt->execute(['gibbonPersonID' => $gibbonPersonID]);
    }

    public function markUsed(string $publicKeyCredentialId): void
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredentialId);

        $stmt = $this->db->prepare('UPDATE gibbonPersonPasskey SET lastUsedAt=NOW() WHERE credentialId=:credentialId');
        $stmt->execute(['credentialId' => $credentialId]);
    }

    protected function decodeSource($json): ?CredentialRecord
    {
        if (empty($json)) {
            return null;
        }

        try {
            return $this->getSerializer()->deserialize((string) $json, CredentialRecord::class, 'json');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getSerializer(): SerializerInterface
    {
        if (!$this->serializer) {
            $manager = AttestationStatementSupportManager::create([
                new NoneAttestationStatementSupport(),
            ]);
            $this->serializer = (new WebauthnSerializerFactory($manager))->create();
        }

        return $this->serializer;
    }
}
