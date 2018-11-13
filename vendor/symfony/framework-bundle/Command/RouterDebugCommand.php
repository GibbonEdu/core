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

use Symfony\Bundle\FrameworkBundle\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * A console command for retrieving information about routes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 *
 * @final
 */
class RouterDebugCommand extends Command
{
    protected static $defaultName = 'debug:router';
    private $router;

    public function __construct(RouterInterface $router)
    {
        parent::__construct();

        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputArgument('name', InputArgument::OPTIONAL, 'A route name'),
                new InputOption('show-controllers', null, InputOption::VALUE_NONE, 'Show assigned controllers in overview'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt'),
                new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw route(s)'),
            ))
            ->setDescription('Displays current routes for an application')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> displays the configured routes:

  <info>php %command.full_name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException When route does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $helper = new DescriptorHelper();
        $routes = $this->router->getRouteCollection();

        if ($name) {
            if (!($route = $routes->get($name)) && $matchingRoutes = $this->findRouteNameContaining($name, $routes)) {
                $default = 1 === \count($matchingRoutes) ? $matchingRoutes[0] : null;
                $name = $io->choice('Select one of the matching routes', $matchingRoutes, $default);
                $route = $routes->get($name);
            }

            if (!$route) {
                throw new InvalidArgumentException(sprintf('The route "%s" does not exist.', $name));
            }

            $helper->describe($io, $route, array(
                'format' => $input->getOption('format'),
                'raw_text' => $input->getOption('raw'),
                'name' => $name,
                'output' => $io,
            ));
        } else {
            $helper->describe($io, $routes, array(
                'format' => $input->getOption('format'),
                'raw_text' => $input->getOption('raw'),
                'show_controllers' => $input->getOption('show-controllers'),
                'output' => $io,
            ));
        }
    }

    private function findRouteNameContaining(string $name, RouteCollection $routes): array
    {
        $foundRoutesNames = array();
        foreach ($routes as $routeName => $route) {
            if (false !== stripos($routeName, $name)) {
                $foundRoutesNames[] = $routeName;
            }
        }

        return $foundRoutesNames;
    }
}
