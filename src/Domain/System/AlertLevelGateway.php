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

namespace Gibbon\Domain\System;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * AlarmLevel Gateway.
 *
 * @version v25
 * @since   v25
 */
class AlertLevelGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonAlertLevel';
    private static $primaryKey = 'gibbonAlertLevelID';

    const LEVEL_HIGH = 1;
    const LEVEL_MEDIUM = 2;
    const LEVEL_LOW = 3;

    /**
     * Get the specified alert level.
     *
     * @version v25
     * @since   v25
     *
     * @param integer $gibbonAlertLevelID  The ID of the alert level.
     * @param bool    $translated          To translate name and description field of the
     *                                     alert. Default: true.
     *
     * @return array|false  An assoc array of the selected alert,
     *                      or false if not exists.
     *                      The field 'name' and 'description' are
     *                      translated unless $translated is false.
     */
    public function getByID(int $gibbonAlertLevelID, bool $translated = true)
    {
        $sql = 'SELECT * FROM gibbonAlertLevel WHERE gibbonAlertLevelID=:gibbonAlertLevelID';
        $row = $this->db()
            ->selectOne($sql, [
                'gibbonAlertLevelID' => $gibbonAlertLevelID,
            ]);
        return (!empty($row) && $translated)
            ? [
                'gibbonAlertLevelID' => $row['gibbonAlertLevelID'],
                'name' => __($row['name']),
                'nameShort' => $row['nameShort'],
                'color' => $row['color'],
                'colorBG' => $row['colorBG'],
                'description' => __($row['description']),
                'sequenceNumber' => $row['sequenceNumber'],
            ]
            : $row;
    }
}
