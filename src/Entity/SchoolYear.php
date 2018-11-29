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
 * Time: 11:05
 */
namespace Gibbon\Entity;

/**
 * Class SchoolYear
 * @package Gibbon\Entity
 */
class SchoolYear
{
    /**
     * @var int|null
     */
    private $gibbonSchoolYearID;
    /**
     * getGibbonSchoolYearID
     *
     * @return int|null
     */
    public function getGibbonSchoolYearID()
    {
        return $this->gibbonSchoolYearID;
    }
    /**
     * setGibbonSchoolYearID
     *
     * @param $gibbonSchoolYearID
     * @return $this
     */
    public function setGibbonSchoolYearID($gibbonSchoolYearID)
    {
        $this->gibbonSchoolYearID = $gibbonSchoolYearID;
        return $this;
    }
}
