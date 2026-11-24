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

use Gibbon\Contracts\Database\Connection;

/**
 * Stores the link between an OIDC issuer/subject pair and a Gibbon person.
 *
 * Manual binding creates links only after the user authenticates with their
 * existing Gibbon credentials. Automatic matching is opt-in and is limited to
 * one unambiguous existing account; the adapter decides whether it is allowed.
 */
class OIDCLinkRepository
{
    protected Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function findBySubject(string $issuer, string $subject): ?array
    {
        if ($issuer === '' || $subject === '') {
            return null;
        }

        $link = $this->db->selectOne(
            "SELECT gibbonPersonOIDCID, gibbonPersonID, issuer, subject, email, displayName
             FROM gibbonPersonOIDC
             WHERE issuer=:issuer AND subject=:subject
             LIMIT 1",
            ['issuer' => $issuer, 'subject' => $subject]
        );

        return empty($link) ? null : $link;
    }

    public function findPersonIDsByField(string $field, string $value): array
    {
        if ($field === 'email') {
            return $this->findPersonIDsByEmail($value);
        }
        if ($field === 'username') {
            return $this->findPersonIDsByUsername($value);
        }

        return [];
    }

    public function findPersonIDsByEmail(string $email): array
    {
        $email = trim($email);
        if ($email === '') {
            return [];
        }

        $result = $this->db->select(
            "SELECT gibbonPersonID FROM gibbonPerson
             WHERE status='Full' AND LOWER(email)=LOWER(:email)
             LIMIT 2",
            ['email' => $email]
        );

        return array_map('strval', $result->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function findPersonIDsByUsername(string $username): array
    {
        $username = trim($username);
        if ($username === '') {
            return [];
        }

        $result = $this->db->select(
            "SELECT gibbonPersonID FROM gibbonPerson
             WHERE status='Full' AND username=:username
             LIMIT 2",
            ['username' => $username]
        );

        return array_map('strval', $result->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function link(string $gibbonPersonID, string $issuer, string $subject, string $email = '', string $displayName = ''): bool
    {
        if ($gibbonPersonID === '' || $issuer === '' || $subject === '') {
            return false;
        }

        $existing = $this->findBySubject($issuer, $subject);
        if (!empty($existing)) {
            return (string) $existing['gibbonPersonID'] === $gibbonPersonID;
        }

        return $this->db->insert(
            "INSERT INTO gibbonPersonOIDC
                (gibbonPersonID, issuer, subject, email, displayName)
             VALUES
                (:gibbonPersonID, :issuer, :subject, :email, :displayName)",
            [
                'gibbonPersonID' => $gibbonPersonID,
                'issuer'         => $issuer,
                'subject'        => $subject,
                'email'          => $email,
                'displayName'    => $displayName,
            ]
        ) !== false;
    }
}
