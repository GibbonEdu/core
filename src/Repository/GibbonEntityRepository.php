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
 * Time: 12:34
 */
namespace Gibbon\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class GibbonEntityRepository
 * @package Gibbon\Repository
 */
class GibbonEntityRepository extends EntityRepository
{
    /**
     * GibbonEntityRepository constructor.
     * @param EntityManagerInterface $entityManager
     * @param $entityClass
     */
    public function __construct(EntityManagerInterface $entityManager, $entityClass)
    {
        parent::__construct($entityManager, new ClassMetadata($entityClass));
    }
}