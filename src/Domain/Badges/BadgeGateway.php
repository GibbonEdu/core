<?php

namespace Gibbon\Domain\Badges;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class BadgeGateway extends QueryableGateway
{
    use TableAware;
    private static $tableName = 'badgesBadge';
    private static $searchableColumns = ['p.firstname' , 'p.surname', 'bb.name', 'bb.category'];

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
                'p.gibbonPersonID as gibbonPersonID',
                'p.title as title',
                'p.surname as surname',
                'p.preferredName as preferredName'
            ])
            ->innerJoin('badgesBadgeStudent as bbs', 'bbs.badgesBadgeID = bb.badgesBadgeID')
            ->innerJoin('gibbonPerson as p', 'bbs.gibbonPersonID = p.gibbonPersonID')
            ->where('bbs.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'studentName' => function($query,$nameNeedle)
            {
                $nameNeedle = '%' . $nameNeedle . '%';
                return $query
                    ->where("(p.surname like :nameNeedle or p.preferredName like :nameNeedle)")
                    ->bindValue('nameNeedle',$nameNeedle);
            },
            'badgeId' => function($query,$badgeID)
            {
                return $query
                    ->where("bb.badgesBadgeID = :badgeID")
                    ->bindValue('badgeID',$badgeID);
            },
            'studentId' => function($query,$studentID)
            {
                return $query
                    ->where("p.gibbonPersonID = :studentID")
                    ->bindValue('studentID',$studentID);
            },
            'studentIdMulti' => function($query,$studentIDArr)
            {
                $needle = implode($needle,',');
                return $query
                    ->where("p.gibbonPersonID in :needle")
                    ->bindValue('needle',$needle);
            },
            'badgeStudentID' => function($query,$badgeStudentID)
            {
                return $query
                    ->where("bbs.badgesBadgeStudentID = :badgeStudentID")
                    ->bindValue('badgeStudentID',$badgeStudentID);
            },
            'badgeName' => function($query,$badgeNameNeedle)
            {
                $badgeNameNeedle = '%' . $badgeNameNeedle . '%'; //Surround in wildcards
                return $query
                    ->where("(bb.name like :badgeNameNeedle)")
                    ->bindValue('badgeNameNeedle',$badgeNameNeedle);
            },
            'badgeCategory' => function($query,$category)
            {
                return $query
                    ->where("(bb.category = :category)")
                    ->bindValue('category',$category);

            }
        ]);

        /*var_dump($criteria);
        echo "<br/><br/><br/>";*/
        return $this->runQuery($query,$criteria);
    }
}

?>