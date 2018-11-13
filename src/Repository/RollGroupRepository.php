<?php
/**
 * Created by PhpStorm.
 *
 * This file is part of the Gibbon.
 *
 * (c) Craig Rayner <craig@craigrayner.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * User: craig
 * Date: 13/11/2018
 * Time: 09:13
 */
namespace Gibbon\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Gibbon\Entity\RollGroup;

/**
 * Class RollGroupRepository
 * @package Gibbon\Repository
 */
class RollGroupRepository extends GibbonEntityRepository
{
    /**
     * RollGroupRepository constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, RollGroup::class);
    }
}