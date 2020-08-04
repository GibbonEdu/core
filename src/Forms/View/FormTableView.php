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

namespace Gibbon\Forms\View;

use Gibbon\Forms\Form;
use Gibbon\Forms\ValidatableInterface;
use Gibbon\Forms\View\FormRendererInterface;
use Gibbon\Forms\View\FormView;

/**
 * FormTableView
 *
 * @version v21
 * @since   v21
 */
class FormTableView extends FormView implements FormRendererInterface
{
    /**
     * Transform a Form object into HTML and javascript output using a Twig template.
     * @param   Form    $form
     * @return  string
     */
    public function renderForm(Form $form)
    {
        $this->addData('form', $form);
        $this->addData('javascript', $this->getInlineJavascript($form));
        $this->addData('totalColumns', $this->getColumnCount($form));

        return $this->render('components/formTable.twig.html');
    }
}
