<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\Dumper\GraphvizDumper;
use Symfony\Component\Workflow\Dumper\StateMachineGraphvizDumper;
use Symfony\Component\Workflow\Marking;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class WorkflowDumpCommand extends ContainerAwareCommand
{
    public function isEnabled()
    {
        return $this->getContainer()->has('workflow.registry');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('workflow:dump')
            ->setDefinition(array(
                new InputArgument('name', InputArgument::REQUIRED, 'A workflow name'),
                new InputArgument('marking', InputArgument::IS_ARRAY, 'A marking (a list of places)'),
            ))
            ->setDescription('Dump a workflow')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command dumps the graphical representation of a
workflow in DOT format

    %command.full_name% <workflow name> | dot -Tpng > workflow.png

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $serviceId = $input->getArgument('name');
        if ($container->has('workflow.'.$serviceId)) {
            $workflow = $container->get('workflow.'.$serviceId);
            $dumper = new GraphvizDumper();
        } elseif ($container->has('state_machine.'.$serviceId)) {
            $workflow = $container->get('state_machine.'.$serviceId);
            $dumper = new StateMachineGraphvizDumper();
        } else {
            throw new \InvalidArgumentException(sprintf('No service found for "workflow.%1$s" nor "state_machine.%1$s".', $serviceId));
        }

        $marking = new Marking();

        foreach ($input->getArgument('marking') as $place) {
            $marking->mark($place);
        }

        $output->writeln($dumper->dump($workflow->getDefinition(), $marking));
    }
}
