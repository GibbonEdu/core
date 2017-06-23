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
    protected $formOutput;

    public function __construct(FormFactoryInterface &$factory, $name, $form)
    {
        $this->name = $name;
        $this->placeholder = "Blocks will appear here...";  
        $this->addButton = $factory->createButton("Add Block", 'add'. $this->name .'Block()');
        $this->formOutput = $form->getOutput();
    }

    public function placeholder($value)
    {
        $this->placeholder = $value;
    }

    public function getClass()
    {
        return '';
    }

    public function getOutput()
    {
        $output = '';

        $output .= '<style>
                #<?php print $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
                #<?php print $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
                div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
                html>body #<?php print $type ?> li { min-height: 58px; line-height: 1.2em; }
                .<?php print $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
                .<?php print $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
            </style>';

        $output .= '<div class="' . $this->name. '" id="' . $this->name. '" style="width: 100%; padding: 5px 0px 0px 0px; min-height: 66px">';
            $output .= '<div id="' . $this->name . 'Outer0">';
                $output .= '<div style="color: #ddd; font-size: 230%; margin: 15px 0 0 6px">' . $this->placeholder . '</div>';
            $output .= '</div>';

            $output .= '<div id="'. $this->name .'Template" style="display:none">';
                $output .= '<div style="float:left; width:90%">';
                $output .= $this->formOutput;
                $output .= '</div>';
                $output .= '<div>';
                $output .= 'Test';
                $output .= '</div>';
            $output .= '</div>';

        $output .= '</div>';

        $output .= '<script type="text/javascript">' . "\n";
            $output .= 'var ' . $this->name . 'Count = 1;' . "\n";
            $output .= 'function add'. $this->name .'Block() {' . "\n";
                $output .= '$("#' . $this->name . 'Outer0").css("display", "none");' . "\n";
                $output .= 'var outerName = "'. $this->name .'Outer" + ' . $this->name . 'Count;';
                $output .= '$("#'. $this->name . 'Template").clone().css("display", "block").prop("id", outerName).appendTo($("#'. $this->name .'"));' . "\n";
                $output .= $this->name . 'Count++;' . "\n";
            $output .= '}';
        $output .= '</script>';

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
