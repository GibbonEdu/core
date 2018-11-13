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
 * Time: 15:13
 */

namespace Gibbon\Entity;

/**
 * Class StudentEnrolment
 * @package Gibbon\Entity
 */
class StudentEnrolment
{
    /**
     * @var int|null
     */
    private $gibbonStudentEnrolmentID;

    /**
     * @return int|null
     */
    public function getGibbonStudentEnrolmentID()
    {
        return $this->gibbonStudentEnrolmentID;
    }

    /**
     * @param int|null $gibbonStudentEnrolmentID
     * @return StudentEnrolment
     */
    public function setGibbonStudentEnrolmentID($gibbonStudentEnrolmentID)
    {
        $this->gibbonStudentEnrolmentID = $gibbonStudentEnrolmentID;
        return $this;
    }

    /**
     * @var Person|null
     */
    private $gibbonPersonID;

    /**
     * @return Person|null
     */
    public function getGibbonPersonID()
    {
        return $this->gibbonPersonID;
    }

    /**
     * @param Person|null $gibbonPersonID
     * @return StudentEnrolment
     */
    public function setGibbonPersonID($gibbonPersonID)
    {
        $this->gibbonPersonID = $gibbonPersonID;
        return $this;
    }

    /**
     * @var RollGroup|null
     */
    private $gibbonRollGroupID;

    /**
     * @return RollGroup|null
     */
    public function getGibbonRollGroupID()
    {
        return $this->gibbonRollGroupID;
    }

    /**
     * @param RollGroup|null $gibbonRollGroupID
     * @return StudentEnrolment
     */
    public function setGibbonRollGroupID($gibbonRollGroupID)
    {
        $this->gibbonRollGroupID = $gibbonRollGroupID;
        return $this;
    }
}