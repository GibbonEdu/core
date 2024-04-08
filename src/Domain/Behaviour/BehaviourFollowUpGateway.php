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

namespace Gibbon\Domain\Behaviour;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * @version v27
 * @since   v27
 */
class BehaviourFollowUpGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonBehaviourFollowUp';
    private static $primaryKey = 'gibbonBehaviourFollowUpID';

    private static $searchableColumns = [''];
    
    private static $scrubbableKey = ['gibbonPersonID', 'gibbonBehaviourFollowUpID', 'gibbonBehaviourID'];
    private static $scrubbableColumns = ['followUp' => ''];

    public function selectFollowUpByBehaviourID($gibbonBehaviourID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonBehaviourFollowUp.*',
                'gibbonBehaviourFollowUp.followUp as comment',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.image_240'
            ])
            ->innerJoin('gibbonBehaviour', 'gibbonBehaviourFollowUp.gibbonBehaviourID=gibbonBehaviour.gibbonBehaviourID')
            ->innerJoin('gibbonPerson', 'gibbonBehaviourFollowUp.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('gibbonBehaviourFollowUp.gibbonBehaviourID=:gibbonBehaviourID')
            ->bindValue('gibbonBehaviourID', $gibbonBehaviourID);

        return $this->runSelect($query);
    }

    public function selectFollowUpsByBehaviorID($gibbonBehaviourIDList)
    {
        $idList = is_array($gibbonBehaviourIDList) ? implode(',', $gibbonBehaviourIDList) : $gibbonBehaviourIDList;
        $data = array('idList' => $idList);
        $sql = "SELECT gibbonBehaviourFollowUp.gibbonBehaviourID, gibbonBehaviourFollowUp.gibbonPersonID, gibbonBehaviourFollowUp.followUp, gibbonPerson.firstName, gibbonPerson.surname
        FROM gibbonBehaviourFollowUp
        JOIN gibbonPerson ON (gibbonBehaviourFollowUp.gibbonPersonID=gibbonPerson.gibbonPersonID)
        WHERE FIND_IN_SET(gibbonBehaviourFollowUp.gibbonBehaviourID, :idList)";

        return $this->db()->select($sql, $data);
    }
}
