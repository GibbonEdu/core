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

namespace Gibbon\Forms\View;

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Forms\ValidatableInterface;
use Gibbon\Forms\View\FormRendererInterface;

/**
 * FormView
 *
 * @version v18
 * @since   v18
 */
class FormView extends View implements FormRendererInterface
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

        return $this->render('components/form.twig.html');
    }

    protected function getInlineJavascript(Form $form)
    {
        $javascript = [];

        foreach (array_reverse($form->getTriggers()) as $trigger) {
            $javascript[] = $trigger->getOutput();
        }
        
        return $javascript;
    }

    /**
     * Get the maximum columns required to render this form.
     * @return  int
     */
    protected function getColumnCount(Form $form)
    {
        return array_reduce($form->getRows(), function ($count, $row) {
            return max($count, $row->getElementCount());
        }, 0);
    }

    /**
     * @deprecated Empty-method for module backwards compatibility.
     * Will be removed by the end of the mobile-responsive refactoring.
     */
    public function setWrapper($name, $value) {
        return $this;
    }
}
