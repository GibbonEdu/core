<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

/**
 * Provides utility functions needed in tests.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DoctrineTestHelper
{
    /**
     * Returns an entity manager for testing.
     *
     * @param Configuration|null $config
     *
     * @return EntityManager
     */
    public static function createTestEntityManager(Configuration $config = null)
    {
        if (!extension_loaded('pdo_sqlite')) {
            TestCase::markTestSkipped('Extension pdo_sqlite is required.');
        }

        if (null === $config) {
            $config = self::createTestConfiguration();
        }

        $params = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        return EntityManager::create($params, $config);
    }

    /**
     * @return Configuration
     */
    public static function createTestConfiguration()
    {
        $config = new Configuration();
        $config->setEntityNamespaces(array('SymfonyTestsDoctrine' => 'Symfony\Bridge\Doctrine\Tests\Fixtures'));
        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setProxyNamespace('SymfonyTests\Doctrine');
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        $config->setQueryCacheImpl(new ArrayCache());
        $config->setMetadataCacheImpl(new ArrayCache());

        return $config;
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
