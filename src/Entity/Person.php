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
 * Time: 11:08
 */
namespace Gibbon\Entity;

/**
 * Class Person
 * @package Gibbon\Entity
 */
class Person
{
    /**
     * @var int|null
     */
    private $gibbonPersonID;
    /**
     * @return int|null
     */
    public function getGibbonPersonID()
    {
        return $this->gibbonPersonID;
    }
    /**
     * @param int|null $gibbonPersonID
     * @return Person
     */
    public function setGibbonPersonID($gibbonPersonID)
    {
        $this->gibbonPersonID = $gibbonPersonID;
        return $this;
    }
    /**
     * @var string|null
     */
    private $title;
    /**
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }
    /**
     * @param null|string $title
     * @return Person
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    /**
     * @var string|null
     */
    private $surname;
    /**
     * @return null|string
     */
    public function getSurname()
    {
        return $this->surname;
    }
    /**
     * @param null|string $surname
     * @return Person
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;
        return $this;
    }
    /**
     * @var null|string
     */
    private $preferredName;
    /**
     * @return null|string
     */
    public function getPreferredName()
    {
        return $this->preferredName;
    }
    /**
     * @param null|string $preferredName
     * @return Person
     */
    public function setPreferredName($preferredName)
    {
        $this->preferredName = $preferredName;
        return $this;
    }
}
