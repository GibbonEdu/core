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
 * Time: 11:42
 */
namespace Gibbon\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gibbon\Database\DoctrineConnector;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class RepositoryFactory
 * @package Gibbon\Repository
 */
class RepositoryFactory implements \Doctrine\ORM\Repository\RepositoryFactory
{
    /** @var ObjectRepository[] */
    private $managedRepositories = [];
    /**
     * {@inheritdoc}
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $metadata            = $entityManager->getClassMetadata($entityName);
        $repositoryServiceId = $metadata->customRepositoryClassName;

        $customRepositoryName = $metadata->customRepositoryClassName;

        if ($customRepositoryName !== null) {
            // fetch from the container

            // if not in the container but the class/id implements the interface, throw an error
            if (is_a($customRepositoryName, ServiceEntityRepositoryInterface::class, true)) {
                // can be removed when DoctrineBundle requires Symfony 3.3
                if ($this->container === null) {
                    throw new \RuntimeException(sprintf('Support for loading entities from the service container only works for Symfony 3.3 or higher. Upgrade your version of Symfony or make sure your "%s" class does not implement "%s"', $customRepositoryName, ServiceEntityRepositoryInterface::class));
                }

                throw new \RuntimeException(sprintf('The "%s" entity repository implements "%s", but its service could not be found. Make sure the service exists and is tagged with "%s".', $customRepositoryName, ServiceEntityRepositoryInterface::class, ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG));
            }

            if (! class_exists($customRepositoryName)) {
                throw new \RuntimeException(sprintf('The "%s" entity has a repositoryClass set to "%s", but this is not a valid class. Check your class naming. If this is meant to be a service id, make sure this service exists and is tagged with "%s".', $metadata->name, $customRepositoryName, ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG));
            }

            // allow the repository to be created below
        }

        return $this->getOrCreateRepository($entityManager, $metadata);
    }

    /**
     * getOrCreateRepository
     *
     * @param EntityManagerInterface $entityManager
     * @param ClassMetadata $metadata
     * @return ObjectRepository
     */
    private function getOrCreateRepository(EntityManagerInterface $entityManager, ClassMetadata $metadata)
    {
        $repositoryHash = $metadata->getName() . spl_object_hash($entityManager);
        if (isset($this->managedRepositories[$repositoryHash])) {
            return $this->managedRepositories[$repositoryHash];
        }


        $repositoryClassName = $metadata->customRepositoryClassName ?: $entityManager->getConfiguration()->getDefaultRepositoryClassName();

        return $this->managedRepositories[$repositoryHash] = new $repositoryClassName($entityManager, $metadata);
    }
}
