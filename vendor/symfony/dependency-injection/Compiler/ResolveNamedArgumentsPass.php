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

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Resolves named arguments to their corresponding numeric index.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResolveNamedArgumentsPass extends AbstractRecursivePass
{
    /**
     * {@inheritdoc}
     */
    protected function processValue($value, $isRoot = false)
    {
        if (!$value instanceof Definition) {
            return parent::processValue($value, $isRoot);
        }

        $calls = $value->getMethodCalls();
        $calls[] = array('__construct', $value->getArguments());

        foreach ($calls as $i => $call) {
            list($method, $arguments) = $call;
            $parameters = null;
            $resolvedArguments = array();

            foreach ($arguments as $key => $argument) {
                if (is_int($key)) {
                    $resolvedArguments[$key] = $argument;
                    continue;
                }
                if ('' === $key || '$' !== $key[0]) {
                    throw new InvalidArgumentException(sprintf('Invalid key "%s" found in arguments of method "%s()" for service "%s": only integer or $named arguments are allowed.', $key, $method, $this->currentId));
                }

                if (null === $parameters) {
                    $r = $this->getReflectionMethod($value, $method);
                    $class = $r instanceof \ReflectionMethod ? $r->class : $this->currentId;
                    $parameters = $r->getParameters();
                }

                foreach ($parameters as $j => $p) {
                    if ($key === '$'.$p->name) {
                        $resolvedArguments[$j] = $argument;

                        continue 2;
                    }
                }

                throw new InvalidArgumentException(sprintf('Unable to resolve service "%s": method "%s()" has no argument named "%s". Check your service definition.', $this->currentId, $class !== $this->currentId ? $class.'::'.$method : $method, $key));
            }

            if ($resolvedArguments !== $call[1]) {
                ksort($resolvedArguments);
                $calls[$i][1] = $resolvedArguments;
            }
        }

        list(, $arguments) = array_pop($calls);

        if ($arguments !== $value->getArguments()) {
            $value->setArguments($arguments);
        }
        if ($calls !== $value->getMethodCalls()) {
            $value->setMethodCalls($calls);
        }

        return parent::processValue($value, $isRoot);
    }
}
