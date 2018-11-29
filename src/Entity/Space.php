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
 * Time: 11:07
 */
namespace Gibbon\Entity;
/**
 * Class Space
 * @package Gibbon\Entity
 */
class Space
{
    /**
     * @var int|null
     */
    private $gibbonSpaceID;
    /**
     * @return int|null
     */
    public function getGibbonSpaceID()
    {
        return $this->gibbonSpaceID;
    }
    /**
     * @param int|null $gibbonSpaceID
     * @return Space
     */
    public function setGibbonSpaceID($gibbonSpaceID)
    {
        $this->gibbonSpaceID = $gibbonSpaceID;
        return $this;
    }
    /**
     * @var string|null
     */
    private $name;
    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param null|string $name
     * @return Space
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
