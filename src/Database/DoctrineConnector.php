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
 * Time: 08:15
 */
namespace Gibbon\Database;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Gibbon\Repository\RepositoryFactory;
use League\Container\Container;

/**
 * Class DoctrineConnector
 * @package Gibbon\Database
 */
class DoctrineConnector
{
    /**
     * getConnection
     *
     * @return array
     */
    public function getConnection()
    {
        if (file_exists(dirname(__FILE__). '/../../config.php') && filesize(dirname(__FILE__). '/../../config.php') > 0) {
            include dirname(__FILE__). '/../../config.php';
        } else {
            return [];
        }

        return [
            'driver'    =>  'pdo_mysql',    //  This is constant at the moment
            'host'      =>  $databaseServer,
            'port'      =>  isset($databasePort)? $databasePort : null,
            'dbname'    =>  $databaseName,
            'user'      =>  $databaseUsername,
            'password'  =>  $databasePassword,
            'charset'   =>  'utf8mb4',
            'default_table_options' => [
                'charset'   =>  'utf8mb4',
                'collate'   =>  'utf8mb4_unicode_ci',
            ]
        ];
    }

    /**
     * getConfiguration
     *
     * @return \Doctrine\ORM\Configuration
     */
    public function getConfiguration()
    {
        $isDevMode = true;
        $config = Setup::createYAMLMetadataConfiguration([realpath(__DIR__."/../../src/Entity")], $isDevMode);

        $factory = new RepositoryFactory();
        $config->setRepositoryFactory($factory);
        return $config;
    }

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * getEntityManager
     *
     * @param bool|null $org
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    public function getEntityManager(bool $org = null)
    {
        if (empty($this->entityManager) && (is_null($org) || $org))
            $this->entityManager = EntityManager::create($this->getConnection(), $this->getConfiguration());
        return $this->entityManager;
    }

    /**
     * @var Container
     */
    private $container;

    /**
     * DoctrineConnector constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * getRepository
     *
     * @param string $className
     * @return \Doctrine\ORM\EntityRepository
     * @throws \Doctrine\ORM\ORMException
     */
    public function getRepository(string $className)
    {
        return $this->getEntityManager()->getRepository($className);
    }
}