<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\Tests\Form\ChoiceList;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\ChoiceList\DoctrineChoiceLoader;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;
use Symfony\Bridge\Doctrine\Form\ChoiceList\IdReader;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DoctrineChoiceLoaderTest extends TestCase
{
    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $om;

    /**
     * @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var string
     */
    private $class;

    /**
     * @var IdReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $idReader;

    /**
     * @var EntityLoaderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectLoader;

    /**
     * @var \stdClass
     */
    private $obj1;

    /**
     * @var \stdClass
     */
    private $obj2;

    /**
     * @var \stdClass
     */
    private $obj3;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface')->getMock();
        $this->om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')->getMock();
        $this->repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')->getMock();
        $this->class = 'stdClass';
        $this->idReader = $this->getMockBuilder('Symfony\Bridge\Doctrine\Form\ChoiceList\IdReader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectLoader = $this->getMockBuilder('Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface')->getMock();
        $this->obj1 = (object) array('name' => 'A');
        $this->obj2 = (object) array('name' => 'B');
        $this->obj3 = (object) array('name' => 'C');

        $this->om->expects($this->any())
            ->method('getRepository')
            ->with($this->class)
            ->willReturn($this->repository);

        $this->om->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->class)
            ->willReturn(new ClassMetadata($this->class));
    }

    public function testLoadChoiceList()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader
        );

        $choices = array($this->obj1, $this->obj2, $this->obj3);
        $value = function () {};
        $choiceList = new ArrayChoiceList($choices, $value);

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($choices);

        $this->assertEquals($choiceList, $loader->loadChoiceList($value));

        // no further loads on subsequent calls

        $this->assertEquals($choiceList, $loader->loadChoiceList($value));
    }

    /**
     * @group legacy
     */
    public function testLegacyLoadChoiceList()
    {
        $factory = $this->getMockBuilder('Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface')->getMock();
        $loader = new DoctrineChoiceLoader(
            $factory,
            $this->om,
            $this->class,
            $this->idReader
        );

        $choices = array($this->obj1, $this->obj2, $this->obj3);
        $value = function () {};
        $choiceList = new ArrayChoiceList($choices, $value);

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($choices);

        $factory->expects($this->never())
            ->method('createListFromChoices');

        $this->assertEquals($choiceList, $loaded = $loader->loadChoiceList($value));

        // no further loads on subsequent calls

        $this->assertSame($loaded, $loader->loadChoiceList($value));
    }

    public function testLoadChoiceListUsesObjectLoaderIfAvailable()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader,
            $this->objectLoader
        );

        $choices = array($this->obj1, $this->obj2, $this->obj3);
        $choiceList = new ArrayChoiceList($choices);

        $this->repository->expects($this->never())
            ->method('findAll');

        $this->objectLoader->expects($this->once())
            ->method('getEntities')
            ->willReturn($choices);

        $this->assertEquals($choiceList, $loaded = $loader->loadChoiceList());

        // no further loads on subsequent calls

        $this->assertSame($loaded, $loader->loadChoiceList());
    }

    public function testLoadValuesForChoices()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader
        );

        $choices = array($this->obj1, $this->obj2, $this->obj3);

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($choices);

        $this->assertSame(array('1', '2'), $loader->loadValuesForChoices(array($this->obj2, $this->obj3)));

        // no further loads on subsequent calls

        $this->assertSame(array('1', '2'), $loader->loadValuesForChoices(array($this->obj2, $this->obj3)));
    }

    public function testLoadValuesForChoicesDoesNotLoadIfEmptyChoices()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader
        );

        $this->repository->expects($this->never())
            ->method('findAll');

        $this->assertSame(array(), $loader->loadValuesForChoices(array()));
    }

    public function testLoadValuesForChoicesDoesNotLoadIfSingleIntId()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader
        );

        $this->idReader->expects($this->any())
            ->method('isSingleId')
            ->willReturn(true);

        $this->repository->expects($this->never())
            ->method('findAll');

        $this->idReader->expects($this->any())
            ->method('getIdValue')
            ->with($this->obj2)
            ->willReturn('2');

        $this->assertSame(array('2'), $loader->loadValuesForChoices(array($this->obj2)));
    }

    public function testLoadValuesForChoicesLoadsIfSingleIntIdAndValueGiven()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader
        );

        $choices = array($this->obj1, $this->obj2, $this->obj3);
        $value = function (\stdClass $object) { return $object->name; };

        $this->idReader->expects($this->any())
            ->method('isSingleId')
            ->willReturn(true);

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($choices);

        $this->assertSame(array('B'), $loader->loadValuesForChoices(
            array($this->obj2),
            $value
        ));
    }

    public function testLoadValuesForChoicesDoesNotLoadIfValueIsIdReader()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader
        );

        $value = array($this->idReader, 'getIdValue');

        $this->idReader->expects($this->any())
            ->method('isSingleId')
            ->willReturn(true);

        $this->repository->expects($this->never())
            ->method('findAll');

        $this->idReader->expects($this->any())
            ->method('getIdValue')
            ->with($this->obj2)
            ->willReturn('2');

        $this->assertSame(array('2'), $loader->loadValuesForChoices(
            array($this->obj2),
            $value
        ));
    }

    public function testLoadChoicesForValues()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader
        );

        $choices = array($this->obj1, $this->obj2, $this->obj3);

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($choices);

        $this->assertSame(array($this->obj2, $this->obj3), $loader->loadChoicesForValues(array('1', '2')));

        // no further loads on subsequent calls

        $this->assertSame(array($this->obj2, $this->obj3), $loader->loadChoicesForValues(array('1', '2')));
    }

    public function testLoadChoicesForValuesDoesNotLoadIfEmptyValues()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader
        );

        $this->repository->expects($this->never())
            ->method('findAll');

        $this->assertSame(array(), $loader->loadChoicesForValues(array()));
    }

    public function testLoadChoicesForValuesLoadsOnlyChoicesIfSingleIntId()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader,
            $this->objectLoader
        );

        $choices = array($this->obj2, $this->obj3);

        $this->idReader->expects($this->any())
            ->method('isSingleId')
            ->willReturn(true);

        $this->idReader->expects($this->any())
            ->method('getIdField')
            ->willReturn('idField');

        $this->repository->expects($this->never())
            ->method('findAll');

        $this->objectLoader->expects($this->once())
            ->method('getEntitiesByIds')
            ->with('idField', array(4 => '3', 7 => '2'))
            ->willReturn($choices);

        $this->idReader->expects($this->any())
            ->method('getIdValue')
            ->willReturnMap(array(
                array($this->obj2, '2'),
                array($this->obj3, '3'),
            ));

        $this->assertSame(
            array(4 => $this->obj3, 7 => $this->obj2),
            $loader->loadChoicesForValues(array(4 => '3', 7 => '2')
        ));
    }

    public function testLoadChoicesForValuesLoadsAllIfSingleIntIdAndValueGiven()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader
        );

        $choices = array($this->obj1, $this->obj2, $this->obj3);
        $value = function (\stdClass $object) { return $object->name; };

        $this->idReader->expects($this->any())
            ->method('isSingleId')
            ->willReturn(true);

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($choices);

        $this->assertSame(array($this->obj2), $loader->loadChoicesForValues(
            array('B'),
            $value
        ));
    }

    public function testLoadChoicesForValuesLoadsOnlyChoicesIfValueIsIdReader()
    {
        $loader = new DoctrineChoiceLoader(
            $this->om,
            $this->class,
            $this->idReader,
            $this->objectLoader
        );

        $choices = array($this->obj2, $this->obj3);
        $value = array($this->idReader, 'getIdValue');

        $this->idReader->expects($this->any())
            ->method('isSingleId')
            ->willReturn(true);

        $this->idReader->expects($this->any())
            ->method('getIdField')
            ->willReturn('idField');

        $this->repository->expects($this->never())
            ->method('findAll');

        $this->objectLoader->expects($this->once())
            ->method('getEntitiesByIds')
            ->with('idField', array('2'))
            ->willReturn($choices);

        $this->idReader->expects($this->any())
            ->method('getIdValue')
            ->willReturnMap(array(
                array($this->obj2, '2'),
                array($this->obj3, '3'),
            ));

        $this->assertSame(array($this->obj2), $loader->loadChoicesForValues(array('2'), $value));
    }
}
