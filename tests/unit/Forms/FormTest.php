<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Forms;

use PHPUnit\Framework\TestCase;
use Gibbon\Forms\View\FormRendererInterface;
use Gibbon\Services\ViewServiceProvider;
use League\Container\Container;
use Gibbon\Forms\View\FormView;

/**
 * @covers Form
 */
class FormTest extends TestCase
{
    /**
     * Container instance to generate the Form instance.
     *
     * @var Container $container
     */
    private $container;

    /**
     * Backup of global container instance.
     *
     * @var Container $container
     */
    private $containerBackup = null;

    /**
     * Provide a container environment for the tests to conduct.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $container = new Container();

        $container->share('twig', function () {
            $absolutePath = realpath(__DIR__ . '/../../../');
            $loader = new \Twig\Loader\FilesystemLoader($absolutePath.'/resources/templates');

            $twig = new \Twig\Environment($loader);
            $twig->addFunction(new \Twig\TwigFunction('__', function ($string, $domain = null) {
                return $string;
            }));

            return $twig;
        });

        $service = new ViewServiceProvider();
        $service->setContainer($container);
        $service->register();

        $this->container = $container;
    }

    public function testCanBeCreatedStatically()
    {
        $this->useOwnGlobalContainer();
        $this->assertInstanceOf(
            Form::class,
            Form::create('testID', 'testAction')
        );
        $this->restoreGlobalContainer();
    }

    public function testCanOutputToHtml()
    {
        /** @var Form */
        $form = $this->container->get(Form::class)
            ->setID('testID')
            ->setAction('testAction');
        $output = $form->getOutput();

        $this->assertTrue(stripos($output, '<form') !== false);
        $this->assertTrue(stripos($output, '</form>') !== false);
    }

    public function testCanAddRow()
    {
        $form = $this->container->get(Form::class)
            ->setID('testID')
            ->setAction('testAction');

        $this->assertTrue(count($form->getRows()) == 0);
        $row = $form->addRow();
        $row->addContent('Test');

        $this->assertTrue(count($form->getRows()) > 0);
        $this->assertSame($row, $form->getRow());
    }

    public function testCanAddHiddenValue()
    {
        $form = $this->container->get(Form::class)
            ->setID('testID')
            ->setAction('testAction');

        $this->assertTrue(count($form->getHiddenValues()) == 0);
        $form->addHiddenValue('name', 'value');

        $this->assertTrue(count($form->getHiddenValues()) > 0);
    }

    public function testCanAddTrigger()
    {
        $form = $this->container->get(Form::class)
            ->setID('testID')
            ->setAction('testAction');

        $this->assertTrue(count($form->getTriggers()) == 0);
        $form->addTrigger('selector', 'trigger');

        $this->assertTrue(count($form->getTriggers()) > 0);
    }

    public function testCanSetFactory()
    {
        $form = $this->container->get(Form::class)
            ->setID('testID')
            ->setAction('testAction');

        $newFactory = FormFactory::create();
        $form->setFactory($newFactory);

        $this->assertSame($newFactory, $form->getFactory());
    }

    public function testCanSetRenderer()
    {
        $form = $this->container->get(Form::class)
            ->setID('testID')
            ->setAction('testAction');

        $newRenderer = $this->createMock(FormView::class);
        $form->setRenderer($newRenderer);

        $this->assertSame($newRenderer, $form->getRenderer());
    }

    public function testEachNewFormHasBasicAttributes()
    {
        $form = $this->container->get(Form::class)
            ->setID('testID')
            ->setAction('testAction');

        $this->assertSame('testID', $form->getID());
        $this->assertSame('testAction', $form->getAction());
        $this->assertSame('post', $form->getMethod());
    }

    /**
     * Use the container in setUp as global container.
     * For testing with Form::create().
     *
     * @return void
     */
    private function useOwnGlobalContainer()
    {
        global $container;
        if (!isset($container)) {
            $this->containerBackup = $container;
        }
        $container = $this->container; // Use the custom container.
    }

    /**
     * Restore container replaced by useOwnGlobalContainer().
     *
     * @return void
     */
    private function restoreGlobalContainer()
    {
        if (empty($this->containerBackup)) {
            return; // do nothing
        }
        global $container;
        $container = $this->containerBackup;
    }
}
