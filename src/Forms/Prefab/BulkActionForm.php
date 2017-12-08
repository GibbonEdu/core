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

namespace Gibbon\Forms\Prefab;

use Gibbon\Forms\Form;
use Gibbon\Forms\FormFactory;
use Gibbon\Forms\FormRenderer;

/**
 * BulkActionForm
 *
 * @version v15
 * @since   v15
 */
class BulkActionForm extends Form
{
    public static function create($id, $action, $method = 'post', $class = 'smallIntBorder fullWidth standardForm')
    {
        $factory = FormFactory::create();
        $renderer = FormRenderer::create();

        $renderer->setWrapper('form', 'div');
        $renderer->setWrapper('row', 'div');
        $renderer->setWrapper('cell', 'fieldset');

        $form = new BulkActionForm($factory, $renderer, $action, $method);

        $form->setID($id);
        $form->setClass($class);

        $form->addConfirmation(__('Are you sure you wish to process this action? It cannot be undone.'));
        $form->addHiddenValue('address', $_GET['q']);

        return $form;
    }

    public function addActionRow($id = '')
    {
        $row = $this->addRow($id)->setClass('right');
        return $row->addColumn()->addClass('inline right');
    }
}
