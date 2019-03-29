<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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
use Gibbon\Forms\View\FormView;
use League\Container\ServiceProvider\AbstractServiceProvider;

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
    }
}
