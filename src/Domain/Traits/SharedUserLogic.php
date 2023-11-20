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

namespace Gibbon\Domain\Traits;

/**
 * Provides common filter and row highlight logic for tables that deal with user role and status.
 */
trait SharedUserLogic
{
    protected function getSharedUserFilterRules()
    {
        return [
            'role' => function ($query, $roleCategory) {
                return $query
                    ->where('gibbonRole.category = :roleCategory')
                    ->bindValue('roleCategory', ucfirst($roleCategory));
            },

            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonPerson.status = :status')
                    ->bindValue('status', ucfirst($status));
            },

            'date' => function ($query, $dateType) {
                return $query
                    ->where(($dateType == 'starting')
                        ? '(gibbonPerson.dateStart IS NOT NULL AND gibbonPerson.dateStart >= :today)'
                        : '(gibbonPerson.dateEnd IS NOT NULL AND gibbonPerson.dateEnd <= :today)')
                    ->bindValue('today', date('Y-m-d'));
            },
        ];
    }

    public function getSharedUserRowHighlighter()
    {
        return function($person, $row) {
            $highlight = '';
            if (!empty($person['status']) && $person['status'] != 'Full') $highlight = 'error';
            if (!empty($person['roleCategory']) && $person['roleCategory'] == 'Student') {
                if (!(empty($person['dateStart']) || $person['dateStart'] <= date('Y-m-d'))) $highlight = 'dull';
                if (!(empty($person['dateEnd'] ) || $person['dateEnd'] >= date('Y-m-d'))) $highlight = 'error';
                if (empty($person['gibbonStudentEnrolmentID'])) $highlight = 'error';
            }
            return $row->addClass($highlight);
        };
    }
}
