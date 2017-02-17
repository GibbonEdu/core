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

namespace Gibbon\Forms;

use Gibbon\Forms\FormRendererInterface;

/**
 * FormRenderer
 *
 * Handles turning the Rows and Elements of a Form into HTML output. Replaceable component for extensibility.
 *
 * @version v14
 * @since   v14
 */
class FormRenderer implements FormRendererInterface
{
    public static function create()
    {
        return new FormRenderer();
    }

    public function renderForm(Form $form)
    {
        $output = '';

        $totalColumns = $this->getColumnCount($form, $form->getRows());

        $output .= '<form id="'.$form->getID().'" method="'.$form->getMethod().'" action="'.$form->getAction().'" enctype="multipart/form-data">';

        // Output hidden values
        foreach ($form->getHiddenValues() as $name => $value) {
            $output .= '<input name="'.$name.'" value="'.$value.'" type="hidden">';
        }

        $output .= '<table class="'.$form->getClass().'" cellspacing="0">';

        // Output form rows
        foreach ($form->getRows() as $row) {
            $output .= '<tr id="'.$row->getID().'" class="'.$row->getClass().'">';

            // Output each element inside the row
            foreach ($row->getElements() as $element) {
                $colspan = ($row->isLastElement($element) && $row->getElementCount() < $totalColumns)? 'colspan="'.($totalColumns + 1 - $row->getElementCount()).'"' : '';

                $output .= '<td class="'.$element->getClass().'" '.$colspan.'>';
                    $output .= $element->getOutput();
                $output .= '</td>';
            }
            $output .= '</tr>';
        }

        $output .= '</table>';

        // Output the validation code, aggregated
        $output .= '<script type="text/javascript">'."\n";

        foreach ($form->getRows() as $row) {
            foreach ($row->getElements() as $element) {
                if ($element instanceof ValidatableInterface) {
                    $output .= $element->getValidation();
                }
            }
        }

        // Output the trigger code
        foreach (array_reverse($form->getTriggers()) as $trigger) {
            $output .= $trigger->getOutput();
        }

        $output .= '</script>';

        $output .= '</form>';

        return $output;
    }

    protected function getColumnCount(Form $form, $rows)
    {
        $count = 0;
        foreach ($form->getRows() as $row) {
            if ($row->getElementCount() > $count) {
                $count = $row->getElementCount();
            }
        }

        return $count;
    }
}
