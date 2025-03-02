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

namespace Gibbon\Module\FreeLearning\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class BadgeGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningBadge';
    private static $primaryKey = 'freeLearningBadgeID';

    public function selectBadges($activeOnly = true, $search = '')
    {
        $query = $this
            ->newSelect()
            ->cols(['freeLearningBadge.*','name', 'category', 'logo', 'description'])
            ->from('freeLearningBadge')
            ->innerJoin('badgesBadge', 'freeLearningBadge.badgesBadgeID=badgesBadge.badgesBadgeID');

        if ($activeOnly) {
            $query->where("freeLearningBadge.active = 'Y' AND badgesBadge.active = 'Y'");
        }

        if ($search != '') {
            $query->where("badgesBadge.name LIKE :search OR badgesBadge.category LIKE :search")
                ->bindValue('search', "%".$search."%");
        }

        return $this->runSelect($query)->fetchAll();
    }
}
