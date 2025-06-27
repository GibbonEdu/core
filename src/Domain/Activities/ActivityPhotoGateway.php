<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

namespace Gibbon\Domain\Activities;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ActivityPhotoGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityPhoto';
    private static $primaryKey = 'gibbonActivityPhotoID';
    private static $searchableColumns = [''];

    public function selectPhotosByActivityCategory($gibbonActivityCategoryID)
    {
        $data = ['gibbonActivityCategoryID' => $gibbonActivityCategoryID];
        $sql = "SELECT gibbonActivityPhoto.gibbonActivityID, gibbonActivityPhoto.gibbonActivityPhotoID, gibbonActivityPhoto.filePath, gibbonActivityPhoto.caption
                FROM gibbonActivityPhoto
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityPhoto.gibbonActivityID)
                WHERE gibbonActivity.gibbonActivityCategoryID=:gibbonActivityCategoryID
                AND gibbonActivityPhoto.sequenceNumber=1
                ORDER BY gibbonActivityPhoto.sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectPhotosByActivity($gibbonActivityID)
    {
        $data = ['gibbonActivityID' => $gibbonActivityID];
        $sql = "SELECT gibbonActivityPhoto.gibbonActivityPhotoID, gibbonActivityPhoto.filePath, gibbonActivityPhoto.caption
                FROM gibbonActivityPhoto
                JOIN gibbonActivity ON (gibbonActivity.gibbonActivityID=gibbonActivityPhoto.gibbonActivityID)
                WHERE gibbonActivityPhoto.gibbonActivityID=:gibbonActivityID
                ORDER BY gibbonActivityPhoto.sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectPhotosNotInList($gibbonActivityID, $photoIDList)
    {
        $photoIDList = is_array($photoIDList) ? implode(',', $photoIDList) : $photoIDList;

        $data = ['gibbonActivityID' => $gibbonActivityID, 'photoIDList' => $photoIDList];
        $sql = "SELECT * FROM gibbonActivityPhoto WHERE gibbonActivityID=:gibbonActivityID AND NOT FIND_IN_SET(gibbonActivityPhotoID, :photoIDList)";

        return $this->db()->select($sql, $data);
    }
}
