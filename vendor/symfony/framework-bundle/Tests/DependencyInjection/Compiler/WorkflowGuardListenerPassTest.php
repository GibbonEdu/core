<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\WorkflowGuardListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WorkflowGuardListenerPassTest extends TestCase
{
    private $container;
    private $compilerPass;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compilerPass = new WorkflowGuardListenerPass();
    }

    public function testNoExeptionIfParameterIsNotSet()
    {
        $this->compilerPass->process($this->container);

        $this->assertFalse($this->container->hasParameter('workflow.has_guard_listeners'));
    }

    public function testNoExeptionIfAllDependenciesArePresent()
    {
        $this->container->setParameter('workflow.has_guard_listeners', true);
        $this->container->register('security.token_storage', TokenStorageInterface::class);
        $this->container->register('security.authorization_checker', AuthorizationCheckerInterface::class);
        $this->container->register('security.authentication.trust_resolver', AuthenticationTrustResolverInterface::class);
        $this->container->register('security.role_hierarchy', RoleHierarchy::class);
        $this->container->register('validator', ValidatorInterface::class);

        $this->compilerPass->process($this->container);

        $this->assertFalse($this->container->hasParameter('workflow.has_guard_listeners'));
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage The "security.token_storage" service is needed to be able to use the workflow guard listener.
     */
    public function testExceptionIfTheTokenStorageServiceIsNotPresent()
    {
        $this->container->setParameter('workflow.has_guard_listeners', true);
        $this->container->register('security.authorization_checker', AuthorizationCheckerInterface::class);
        $this->container->register('security.authentication.trust_resolver', AuthenticationTrustResolverInterface::class);
        $this->container->register('security.role_hierarchy', RoleHierarchy::class);

        $this->compilerPass->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage The "security.authorization_checker" service is needed to be able to use the workflow guard listener.
     */
    public function testExceptionIfTheAuthorizationCheckerServiceIsNotPresent()
    {
        $this->container->setParameter('workflow.has_guard_listeners', true);
        $this->container->register('security.token_storage', TokenStorageInterface::class);
        $this->container->register('security.authentication.trust_resolver', AuthenticationTrustResolverInterface::class);
        $this->container->register('security.role_hierarchy', RoleHierarchy::class);

        $this->compilerPass->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage The "security.authentication.trust_resolver" service is needed to be able to use the workflow guard listener.
     */
    public function testExceptionIfTheAuthenticationTrustResolverServiceIsNotPresent()
    {
        $this->container->setParameter('workflow.has_guard_listeners', true);
        $this->container->register('security.token_storage', TokenStorageInterface::class);
        $this->container->register('security.authorization_checker', AuthorizationCheckerInterface::class);
        $this->container->register('security.role_hierarchy', RoleHierarchy::class);

        $this->compilerPass->process($this->container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage The "security.role_hierarchy" service is needed to be able to use the workflow guard listener.
     */
    public function testExceptionIfTheRoleHierarchyServiceIsNotPresent()
    {
        $this->container->setParameter('workflow.has_guard_listeners', true);
        $this->container->register('security.token_storage', TokenStorageInterface::class);
        $this->container->register('security.authorization_checker', AuthorizationCheckerInterface::class);
        $this->container->register('security.authentication.trust_resolver', AuthenticationTrustResolverInterface::class);

        $this->compilerPass->process($this->container);
    }
}
