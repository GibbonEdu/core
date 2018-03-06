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
 * @version v15
 * @since   v15
 */
class CustomBlocks implements OutputableInterface
{

    protected $name;
    protected $placeholder;
    protected $toolInputs;
    protected $blockButtons;
    protected $blockTemplate;
    protected $factory;
    protected $session;

    /**
     * Create a Blocks input with a given template.
     * @param  FormFactoryInterface $factory
     * @param  string               $name
     * @param  OutputableInterface  $form
     * @param  Session              $session
     */
    public function __construct(FormFactoryInterface &$factory, $name, OutputableInterface $block, \Gibbon\Session $session)
    {
        $this->session = $session;
        $this->factory = $factory;
        $this->name = $name;
        $this->placeholder = __("Blocks will appear here..."); 
        $this->toolInputs = array($factory->createButton(__("Add Block"), 'add'. $this->name .'Block()'));
        $this->blockButtons = array();
        $this->blockTemplate = $block->setClass("blank fullWidth")->getOutput();
    }

    /**
     * Changes the placeholder string when no blocks are present.
     * @param  string  $value
     * @return self
     */
    public function placeholder($value)
    {
        $this->placeholder = $value;
        return $this;
    }

    /**
     * Adds the given input into the tool bar.
     * @param  OutputableInterface  $value
     * @return self
     */
    public function addToolInput(OutputableInterface $input)
    {
        $this->toolInputs[] = $input;
        return $this;
    }

    /**
     * Adds the given button to each block. 
     * Note: $function must be the name of the function (i.e. "func" not "func()"). The function must only take in one input (the id of the block).
     * @param  string  $name
     * @param  string  $icon
     * @param  string  $function
     * @return self
     */
    public function addBlockButton($name, $icon, $function)
    {
        $this->blockButtons[] = array("name" => $name, "icon" => $icon, "function" => $function);
        return $this;
    }

    public function getClass()
    {
        return '';
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    public function getOutput()
    {
        $output = '';

        $output .= '<style>
                #' . $this->name . ' { list-style-type: none; margin: 0; padding: 0; width: 100%; }
                #' . $this->name . ' div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
                div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
                html>body #' . $this->name . ' li { min-height: 58px; line-height: 1.2em; }
                .' . $this->name . '-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
                .' . $this->name . '-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
            </style>';

        $output .= '<div class="' . $this->name. '" id="' . $this->name. '" style="width: 100%; padding: 5px 0px 0px 0px; min-height: 66px">
            <div id="' . $this->name . 'Outer0">
                <div style="color: #ddd; font-size: 230%; margin: 15px 0 15px 6px">' . $this->placeholder . '</div>
           </div>

           <div id="'. $this->name .'Template" class="hiddenReveal" style="display:none; overflow:hidden; border: 1px solid #d8dcdf; margin: 0 0 5px">
                <div style="float:left; width:92%;">'. $this->blockTemplate .'</div>
                <div style="float:right; width: 8%; ">
                    <table class="blank">
                        <tr>
                            <td>
                                <img id="' . $this->name . 'deleteTemplate" title="' . __('Delete') . '" src="./themes/' . $this->session->get("gibbonThemeName") . '/img/garbage.png"/>
                            </td>
                            ';
                            $count = 1;
                            foreach ($this->blockButtons as $blockButton) {
                                if ($count % 2 == 0) {
                                    $output.= '</tr><tr>';
                                }
                                $output .= '<td>
                                    <img id="' . $this->name . $count . 'ButtonTemplate" title="' . $blockButton["name"] . '" src="' . $blockButton["icon"] . '"/>
                                </td>';
                                $count++;
                            }

                            if ($count % 2 == 1) {
                                $output .= '<td></td>';
                            }

                            $output .= '
                        </tr>
                    </table>
                </div>
            </div>
            
            <div id="'. $this->name .'Tools" class="ui-state-default_dud" style="width: 100%; padding: 0px; height: 40px; display: table">
                <table class="blank" cellspacing="0" style="width: 100%">
                    <tr>';
                        foreach ($this->toolInputs as $toolInput) {
                            $output .= '<td style="float: left">' . $toolInput->getOutput() . '</td>';
                        }
                    $output .= '</tr>
                </table>
            </div>
        </div>';

        $output .= '<script type="text/javascript">
            var ' . $this->name . 'Count = 1;
            function add'. $this->name .'Block() {
                $("#' . $this->name . 'Outer0").css("display", "none");
                $("#'. $this->name . 'Template").clone().css("display", "block").prop("id", "'. $this->name .'Outer" + ' . $this->name . 'Count).insertBefore($("#'. $this->name .'Tools"));
                $("#'. $this->name .'Outer" + ' . $this->name . 'Count + " input[id], #'. $this->name .'Outer" + ' . $this->name . 'Count + " textarea[id]").each(function () { $(this).prop("name", $(this).prop("id") + "[" + ' . $this->name . 'Count + "]"); });
                $("#'. $this->name .'Outer" + ' . $this->name . 'Count + " label").each(function () { $(this).prop("for", $(this).prop("for") + "[" + ' . $this->name . 'Count + "]"); });
                $(\'<input>\').attr({
                            type: \'hidden\',
                            name: \'' . $this->name . 'Order[]\'
                        }).val(' . $this->name . 'Count).appendTo($("#'. $this->name .'Outer" + ' . $this->name . 'Count));
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
                });';

                $count = 1;
                foreach ($this->blockButtons as $blockButton)
                {
                    $output .= '$("#'. $this->name .'Outer" + ' . $this->name . 'Count + " img[id*= '. $this->name . $count . 'ButtonTemplate]").each(function () {
                        $(this).prop("id", "' . $this->name . $count . 'Button" + ' . $this->name . 'Count);
                        $(this).unbind("click").click(function() {
                           ' . $blockButton["function"] . '($(this).attr("id").split("Button")[1]);
                        });
                    });';
                    $count++;
                }

                $output .= $this->name . 'Count++;
            }
        </script>';

        return $output;
    }
}
