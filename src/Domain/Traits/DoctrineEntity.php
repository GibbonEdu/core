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
 * Time: 13:18
 */
namespace Gibbon\Domain\Traits;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Trait DoctrineEntity
 * @package Gibbon\Domain\Traits
 */
trait DoctrineEntity
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var string
     */
    private $prefix = 'Gibbon';
    /**
     * DoctrineEntity constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    /**
     * getRepository
     *
     * @param null $className
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository($className = null)
    {
        if (is_null($className))
            $className = $this->getEntityName();
        return $this->getEntityManager()->getRepository($className);
    }
    /**
     * getEntityManager
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
    /**
     * getEntityName
     *
     * @return mixed
     */
    public function getEntityName()
    {
        if (! property_exists($this, 'entityName') || empty($this->entityName))
            trigger_error(sprintf('The entity has not been defined for %s', __CLASS__), E_USER_ERROR);
        if (! class_exists($this->entityName))
            trigger_error(sprintf('The entity %s defined in %s does not exist.', $this->entityName, __CLASS__), E_USER_ERROR);
        return $this->entityName;
    }
    /**
     * @var object|null
     */
    private $entity;
    /**
     * find
     *
     * @param $id
     * @return null|object
     */
    public function find($id)
    {
        if (empty($this->getEntity()) || $id !== $this->getId())
            $this->setEntity($this->getRepository()->find($id));
        return $this->getEntity();
    }
    /**
     * @return null|object
     */
    public function getEntity()
    {
        return $this->entity;
    }
    /**
     * @param null|object $entity
     * @return DoctrineEntity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }
    /**
     * getId
     *
     * @return null|int
     */
    public function getId()
    {
        $name = 'get' . $this->prefix . ucfirst(basename($this->getEntityName())). 'ID';
        if ($this->getEntity() && method_exists($this->getEntity(), $name))
            return $this->getEntity()->$name();
        return null;
    }
}