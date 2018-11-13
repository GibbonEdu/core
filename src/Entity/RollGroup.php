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
 * Date: 23/06/2018
 * Time: 07:42
 */
namespace Gibbon\Entity;

/**
 * Class RollGroup
 * @package App\Entity
 */
class RollGroup
{
    /**
     * @var integer|null
     */
    private $gibbonRollGroupID;

    /**
     * @return int|null
     */
    public function getGibbonRollGroupID(): ?int
    {
        return $this->gibbonRollGroupID;
    }

    /**
     * @param int|null $gibbonRollGroupID
     * @return RollGroup
     */
    public function setGibbonRollGroupID(?int $gibbonRollGroupID): RollGroup
    {
        $this->gibbonRollGroupID = $gibbonRollGroupID;
        return $this;
    }

    /**
     * @var string|null
     */
    private $name;

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     * @return RollGroup
     */
    public function setName(?string $name): RollGroup
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @var string|null
     */
    private $nameShort;

    /**
     * @return null|string
     */
    public function getNameShort(): ?string
    {
        return $this->nameShort;
    }

    /**
     * @param null|string $nameShort
     * @return RollGroup
     */
    public function setNameShort(?string $nameShort): RollGroup
    {
        $this->nameShort = $nameShort;
        return $this;
    }

    /**
     * @var boolean
     */
    private $attendance;

    /**
     * @return bool
     */
    public function isAttendance(): bool
    {
        return $this->attendance ? true : false ;
    }

    /**
     * @param bool $attendance
     * @return RollGroup
     */
    public function setAttendance(bool $attendance): RollGroup
    {
        $this->attendance = $attendance ? true : false ;
        return $this;
    }

    /**
     * @var string|null
     */
    private $website;

    /**
     * @return null|string
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @param null|string $website
     * @return RollGroup
     */
    public function setWebsite(?string $website): RollGroup
    {
        $this->website = $website;
        return $this;
    }

    /**
     * @var SchoolYear
     */
    private $gibbonSchoolYearID;

    /**
     * @return SchoolYear|null
     */
    public function getGibbonSchoolYearID(): ?SchoolYear
    {
        return $this->gibbonSchoolYearID;
    }

    /**
     * @param SchoolYear|null $gibbonSchoolYearID
     * @return RollGroup
     */
    public function setGibbonSchoolYearID(?SchoolYear $gibbonSchoolYearID): RollGroup
    {
        $this->gibbonSchoolYearID = $gibbonSchoolYearID;
        return $this;
    }

    /**
     * @var RollGroup
     */
    private $gibbonRollGroupIDNext;

    /**
     * @return RollGroup|null
     */
    public function getGibbonRollGroupIDNext(): ?RollGroup
    {
        return $this->gibbonRollGroupIDNext;
    }

    /**
     * @param RollGroup|null $gibbonRollGroupIDNext
     * @return RollGroup
     */
    public function setGibbonRollGroupIDNext(?RollGroup $gibbonRollGroupIDNext): RollGroup
    {
        $this->gibbonRollGroupIDNext = $gibbonRollGroupIDNext;
        return $this;
    }

    /**
     * @var Space|null
     */
    private $gibbonSpaceID;

    /**
     * @return Space|null
     */
    public function getGibbonSpaceID(): ?Space
    {
        return $this->gibbonSpaceID;
    }

    /**
     * @param Space|null $gibbonSpaceID
     * @return RollGroup
     */
    public function setGibbonSpaceID(?Space $gibbonSpaceID): RollGroup
    {
        $this->gibbonSpaceID = $gibbonSpaceID;
        return $this;
    }

    /**
     * @var Person|null
     */
    private $gibbonPersonIDTutor;

    /**
     * @return Person|null
     */
    public function getGibbonPersonIDTutor()
    {
        return $this->gibbonPersonIDTutor;
    }

    /**
     * @param Person|null $gibbonPersonIDTutor
     */
    public function setGibbonPersonIDTutor($gibbonPersonIDTutor)
    {
        $this->gibbonPersonIDTutor = $gibbonPersonIDTutor;
    }

    /**
     * @var Person|null
     */
    private $gibbonPersonIDTutor2;

    /**
     * @return Person|null
     */
    public function getGibbonPersonIDTutor2()
    {
        return $this->gibbonPersonIDTutor2;
    }

    /**
     * @param Person|null $gibbonPersonIDTutor2
     */
    public function setGibbonPersonIDTutor2($gibbonPersonIDTutor2)
    {
        $this->gibbonPersonIDTutor2 = $gibbonPersonIDTutor2;
    }

    /**
     * @var Person|null
     */
    private $gibbonPersonIDTutor3;

    /**
     * @return Person|null
     */
    public function getGibbonPersonIDTutor3()
    {
        return $this->gibbonPersonIDTutor3;
    }

    /**
     * @param Person|null $gibbonPersonIDTutor3
     */
    public function setGibbonPersonIDTutor3($gibbonPersonIDTutor3)
    {
        $this->gibbonPersonIDTutor3 = $gibbonPersonIDTutor3;
    }

    /**
     * @var Person|null
     */
    private $gibbonPersonIDEA;

    /**
     * @return Person|null
     */
    public function getGibbonPersonIDEA()
    {
        return $this->gibbonPersonIDEA;
    }

    /**
     * @param Person|null $gibbonPersonIDEA
     */
    public function setGibbonPersonIDEA($gibbonPersonIDEA)
    {
        $this->gibbonPersonIDEA = $gibbonPersonIDEA;
    }

    /**
     * @var Person|null
     */
    private $gibbonPersonIDEA2;

    /**
     * @return Person|null
     */
    public function getGibbonPersonIDEA2()
    {
        return $this->gibbonPersonIDEA2;
    }

    /**
     * @param Person|null $gibbonPersonIDEA2
     */
    public function setGibbonPersonIDEA2($gibbonPersonIDEA2)
    {
        $this->gibbonPersonIDEA2 = $gibbonPersonIDEA2;
    }

    /**
     * @var Person|null
     */
    private $gibbonPersonIDEA3;

    /**
     * @return Person|null
     */
    public function getGibbonPersonIDEA3()
    {
        return $this->gibbonPersonIDEA3;
    }

    /**
     * @param Person|null $gibbonPersonIDEA3
     */
    public function setGibbonPersonIDEA3($gibbonPersonIDEA3)
    {
        $this->gibbonPersonIDEA3 = $gibbonPersonIDEA3;
    }
}