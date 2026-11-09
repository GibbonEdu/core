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
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Database-backed WebAuthn credential repository.
 */
class PasskeyRepository implements PublicKeyCredentialSourceRepository
{
    /** @var \PDO */
    protected $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getConnection();
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
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
     * @return PublicKeyCredentialSource[]
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return $this->findAllForPerson((int) $publicKeyCredentialUserEntity->getId());
    }

    /**
     * @return PublicKeyCredentialSource[]
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

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredentialSource->getPublicKeyCredentialId());

        $stmt = $this->db->prepare('UPDATE gibbonPersonPasskey SET credentialSource=:credentialSource WHERE credentialId=:credentialId');
        $stmt->execute([
            'credentialSource' => json_encode($publicKeyCredentialSource),
            'credentialId'     => $credentialId,
        ]);
    }

    public function saveForPerson(int $gibbonPersonID, string $label, PublicKeyCredentialSource $source): bool
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($source->getPublicKeyCredentialId());
        $label = trim($label) !== '' ? mb_substr(trim($label), 0, 60) : __('Passkey');

        $sql = 'INSERT INTO gibbonPersonPasskey
                    (gibbonPersonID, label, credentialId, credentialSource)
                VALUES
                    (:gibbonPersonID, :label, :credentialId, :credentialSource)
                ON DUPLICATE KEY UPDATE
                    label=VALUES(label), credentialSource=VALUES(credentialSource)';

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'gibbonPersonID'  => $gibbonPersonID,
                'label'           => $label,
                'credentialId'    => $credentialId,
                'credentialSource'=> json_encode($source),
            ]);
        } catch (\PDOException $e) {
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
        return $stmt->execute([
            'id' => $gibbonPersonPasskeyID,
            'gibbonPersonID' => $gibbonPersonID,
        ]);
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

    protected function decodeSource($json): ?PublicKeyCredentialSource
    {
        if (empty($json)) {
            return null;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }

        try {
            return PublicKeyCredentialSource::createFromArray($data);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
