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
        $this->placeholder = __("Blocks will appear here...");  
        $this->addButton = $factory->createButton(__("Add Block"), 'add'. $this->name .'Block()');
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

        $output .= '<div class="' . $this->name. '" id="' . $this->name. '" style="width: 100%; padding: 5px 0px 0px 0px; min-height: 66px">
            <div id="' . $this->name . 'Outer0">
                <div style="color: #ddd; font-size: 230%; margin: 15px 0 0 6px">' . $this->placeholder . '</div>
           </div>

           <div id="'. $this->name .'Template" style="display:none">
                <input name="' . $this->name . 'order[]" type="hidden" value="-1">
                <div style="float:left; width:90%">'. $this->formOutput .'</div>
                <div style="float:right; width: 9.5%">
                    <table class="smallIntBorder fullWidth standardForm">
                        <tr>
                            <td>
                                <img id="' . $this->name . 'deleteTemplate" title="' . __('Delete') . '" src="./themes/Default/img/garbage.png"/>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div id="'. $this->name .'Tools" class="ui-state-default_dud" style="width: 100%; padding: 0px; height: 40px; display: table">
                <table class="blank" cellspacing="0" style="width: 100%">
                    <tr>
                        <td style="width: 50%">'. $this->addButton->getOutput() .'</td>
                    </tr>
                </table>
            </div>
        </div>';

        $output .= '<script type="text/javascript">
            var ' . $this->name . 'Count = 1;
            function add'. $this->name .'Block() {
                $("#' . $this->name . 'Outer0").css("display", "none");
                $("#'. $this->name . 'Template").clone().css("display", "block").prop("id", "'. $this->name .'Outer" + ' . $this->name . 'Count).insertBefore($("#'. $this->name .'Tools"));
                $("#'. $this->name .'Outer" + ' . $this->name . 'Count).find("input[name*=' . $this->name . 'order]").val(' . $this->name . 'Count);
                $("#'. $this->name .'Outer" + ' . $this->name . 'Count + " input[id], #'. $this->name .'Outer" + ' . $this->name . 'Count + " textarea[id]").each(function () { $(this).prop("id", $(this).prop("id") + ' . $this->name . 'Count); });
                $("#'. $this->name .'Outer" + ' . $this->name . 'Count + " img[id*= '. $this->name . 'deleteTemplate]").each(function () {
                    $(this).prop("id", "' . $this->name . 'Delete" + ' . $this->name . 'Count);
                    $(this).unbind("click").click(function() {
                        if (confirm("Are you sure you want to delete this record?")) {
                            $("#'. $this->name .'Outer" + $(this).attr("id").split("Delete")[1]).fadeOut(600, function(){
                               $(this).remove();
                                if ($("#'. $this->name .'").children().length == 3) {
                                    $("#' . $this->name . 'Outer0").css("display", "block");
                                }
                            });
                        }
                    });
                });'.
                $this->name . 'Count++;
            }
        </script>';

        return $output;
    }
}
