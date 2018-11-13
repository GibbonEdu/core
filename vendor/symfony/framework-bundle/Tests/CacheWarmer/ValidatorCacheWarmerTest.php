<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\ValidatorCacheWarmer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\ValidatorBuilder;

class ValidatorCacheWarmerTest extends TestCase
{
    public function testWarmUp()
    {
        $validatorBuilder = new ValidatorBuilder();
        $validatorBuilder->addXmlMapping(__DIR__.'/../Fixtures/Validation/Resources/person.xml');
        $validatorBuilder->addYamlMapping(__DIR__.'/../Fixtures/Validation/Resources/author.yml');
        $validatorBuilder->addMethodMapping('loadValidatorMetadata');
        $validatorBuilder->enableAnnotationMapping();

        $file = sys_get_temp_dir().'/cache-validator.php';
        @unlink($file);

        $fallbackPool = new ArrayAdapter();

        $warmer = new ValidatorCacheWarmer($validatorBuilder, $file, $fallbackPool);
        $warmer->warmUp(\dirname($file));

        $this->assertFileExists($file);

        $arrayPool = new PhpArrayAdapter($file, new NullAdapter());

        $this->assertTrue($arrayPool->getItem('Symfony.Bundle.FrameworkBundle.Tests.Fixtures.Validation.Person')->isHit());
        $this->assertTrue($arrayPool->getItem('Symfony.Bundle.FrameworkBundle.Tests.Fixtures.Validation.Author')->isHit());

        $values = $fallbackPool->getValues();

        $this->assertInternalType('array', $values);
        $this->assertCount(2, $values);
        $this->assertArrayHasKey('Symfony.Bundle.FrameworkBundle.Tests.Fixtures.Validation.Person', $values);
        $this->assertArrayHasKey('Symfony.Bundle.FrameworkBundle.Tests.Fixtures.Validation.Author', $values);
    }

    public function testWarmUpWithAnnotations()
    {
        $validatorBuilder = new ValidatorBuilder();
        $validatorBuilder->addYamlMapping(__DIR__.'/../Fixtures/Validation/Resources/categories.yml');
        $validatorBuilder->enableAnnotationMapping();

        $file = sys_get_temp_dir().'/cache-validator-with-annotations.php';
        @unlink($file);

        $fallbackPool = new ArrayAdapter();

        $warmer = new ValidatorCacheWarmer($validatorBuilder, $file, $fallbackPool);
        $warmer->warmUp(\dirname($file));

        $this->assertFileExists($file);

        $arrayPool = new PhpArrayAdapter($file, new NullAdapter());

        $item = $arrayPool->getItem('Symfony.Bundle.FrameworkBundle.Tests.Fixtures.Validation.Category');
        $this->assertTrue($item->isHit());

        $this->assertInstanceOf(ClassMetadata::class, $item->get());

        $values = $fallbackPool->getValues();

        $this->assertInternalType('array', $values);
        $this->assertCount(2, $values);
        $this->assertArrayHasKey('Symfony.Bundle.FrameworkBundle.Tests.Fixtures.Validation.Category', $values);
        $this->assertArrayHasKey('Symfony.Bundle.FrameworkBundle.Tests.Fixtures.Validation.SubCategory', $values);
    }

    public function testWarmUpWithoutLoader()
    {
        $validatorBuilder = new ValidatorBuilder();

        $file = sys_get_temp_dir().'/cache-validator-without-loaders.php';
        @unlink($file);

        $fallbackPool = new ArrayAdapter();

        $warmer = new ValidatorCacheWarmer($validatorBuilder, $file, $fallbackPool);
        $warmer->warmUp(\dirname($file));

        $this->assertFileExists($file);

        $values = $fallbackPool->getValues();

        $this->assertInternalType('array', $values);
        $this->assertCount(0, $values);
    }
}
