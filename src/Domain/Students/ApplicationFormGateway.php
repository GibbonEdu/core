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

namespace Gibbon\Domain\Students;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\TableQueryAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class ApplicationFormGateway extends QueryableGateway
{
    use TableAware;
    use TableQueryAware;

    private static $tableName = 'gibbonApplicationForm';
    private static $primaryKey = 'gibbonApplicationFormID';

    private static $searchableColumns = ['gibbonApplicationFormID', 'preferredName', 'surname', 'username'];

    public function getApplicationFormByID($gibbonApplicationFormID)
    {
        return $this->selectRow($this->getTableName(), $this->getPrimaryKey(), $gibbonApplicationFormID)->fetch();
    }

    public function updateApplicationForm($data)
    {
        return $this->updateRow($this->getTableName(), $this->getPrimaryKey(), $data);
    }

    public function insertApplicationForm($data)
    {
        return $this->insertRow($this->getTableName(), $this->getPrimaryKey(), $data);
    }

    public function updateApplicationFormRelationship($data)
    {
        return $this->updateRow('gibbonApplicationFormRelationship', 'gibbonApplicationFormRelationshipID', $data);
    }

    public function insertApplicationFormRelationship($data)
    {
        return $this->insertRow('gibbonApplicationFormRelationship', 'gibbonApplicationFormRelationshipID', $data);
    }
}
