<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Config\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\ConfigCacheFactory;

class ConfigCacheFactoryTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid type for callback argument. Expected callable, but got "object".
     */
    public function testCacheWithInvalidCallback()
    {
        $cacheFactory = new ConfigCacheFactory(true);

        $cacheFactory->cache('file', new \stdClass());
    }
}
