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

use Symfony\Component\DependencyInjection\Argument\ArgumentInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Inline service definitions where this is possible.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class InlineServiceDefinitionsPass extends AbstractRecursivePass implements RepeatablePassInterface
{
    private $cloningIds = array();

    /**
     * {@inheritdoc}
     */
    public function setRepeatedPass(RepeatedPass $repeatedPass)
    {
        // no-op for BC
    }

    /**
     * {@inheritdoc}
     */
    protected function processValue($value, $isRoot = false)
    {
        if ($value instanceof ArgumentInterface) {
            // Reference found in ArgumentInterface::getValues() are not inlineable
            return $value;
        }

        if ($value instanceof Definition && $this->cloningIds) {
            if ($value->isShared()) {
                return $value;
            }
            $value = clone $value;
        }

        if (!$value instanceof Reference || !$this->container->hasDefinition($id = (string) $value)) {
            return parent::processValue($value, $isRoot);
        }

        $definition = $this->container->getDefinition($id);

        if (!$this->isInlineableDefinition($id, $definition, $this->container->getCompiler()->getServiceReferenceGraph())) {
            return $value;
        }

        $this->container->log($this, sprintf('Inlined service "%s" to "%s".', $id, $this->currentId));

        if ($definition->isShared()) {
            return $definition;
        }

        if (isset($this->cloningIds[$id])) {
            $ids = array_keys($this->cloningIds);
            $ids[] = $id;

            throw new ServiceCircularReferenceException($id, \array_slice($ids, array_search($id, $ids)));
        }

        $this->cloningIds[$id] = true;
        try {
            return $this->processValue($definition);
        } finally {
            unset($this->cloningIds[$id]);
        }
    }

    /**
     * Checks if the definition is inlineable.
     *
     * @return bool If the definition is inlineable
     */
    private function isInlineableDefinition($id, Definition $definition, ServiceReferenceGraph $graph)
    {
        if ($definition->getErrors() || $definition->isDeprecated() || $definition->isLazy() || $definition->isSynthetic()) {
            return false;
        }

        if (!$definition->isShared()) {
            foreach ($graph->getNode($id)->getInEdges() as $edge) {
                if ($edge->isWeak()) {
                    return false;
                }
            }

            return true;
        }

        if ($definition->isPublic()) {
            return false;
        }

        if (!$graph->hasNode($id)) {
            return true;
        }

        if ($this->currentId == $id) {
            return false;
        }

        $ids = array();
        foreach ($graph->getNode($id)->getInEdges() as $edge) {
            if ($edge->isWeak()) {
                return false;
            }
            $ids[] = $edge->getSourceNode()->getId();
        }

        if (\count(array_unique($ids)) > 1) {
            return false;
        }

        if (\count($ids) > 1 && \is_array($factory = $definition->getFactory()) && ($factory[0] instanceof Reference || $factory[0] instanceof Definition)) {
            return false;
        }

        return !$ids || $this->container->getDefinition($ids[0])->isShared();
    }
}
