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

/**
 * DeleteForm
 *
 * @version v15
 * @since   v15
 */
class DeleteForm extends Form
{
    public static function createForm($action, $confirmation = false, $submit = true)
    {
        $form = parent::create('deleteRecord', $action);
        $form->addHiddenValue('address', $_GET['q']);

        foreach ($_GET as $key => $value) {
            $form->addHiddenValue($key, $value);
        }

        $row = $form->addRow();
            $col = $row->addColumn();
            $col->addContent(__('Are you sure you want to delete this record?'))->wrap('<strong>', '</strong>');
            $col->addContent(__('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!'))
                ->wrap('<span style="color: #cc0000"><i>', '</i></span>');

        if ($confirmation) {
            $row = $form->addRow();
            $row->addLabel('confirm', sprintf(__('Type %1$s to confirm'), __('DELETE')));
            $row->addTextField('confirm')
                ->isRequired()
                ->addValidation(
                    'Validate.Inclusion',
                    'within: [\''.__('DELETE').'\'], failureMessage: "'.__('Please enter the text exactly as it is displayed to confirm this action.').'", caseSensitive: false')
                ->addValidationOption('onlyOnSubmit: true');
        }

        if ($submit) {
            $form->addRow()->addConfirmSubmit();
        }

        return $form;
    }
}
