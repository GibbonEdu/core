<?php
/**
 * Created by PhpStorm.
 *
 * This file is part of the Busybee Project.
 *
 * (c) Craig Rayner <craig@craigrayner.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * User: craig
 * Date: 13/11/2018
 * Time: 13:23
 */

use Gibbon\Domain\Traits\DoctrineEntity;
use Gibbon\Entity\Person;
use Gibbon\Entity\RollGroup;
use Gibbon\Entity\StudentEnrolment;

class RollGroupManager
{
    use DoctrineEntity;

    /**
     * @var string
     * The class name of the Entity.
     */
    private $entityName = RollGroup::class;

    /**
     * findBySchoolYear
     *
     * @param int $gibbonSchoolYearID
     * @return mixed
     */
    public function findBySchoolYear(int $gibbonSchoolYearID)
    {
        $studentCount = 0;

        $result = $this->getRepository()->createQueryBuilder('rg')
            ->select(['rg.gibbonRollGroupID','rg.name', 'rg.nameShort', 'sp.name AS space','rg.website', 'tutor1.gibbonPersonID as gibbonPersonIDTutor', 'tutor2.gibbonPersonID AS gibbonPersonIDTutor2', 'tutor3.gibbonPersonID as gibbonPersonIDTutor3'])
            ->leftJoin('rg.gibbonSpaceID', 'sp')
            ->leftJoin('rg.gibbonSchoolYearID', 'sy')
            ->leftJoin('rg.gibbonPersonIDTutor', 'tutor1')
            ->leftJoin('rg.gibbonPersonIDTutor2', 'tutor2')
            ->leftJoin('rg.gibbonPersonIDTutor3', 'tutor3')
            ->where('sy.gibbonSchoolYearID = :schoolYear')
            ->setParameter('schoolYear', $gibbonSchoolYearID)
            ->groupBy('rg.gibbonRollGroupID')
            ->orderBy('rg.name')
            ->getQuery()
            ->getArrayResult();
        return $result;
    }

    public function getStudentCount($gibbonRollGroupID)
    {
        $rollGroup = $this->find($gibbonRollGroupID);

        $result = $this->getRepository(StudentEnrolment::class)->createQueryBuilder('se')
            ->select(['COUNT(p.gibbonPersonID)'])
            ->leftJoin('se.gibbonPersonID', 'p')
            ->where('se.gibbonRollGroupID = :rollGroup')
            ->setParameter('rollGroup', $rollGroup)
            ->getQuery()
            ->getSingleScalarResult();
        return intval($result);
    }

    /**
     * findTutors
     *
     * @param $gibbonRollGroupID
     * @return mixed
     */
    public function findTutors($gibbonRollGroupID)
    {
        $rollGroup = $this->find($gibbonRollGroupID);
        $data = [];
        if ($rollGroup->getGibbonPersonIDTutor())
            $data[] = $rollGroup->getGibbonPersonIDTutor()->getGibbonPersonID();
        if ($rollGroup->getGibbonPersonIDTutor2())
            $data[] = $rollGroup->getGibbonPersonIDTutor2()->getGibbonPersonID();
        if ($rollGroup->getGibbonPersonIDTutor3())
            $data[] = $rollGroup->getGibbonPersonIDTutor3()->getGibbonPersonID();
        return $this->getRepository(Person::class)->createQueryBuilder('p')
            ->where('p.gibbonPersonID IN (:people)')
            ->setParameter('people', $data, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getArrayResult();

    }
}