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

namespace Gibbon\Forms\Input;

use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms\FormFactoryInterface;

/**
 * Custom Blocks
 *
 * @version v14
 * @since   v14
 */
class CustomBlocks implements OutputableInterface
{

    protected $name;
    protected $placeholder;
    protected $addButton;

    public function __construct(FormFactoryInterface &$factory, $name)
    {
        $this->name = $name;
        $this->placeholder = "Blocks will appear here...";  
        $this->addButton = $factory->createButton("Add Block", "");
    }

    public function placeholder($value)
    {
        $placeholder = $value;
    }

    public function getClass()
    {
        return '';
    }

    public function getOutput()
    {
        $output = '';


        $output .= '<div class="' . $this->name. '" id="' . $this->name. ' " style="width: 100%; padding: 5px 0px 0px 0px; min-height: 66px">';
            $output .= '<div id="' . $this->name . 'Outer0">';
                $output .= '<div style="color: #ddd; font-size: 230%; margin: 15px 0 0 6px">' . $this->placeholder . '</div>';
            $output .= '</div>';
        $output .= '</div>';

        $output .= '<div style="width: 100%; padding: 0px 0px 0px 0px">';
            $output .= '<div class="ui-state-default_dud" style="padding: 0px; height: 40px">';
                $output .= '<table class="blank" cellspacing="0" style="width: 100%">';
                    $output .= '<tr>';
                        $output .= '<td style="width: 50%">';
                            $output .= $this->addButton->getOutput();
                        $output .= '</td>';
                    $output .= '</tr>';
                $output .= '</table>';
            $output .= '</div>';
        $output .= '</div>';

        return $output;
    }
}
