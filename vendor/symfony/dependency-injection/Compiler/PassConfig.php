<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Compiler Pass Configuration.
 *
 * This class has a default configuration embedded.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PassConfig
{
    const TYPE_AFTER_REMOVING = 'afterRemoving';
    const TYPE_BEFORE_OPTIMIZATION = 'beforeOptimization';
    const TYPE_BEFORE_REMOVING = 'beforeRemoving';
    const TYPE_OPTIMIZE = 'optimization';
    const TYPE_REMOVE = 'removing';

    private $mergePass;
    private $afterRemovingPasses = array();
    private $beforeOptimizationPasses = array();
    private $beforeRemovingPasses = array();
    private $optimizationPasses;
    private $removingPasses;

    public function __construct()
    {
        $this->mergePass = new MergeExtensionConfigurationPass();

        $this->beforeOptimizationPasses = array(
            100 => array(
                $resolveClassPass = new ResolveClassPass(),
                new ResolveInstanceofConditionalsPass(),
            ),
        );

        $this->optimizationPasses = array(array(
            new ExtensionCompilerPass(),
            new ResolveDefinitionTemplatesPass(),
            new ServiceLocatorTagPass(),
            new DecoratorServicePass(),
            new ResolveParameterPlaceHoldersPass(),
            new ResolveFactoryClassPass(),
            new FactoryReturnTypePass($resolveClassPass),
            new CheckDefinitionValidityPass(),
            new RegisterServiceSubscribersPass(),
            new ResolveNamedArgumentsPass(),
            $autowirePass = new AutowirePass(false),
            new ResolveServiceSubscribersPass(),
            new ResolveReferencesToAliasesPass(),
            new ResolveInvalidReferencesPass(),
            new AnalyzeServiceReferencesPass(true),
            new CheckCircularReferencesPass(),
            new CheckReferenceValidityPass(),
            new CheckArgumentsValidityPass(),
        ));

        $this->removingPasses = array(array(
            new RemovePrivateAliasesPass(),
            new ReplaceAliasByActualDefinitionPass(),
            new RemoveAbstractDefinitionsPass(),
            new RepeatedPass(array(
                new AnalyzeServiceReferencesPass(),
                $inlinedServicePass = new InlineServiceDefinitionsPass(),
                new AnalyzeServiceReferencesPass(),
                new RemoveUnusedDefinitionsPass(),
            )),
            new AutowireExceptionPass($autowirePass, $inlinedServicePass),
            new CheckExceptionOnInvalidReferenceBehaviorPass(),
        ));
    }

    /**
     * Returns all passes in order to be processed.
     *
     * @return CompilerPassInterface[]
     */
    public function getPasses()
    {
        return array_merge(
            array($this->mergePass),
            $this->getBeforeOptimizationPasses(),
            $this->getOptimizationPasses(),
            $this->getBeforeRemovingPasses(),
            $this->getRemovingPasses(),
            $this->getAfterRemovingPasses()
        );
    }

    /**
     * Adds a pass.
     *
     * @param CompilerPassInterface $pass     A Compiler pass
     * @param string                $type     The pass type
     * @param int                   $priority Used to sort the passes
     *
     * @throws InvalidArgumentException when a pass type doesn't exist
     */
    public function addPass(CompilerPassInterface $pass, $type = self::TYPE_BEFORE_OPTIMIZATION/*, int $priority = 0*/)
    {
        if (func_num_args() >= 3) {
            $priority = func_get_arg(2);
        } else {
            if (__CLASS__ !== get_class($this)) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a third `int $priority = 0` argument in version 4.0. Not defining it is deprecated since 3.2.', __METHOD__), E_USER_DEPRECATED);
                }
            }

            $priority = 0;
        }

        $property = $type.'Passes';
        if (!isset($this->$property)) {
            throw new InvalidArgumentException(sprintf('Invalid type "%s".', $type));
        }

        $passes = &$this->$property;

        if (!isset($passes[$priority])) {
            $passes[$priority] = array();
        }
        $passes[$priority][] = $pass;
    }

    /**
     * Gets all passes for the AfterRemoving pass.
     *
     * @return CompilerPassInterface[]
     */
    public function getAfterRemovingPasses()
    {
        return $this->sortPasses($this->afterRemovingPasses);
    }

    /**
     * Gets all passes for the BeforeOptimization pass.
     *
     * @return CompilerPassInterface[]
     */
    public function getBeforeOptimizationPasses()
    {
        return $this->sortPasses($this->beforeOptimizationPasses);
    }

    /**
     * Gets all passes for the BeforeRemoving pass.
     *
     * @return CompilerPassInterface[]
     */
    public function getBeforeRemovingPasses()
    {
        return $this->sortPasses($this->beforeRemovingPasses);
    }

    /**
     * Gets all passes for the Optimization pass.
     *
     * @return CompilerPassInterface[]
     */
    public function getOptimizationPasses()
    {
        return $this->sortPasses($this->optimizationPasses);
    }

    /**
     * Gets all passes for the Removing pass.
     *
     * @return CompilerPassInterface[]
     */
    public function getRemovingPasses()
    {
        return $this->sortPasses($this->removingPasses);
    }

    /**
     * Gets the Merge pass.
     *
     * @return CompilerPassInterface
     */
    public function getMergePass()
    {
        return $this->mergePass;
    }

    /**
     * Sets the Merge Pass.
     *
     * @param CompilerPassInterface $pass The merge pass
     */
    public function setMergePass(CompilerPassInterface $pass)
    {
        $this->mergePass = $pass;
    }

    /**
     * Sets the AfterRemoving passes.
     *
     * @param CompilerPassInterface[] $passes
     */
    public function setAfterRemovingPasses(array $passes)
    {
        $this->afterRemovingPasses = array($passes);
    }

    /**
     * Sets the BeforeOptimization passes.
     *
     * @param CompilerPassInterface[] $passes
     */
    public function setBeforeOptimizationPasses(array $passes)
    {
        $this->beforeOptimizationPasses = array($passes);
    }

    /**
     * Sets the BeforeRemoving passes.
     *
     * @param CompilerPassInterface[] $passes
     */
    public function setBeforeRemovingPasses(array $passes)
    {
        $this->beforeRemovingPasses = array($passes);
    }

    /**
     * Sets the Optimization passes.
     *
     * @param CompilerPassInterface[] $passes
     */
    public function setOptimizationPasses(array $passes)
    {
        $this->optimizationPasses = array($passes);
    }

    /**
     * Sets the Removing passes.
     *
     * @param CompilerPassInterface[] $passes
     */
    public function setRemovingPasses(array $passes)
    {
        $this->removingPasses = array($passes);
    }

    /**
     * Sort passes by priority.
     *
     * @param array $passes CompilerPassInterface instances with their priority as key
     *
     * @return CompilerPassInterface[]
     */
    private function sortPasses(array $passes)
    {
        if (0 === count($passes)) {
            return array();
        }

        krsort($passes);

        // Flatten the array
        return call_user_func_array('array_merge', $passes);
    }
}
