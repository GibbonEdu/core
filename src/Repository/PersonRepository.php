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
 * Time: 14:24
 */
namespace Gibbon\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Gibbon\Entity\Person;

/**
 * Class PersonRepository
 * @package Gibbon\Repository
 */
class PersonRepository extends GibbonEntityRepository
{
    /**
     * PersonRepository constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Person::class);
    }
}