<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Doctrine\Tests\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bridge\Doctrine\Test\TestRepositoryFactory;
use Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity;
use Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity2;
use Symfony\Bridge\Doctrine\Tests\Fixtures\CompositeObjectNoToStringIdEntity;
use Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity;
use Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNullableNameEntity;
use Symfony\Bridge\Doctrine\Tests\Fixtures\Employee;
use Symfony\Bridge\Doctrine\Tests\Fixtures\Person;
use Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity;
use Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdNoToStringEntity;
use Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdStringWrapperNameEntity;
use Symfony\Bridge\Doctrine\Tests\Fixtures\Type\StringWrapper;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UniqueEntityValidatorTest extends ConstraintValidatorTestCase
{
    const EM_NAME = 'foo';

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ObjectRepository
     */
    protected $repository;

    protected $repositoryFactory;

    protected function setUp()
    {
        $this->repositoryFactory = new TestRepositoryFactory();

        $config = DoctrineTestHelper::createTestConfiguration();
        $config->setRepositoryFactory($this->repositoryFactory);

        if (!Type::hasType('string_wrapper')) {
            Type::addType('string_wrapper', 'Symfony\Bridge\Doctrine\Tests\Fixtures\Type\StringWrapperType');
        }

        $this->em = DoctrineTestHelper::createTestEntityManager($config);
        $this->registry = $this->createRegistryMock($this->em);
        $this->createSchema($this->em);

        parent::setUp();
    }

    protected function createRegistryMock(ObjectManager $em = null)
    {
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')->getMock();
        $registry->expects($this->any())
                 ->method('getManager')
                 ->with($this->equalTo(self::EM_NAME))
                 ->will($this->returnValue($em));

        return $registry;
    }

    protected function createRepositoryMock()
    {
        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->setMethods(array('findByCustom', 'find', 'findAll', 'findOneBy', 'findBy', 'getClassName'))
            ->getMock()
        ;

        return $repository;
    }

    protected function createEntityManagerMock($repositoryMock)
    {
        $em = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->getMock()
        ;
        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repositoryMock))
        ;

        $classMetadata = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')->getMock();
        $classMetadata
            ->expects($this->any())
            ->method('hasField')
            ->will($this->returnValue(true))
        ;
        $reflParser = $this->getMockBuilder('Doctrine\Common\Reflection\StaticReflectionParser')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $refl = $this->getMockBuilder('Doctrine\Common\Reflection\StaticReflectionProperty')
            ->setConstructorArgs(array($reflParser, 'property-name'))
            ->setMethods(array('getValue'))
            ->getMock()
        ;
        $refl
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue(true))
        ;
        $classMetadata->reflFields = array('name' => $refl);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($classMetadata))
        ;

        return $em;
    }

    protected function createValidator()
    {
        return new UniqueEntityValidator($this->registry);
    }

    private function createSchema(ObjectManager $em)
    {
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(array(
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdNoToStringEntity'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNameEntity'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\DoubleNullableNameEntity'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\CompositeIntIdEntity'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\AssociationEntity2'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\Person'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\Employee'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\CompositeObjectNoToStringIdEntity'),
            $em->getClassMetadata('Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdStringWrapperNameEntity'),
        ));
    }

    /**
     * This is a functional test as there is a large integration necessary to get the validator working.
     */
    public function testValidateUniqueness()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
        ));

        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Foo');

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->validator->validate($entity2, $constraint);

        $this->buildViolation('myMessage')
            ->atPath('property.path.name')
            ->setParameter('{{ value }}', '"Foo"')
            ->setInvalidValue($entity2)
            ->setCause(array($entity1))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }

    public function testValidateCustomErrorPath()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
            'errorPath' => 'bar',
        ));

        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Foo');

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->validate($entity2, $constraint);

        $this->buildViolation('myMessage')
            ->atPath('property.path.bar')
            ->setParameter('{{ value }}', '"Foo"')
            ->setInvalidValue($entity2)
            ->setCause(array($entity1))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }

    public function testValidateUniquenessWithNull()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
        ));

        $entity1 = new SingleIntIdEntity(1, null);
        $entity2 = new SingleIntIdEntity(2, null);

        $this->em->persist($entity1);
        $this->em->persist($entity2);
        $this->em->flush();

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateUniquenessWithIgnoreNullDisabled()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name', 'name2'),
            'em' => self::EM_NAME,
            'ignoreNull' => false,
        ));

        $entity1 = new DoubleNameEntity(1, 'Foo', null);
        $entity2 = new DoubleNameEntity(2, 'Foo', null);

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->validator->validate($entity2, $constraint);

        $this->buildViolation('myMessage')
            ->atPath('property.path.name')
            ->setParameter('{{ value }}', '"Foo"')
            ->setInvalidValue('Foo')
            ->setCause(array($entity1))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testAllConfiguredFieldsAreCheckedOfBeingMappedByDoctrineWithIgnoreNullEnabled()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name', 'name2'),
            'em' => self::EM_NAME,
            'ignoreNull' => true,
        ));

        $entity1 = new SingleIntIdEntity(1, null);

        $this->validator->validate($entity1, $constraint);
    }

    public function testNoValidationIfFirstFieldIsNullAndNullValuesAreIgnored()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name', 'name2'),
            'em' => self::EM_NAME,
            'ignoreNull' => true,
        ));

        $entity1 = new DoubleNullableNameEntity(1, null, 'Foo');
        $entity2 = new DoubleNullableNameEntity(2, null, 'Foo');

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->validator->validate($entity2, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateUniquenessWithValidCustomErrorPath()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name', 'name2'),
            'em' => self::EM_NAME,
            'errorPath' => 'name2',
        ));

        $entity1 = new DoubleNameEntity(1, 'Foo', 'Bar');
        $entity2 = new DoubleNameEntity(2, 'Foo', 'Bar');

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->validator->validate($entity2, $constraint);

        $this->buildViolation('myMessage')
            ->atPath('property.path.name2')
            ->setParameter('{{ value }}', '"Bar"')
            ->setInvalidValue('Bar')
            ->setCause(array($entity1))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }

    public function testValidateUniquenessUsingCustomRepositoryMethod()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
            'repositoryMethod' => 'findByCustom',
        ));

        $repository = $this->createRepositoryMock();
        $repository->expects($this->once())
            ->method('findByCustom')
            ->will($this->returnValue(array()))
        ;
        $this->em = $this->createEntityManagerMock($repository);
        $this->registry = $this->createRegistryMock($this->em);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $entity1 = new SingleIntIdEntity(1, 'foo');

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateUniquenessWithUnrewoundArray()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
            'repositoryMethod' => 'findByCustom',
        ));

        $entity = new SingleIntIdEntity(1, 'foo');

        $repository = $this->createRepositoryMock();
        $repository->expects($this->once())
            ->method('findByCustom')
            ->will(
                $this->returnCallback(function () use ($entity) {
                    $returnValue = array(
                        $entity,
                    );
                    next($returnValue);

                    return $returnValue;
                })
            )
        ;
        $this->em = $this->createEntityManagerMock($repository);
        $this->registry = $this->createRegistryMock($this->em);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider resultTypesProvider
     */
    public function testValidateResultTypes($entity1, $result)
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
            'repositoryMethod' => 'findByCustom',
        ));

        $repository = $this->createRepositoryMock();
        $repository->expects($this->once())
            ->method('findByCustom')
            ->will($this->returnValue($result))
        ;
        $this->em = $this->createEntityManagerMock($repository);
        $this->registry = $this->createRegistryMock($this->em);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();
    }

    public function resultTypesProvider()
    {
        $entity = new SingleIntIdEntity(1, 'foo');

        return array(
            array($entity, array($entity)),
            array($entity, new \ArrayIterator(array($entity))),
            array($entity, new ArrayCollection(array($entity))),
        );
    }

    public function testAssociatedEntity()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('single'),
            'em' => self::EM_NAME,
        ));

        $entity1 = new SingleIntIdEntity(1, 'foo');
        $associated = new AssociationEntity();
        $associated->single = $entity1;
        $associated2 = new AssociationEntity();
        $associated2->single = $entity1;

        $this->em->persist($entity1);
        $this->em->persist($associated);
        $this->em->flush();

        $this->validator->validate($associated, $constraint);

        $this->assertNoViolation();

        $this->em->persist($associated2);
        $this->em->flush();

        $this->validator->validate($associated2, $constraint);

        $this->buildViolation('myMessage')
            ->atPath('property.path.single')
            ->setParameter('{{ value }}', 'foo')
            ->setInvalidValue($entity1)
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->setCause(array($associated, $associated2))
            ->assertRaised();
    }

    public function testValidateUniquenessNotToStringEntityWithAssociatedEntity()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('single'),
            'em' => self::EM_NAME,
        ));

        $entity1 = new SingleIntIdNoToStringEntity(1, 'foo');
        $associated = new AssociationEntity2();
        $associated->single = $entity1;
        $associated2 = new AssociationEntity2();
        $associated2->single = $entity1;

        $this->em->persist($entity1);
        $this->em->persist($associated);
        $this->em->flush();

        $this->validator->validate($associated, $constraint);

        $this->assertNoViolation();

        $this->em->persist($associated2);
        $this->em->flush();

        $this->validator->validate($associated2, $constraint);

        $expectedValue = 'object("Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdNoToStringEntity") identified by (id => 1)';

        $this->buildViolation('myMessage')
            ->atPath('property.path.single')
            ->setParameter('{{ value }}', $expectedValue)
            ->setInvalidValue($entity1)
            ->setCause(array($associated, $associated2))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }

    public function testAssociatedEntityWithNull()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('single'),
            'em' => self::EM_NAME,
            'ignoreNull' => false,
        ));

        $associated = new AssociationEntity();
        $associated->single = null;

        $this->em->persist($associated);
        $this->em->flush();

        $this->validator->validate($associated, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateUniquenessWithArrayValue()
    {
        $repository = $this->createRepositoryMock();
        $this->repositoryFactory->setRepository($this->em, 'Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity', $repository);

        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('phoneNumbers'),
            'em' => self::EM_NAME,
            'repositoryMethod' => 'findByCustom',
        ));

        $entity1 = new SingleIntIdEntity(1, 'foo');
        $entity1->phoneNumbers[] = 123;

        $repository->expects($this->once())
            ->method('findByCustom')
            ->will($this->returnValue(array($entity1)))
        ;

        $this->em->persist($entity1);
        $this->em->flush();

        $entity2 = new SingleIntIdEntity(2, 'bar');
        $entity2->phoneNumbers[] = 123;
        $this->em->persist($entity2);
        $this->em->flush();

        $this->validator->validate($entity2, $constraint);

        $this->buildViolation('myMessage')
            ->atPath('property.path.phoneNumbers')
            ->setParameter('{{ value }}', 'array')
            ->setInvalidValue(array(123))
            ->setCause(array($entity1))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @expectedExceptionMessage Object manager "foo" does not exist.
     */
    public function testDedicatedEntityManagerNullObject()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
        ));

        $this->em = null;
        $this->registry = $this->createRegistryMock($this->em);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $entity = new SingleIntIdEntity(1, null);

        $this->validator->validate($entity, $constraint);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @expectedExceptionMessage Unable to find the object manager associated with an entity of class "Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity"
     */
    public function testEntityManagerNullObject()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            // no "em" option set
        ));

        $this->em = null;
        $this->registry = $this->createRegistryMock($this->em);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $entity = new SingleIntIdEntity(1, null);

        $this->validator->validate($entity, $constraint);
    }

    public function testValidateUniquenessOnNullResult()
    {
        $repository = $this->createRepositoryMock();
        $repository
             ->method('find')
             ->will($this->returnValue(null))
        ;

        $this->em = $this->createEntityManagerMock($repository);
        $this->registry = $this->createRegistryMock($this->em);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
        ));

        $entity = new SingleIntIdEntity(1, null);

        $this->em->persist($entity);
        $this->em->flush();

        $this->validator->validate($entity, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateInheritanceUniqueness()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
            'entityClass' => 'Symfony\Bridge\Doctrine\Tests\Fixtures\Person',
        ));

        $entity1 = new Person(1, 'Foo');
        $entity2 = new Employee(2, 'Foo');

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->validator->validate($entity2, $constraint);

        $this->buildViolation('myMessage')
            ->atPath('property.path.name')
            ->setInvalidValue('Foo')
            ->setCode('23bd9dbf-6b9b-41cd-a99e-4844bcf3077f')
            ->setCause(array($entity1))
            ->setParameters(array('{{ value }}' => '"Foo"'))
            ->assertRaised();
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @expectedExceptionMessage The "Symfony\Bridge\Doctrine\Tests\Fixtures\SingleStringIdEntity" entity repository does not support the "Symfony\Bridge\Doctrine\Tests\Fixtures\Person" entity. The entity should be an instance of or extend "Symfony\Bridge\Doctrine\Tests\Fixtures\SingleStringIdEntity".
     */
    public function testInvalidateRepositoryForInheritance()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
            'entityClass' => 'Symfony\Bridge\Doctrine\Tests\Fixtures\SingleStringIdEntity',
        ));

        $entity = new Person(1, 'Foo');
        $this->validator->validate($entity, $constraint);
    }

    public function testValidateUniquenessWithCompositeObjectNoToStringIdEntity()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('objectOne', 'objectTwo'),
            'em' => self::EM_NAME,
        ));

        $objectOne = new SingleIntIdNoToStringEntity(1, 'foo');
        $objectTwo = new SingleIntIdNoToStringEntity(2, 'bar');

        $this->em->persist($objectOne);
        $this->em->persist($objectTwo);
        $this->em->flush();

        $entity = new CompositeObjectNoToStringIdEntity($objectOne, $objectTwo);

        $this->em->persist($entity);
        $this->em->flush();

        $newEntity = new CompositeObjectNoToStringIdEntity($objectOne, $objectTwo);

        $this->validator->validate($newEntity, $constraint);

        $expectedValue = 'object("Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdNoToStringEntity") identified by (id => 1)';

        $this->buildViolation('myMessage')
            ->atPath('property.path.objectOne')
            ->setParameter('{{ value }}', $expectedValue)
            ->setInvalidValue($objectOne)
            ->setCause(array($entity))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }

    public function testValidateUniquenessWithCustomDoctrineTypeValue()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
        ));

        $existingEntity = new SingleIntIdStringWrapperNameEntity(1, new StringWrapper('foo'));

        $this->em->persist($existingEntity);
        $this->em->flush();

        $newEntity = new SingleIntIdStringWrapperNameEntity(2, new StringWrapper('foo'));

        $this->validator->validate($newEntity, $constraint);

        $expectedValue = 'object("Symfony\Bridge\Doctrine\Tests\Fixtures\Type\StringWrapper")';

        $this->buildViolation('myMessage')
            ->atPath('property.path.name')
            ->setParameter('{{ value }}', $expectedValue)
            ->setInvalidValue($existingEntity->name)
            ->setCause(array($existingEntity))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }

    /**
     * This is a functional test as there is a large integration necessary to get the validator working.
     */
    public function testValidateUniquenessCause()
    {
        $constraint = new UniqueEntity(array(
            'message' => 'myMessage',
            'fields' => array('name'),
            'em' => self::EM_NAME,
        ));

        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Foo');

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->em->persist($entity1);
        $this->em->flush();

        $this->validator->validate($entity1, $constraint);

        $this->assertNoViolation();

        $this->validator->validate($entity2, $constraint);

        $this->buildViolation('myMessage')
            ->atPath('property.path.name')
            ->setParameter('{{ value }}', '"Foo"')
            ->setInvalidValue($entity2)
            ->setCause(array($entity1))
            ->setCode(UniqueEntity::NOT_UNIQUE_ERROR)
            ->assertRaised();
    }
}
