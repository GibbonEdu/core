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
 * Multi Select
 *
 * @version v14
 * @since   v14
 */
class MultiSelect implements OutputableInterface
{

    protected $sourceSelect;
    protected $destinationSelect;
    protected $addButton;
    protected $removeButton;
    protected $name;

    public function __construct(FormFactoryInterface &$factory, $name) {        
        $this->name = $name;

        $this->sourceSelect = $factory->createSelect($name . "Source")->selectMultiple(true)->setSize(8);
        $this->destinationSelect = $factory->createSelect($name . "Destination")->selectMultiple(true)->setSize(8);

        $this->addButton = $factory->createButton("Add", 'optionTransfer(\'' . $this->sourceSelect->getID() . '\',\'' . $this->destinationSelect->getID() . '\')');
        $this->removeButton = $factory->createButton("Remove", 'optionTransfer(\'' . $this->destinationSelect->getID() . '\',\'' . $this->sourceSelect->getID() . '\')');
    }

    public function setSize($size=8) {
        $this->sourceSelect->setSize($size);
        $this->destinationSelect->setSize($size);
        return $this;
    }

    public function source() {
        return $this->sourceSelect;
    }

    public function destination() {
        return $this->destinationSelect;
    }

    public function getOutput() {
        $output = '';

        // TODO: Move javascript to somewhere more sensible

        $output .= '<script type="text/javascript">';

        $output .= 'function optionTransfer(select0Name, select1Name) {
            var select0 = document.getElementById(select0Name);
            var select1 = document.getElementById(select1Name);
            for (var i = select0.length - 1; i>=0; i--) {
                var option = select0.options[i];
                if (option != null) {
                    if (option.selected) {
                        select0.remove(i);
                        try {
                            select1.add(option, null);
                        } catch (ex) {
                            select1.add(option);
                        }
                    }
                }
            }
            sortSelect(select0);
            sortSelect(select1);
        }' . "\n";

        $output .= 'function sortSelect(list) {
            var tempArray = new Array();
            for (var i=0;i<list.options.length;i++) {
                tempArray[i] = new Array();
                tempArray[i][0] = list.options[i].text;
                tempArray[i][1] = list.options[i].value;
            }
            tempArray.sort();
            while (list.options.length > 0) {
                list.options[0] = null;
            }
            for (var i=0;i<tempArray.length;i++) {
                var op = new Option(tempArray[i][0], tempArray[i][1]);
                list.options[i] = op;
            }
            return;
        }';

        $output .= '
            jQuery(function($){

                var destinationSelect = $(\'#'.$this->destinationSelect->getID().'\');
                var form = destinationSelect.parents(\'form\');

                form.submit(function(){
                    var options = $(\'option\', destinationSelect);

                    for (var i = 0; i < options.length; i++) {
                        $(\'<input>\').attr({
                            type: \'hidden\',
                            name: \'' . $this->name . '[]\'
                        }).val(options[i].value).appendTo(form);
                    }
                });
            });

        ';
        $output .= '</script>';

        $output .= '<table class="blank"><tr>';

        $output .= '<td style="width:40%">';
            $output .= $this->sourceSelect->getOutput();
        $output .= '</td>';

        $output .= '<td style="width:20%; text-align:center">';
            $output .= $this->addButton->getOutput();
            $output .= $this->removeButton->getOutput();
        $output .= '</td>';

        $output .= '<td style="width:40%">';
            $output .= $this->destinationSelect->getOutput();
        $output .= '</td>';

        $output .= '</tr></table>';

        return $output;
    }

    public function getClass() {
        return '';
    }

}
