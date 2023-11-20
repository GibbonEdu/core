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

namespace Gibbon\Forms\Builder;

use Gibbon\Forms\Form;
use Gibbon\Forms\Builder\View\FormViewInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;

abstract class AbstractFormView implements FormViewInterface 
{
    abstract public function getName() : string;

    abstract public function configure(Form $form);

    abstract public function display(Form $form, FormDataInterface $data);

    public function getHeading() : string
    {
        return '';
    }

    public function getDescription() : string
    {
        return '';
    }

    public function getViewName()
    {
        return str_replace(__NAMESPACE__ . '\\View\\', '', get_called_class());
    }

    public function getResultName()
    {
        return str_replace('View', 'Result', $this->getViewName());
    }

    public function configureEdit(Form $form, FormDataInterface $data, string $id) {} 

    public function configureAccept(Form $form, FormDataInterface $data, string $id) {}
}
