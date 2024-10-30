<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Gibbon\Services;

use Gibbon\Forms\Form;
use Gibbon\Forms\FormFactory;
use Gibbon\Forms\FormFactoryInterface;
use Gibbon\Forms\View\FormView;
use Gibbon\Forms\View\FormRendererInterface;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\DataTableView;
use Gibbon\Tables\View\PaginatedView;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Gibbon\Tables\View\DetailsView;
use Twig\Environment;

/**
 * DI Container Services for rendering Views
 *
 * @version v18
 * @since   v18
 */
class ViewServiceProvider extends AbstractServiceProvider
{
    /**
     * The provides array is a way to let the container know that a service
     * is provided by this service provider. Every service that is registered
     * via this service provider must have an alias added to this array or
     * it will be ignored.
     *
     * @var array
     */
    protected $provides = [
        Form::class,
        FormRendererInterface::class,
        FormFactoryInterface::class,
        DataTable::class,
        DataTableView::class,
        PaginatedView::class,
        Environment::class,
        DetailsView::class,
    ];

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register()
    {
        $container = $this->getContainer();
        
        $container->add(Form::class, function () {
            $factory = new FormFactory();
            $renderer = new FormView($this->getContainer()->get('twig'));

            return (new Form($factory, $renderer))->setClass('w-full smallIntBorder standardForm');
        });

        $container->add(FormRendererInterface::class, function () {
            return new FormView($this->getContainer()->get('twig'));
        });

        $container->add(FormFactoryInterface::class, function () {
            return new FormFactory();
        });
        
        $container->add(DataTable::class, function () use ($container) {
            $renderer = new DataTableView($container->get('twig'));

            return new DataTable($renderer);
        });

        $container->add(DataTableView::class, function () use ($container) {
            return new DataTableView($container->get('twig'));
        });

        $container->add(PaginatedView::class, function () use ($container) {
            return new PaginatedView($container->get('twig'));
        });

        $container->add(DetailsView::class, function () use ($container) {
            return new DetailsView($container->get('twig'));
        });

        $container->share(Environment::class, function () {
            return $this->getContainer()->get('twig');
        });
    }
}
