<?php

namespace Gibbon\Domain\Badges;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class BadgeGateway extends QueryableGateway
{
    use TableAware;
    private static $tableName = 'badgesBadge';
    private static $searchableColumns = ['p.firstname' , 'p.surname', 'p.'];

    public function queryBadges(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName() . ' as bb')
            ->cols([
                'bbs.badgesBadgeStudentID as badgesBadgeStudentID',
                'bb.logo as logo',
                'bb.name as name',
                'bb.category as category',
                'bb.description as description',
                'bbs.date as date',
                'bbs.comment as comment',
                'p.title as title',
                'p.surname as surname',
                'p.preferredName as preferredName'
            ])
            ->innerJoin('badgesBadgeStudent as bbs', 'bbs.badgesBadgeID = bb.badgesBadgeID')
            ->innerJoin('gibbonPerson as p', 'bbs.gibbonPersonID = p.gibbonPersonID')
            ->where('bbs.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'name' => function($query,$needle)
            {
                return $query
                    ->where("(p.surname like '%:needle%' or p.preferredName like '%:needle%')")
                    ->bindValue('needle',$needle);
            }
        ]);

        return $this->runQuery($query,$criteria);
    }
}

?>