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

    protected $name;
    protected $sortableAttributes;

    protected $sourceSelect;
    protected $destinationSelect;
    protected $addButton;
    protected $removeButton;
    protected $sortBySelect;

    public function __construct(FormFactoryInterface &$factory, $name) {
        $this->name = $name;

        $this->sourceSelect = $factory->createSelect($name . "Source")->selectMultiple(true)->setSize(8)->setClass('mediumWidth')->addClass("floatNone");
        $this->destinationSelect = $factory->createSelect($name . "Destination")->selectMultiple(true)->setSize(8)->setClass('mediumWidth');

        $this->sortBySelect = $factory->createSelect($name . "Sort")
            ->placeholder(__("Sort by Name"))
            ->setClass("shortWidth")
            ->addClass("floatNone");

        $this->addButton = $factory->createButton(__("Add"))
            ->onClick('optionTransfer(\'' . $this->sourceSelect->getID() . '\',\'' . $this->destinationSelect->getID() . '\')')
            ->setClass("shortWidth");
        $this->removeButton = $factory->createButton(__("Remove"))
            ->onClick('optionTransfer(\'' . $this->destinationSelect->getID() . '\',\'' . $this->sourceSelect->getID() . '\')')
            ->setClass("shortWidth");
    }

    public function addSortableAttribute($attribute, $values)
    {
        $this->sortableAttributes[$attribute] = $values;
        $this->sortBySelect->fromArray(array($attribute => __("Sort by " . $attribute)));
        return $this;
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

        // TODO: Validate merge keys from both selects, throw exception if key conflict


        // TODO: Move javascript to somewhere more sensible

        $output .= '<script type="text/javascript">';

        $output .= 'function optionTransfer(select0Name, select1Name) {
            var select0 = $(\'#\'+select0Name);
            var select1 = $(\'#\'+select1Name);

            select0.find(\'option:selected\').each(function() {
                select1.append($(this).clone());
                $(this).detach().remove();
            });

            sortSelect(select0, null);
            sortSelect(select1, null);
        }' . "\n";

        $output .= 'function sortSelect(list, sortValues) {
            var options = $(\'option\', list);
            if(sortValues == null) {
                sortValues = {};
            }
            var arr = options.map(function(_, o) { return { tSort: sortValues[o.value] + $(o).text(), t: $(o).text(), v: o.value }; }).get();
            arr.sort(function(o1, o2) { return o1.tSort > o2.tSort ? 1 : o1.tSort < o2.tSort ? -1 : 0; });
            options.each(function(i, o) {
              o.value = arr[i].v;
              $(o).text(arr[i].t);
            });
        }
        ';

        $output .= '
            jQuery(function($){

                var sourceSelect = $(\'#'.$this->sourceSelect->getID().'\');
                var destinationSelect = $(\'#'.$this->destinationSelect->getID().'\');
                var form = destinationSelect.parents(\'form\');
                var sortables = $(\'#'. $this->name .'\').data(\'sortable\');

                form.submit(function(){
                    var options = $(\'option\', destinationSelect);

                    for (var i = 0; i < options.length; i++) {
                        $(\'<input>\').attr({
                            type: \'hidden\',
                            name: \'' . $this->name . '[]\'
                        }).val(options[i].value).appendTo(form);
                    }
                });

                $(\'#'. $this->sortBySelect->getID() .'\').change(function(){
                    var sortBy = $(this).val();

                    var values = null;

                    if (sortBy != \'Sort by Name\') {
                        values = sortables[sortBy];
                    }

                    sortSelect(sourceSelect, values);
                    sortSelect(destinationSelect, values);

                });
            });

        ';
        $output .= '</script>';

        $output .= '<table id="'.$this->name.'" class="blank fullWidth" data-sortable="'.htmlentities(json_encode($this->sortableAttributes)).'"><tr>';

        $output .= '<td style="width:35%; vertical-align:top;">';
            $output .= $this->sourceSelect->getOutput();
        $output .= '</td>';

        $output .= '<td style="width:30%; text-align:center">';
            $output .= $this->addButton->getOutput() . '<br/>';
            $output .= $this->removeButton->getOutput();
            if (!empty($this->sortableAttributes)) {
                $output .= '<br/>' . $this->sortBySelect->getOutput();
            }
        $output .= '</td>';

        $output .= '<td style="width:35%; vertical-align:top;">';
            $output .= $this->destinationSelect->getOutput();
        $output .= '</td>';

        $output .= '</tr></table>';

        return $output;
    }

    public function getClass() {
        return '';
    }

}
