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
use Gibbon\Forms\ValidatableInterface;

/**
 * Multi Select
 *
 * @version v14
 * @since   v14
 */
class MultiSelect implements OutputableInterface, ValidatableInterface
{

    protected $name;
    protected $sortableAttributes;

    protected $sourceSelect;
    protected $destinationSelect;
    protected $addButton;
    protected $removeButton;
    protected $sortBySelect;
    protected $searchBox;

    public function __construct(FormFactoryInterface &$factory, $name) {
        $this->name = $name;

        $this->sourceSelect = $factory->createSelect($name . "Source")
            ->selectMultiple(true)
            ->setSize(8)
            ->setClass('w-full')
            ->addClass("floatNone");
        $this->destinationSelect = $factory->createSelect($name)
            ->selectMultiple(true)
            ->setSize(8)
            ->setClass('w-full');

        $this->sortBySelect = $factory->createSelect($name . "Sort")
            ->placeholder(__("Sort by Name"))
            ->setClass("w-48 mt-1")
            ->addClass("floatNone");

        $this->addButton = $factory->createButton(__("Add"))
            ->onClick('optionTransfer(\'' . $this->name . '\', true)')
            ->addClass("w-48");
        $this->removeButton = $factory->createButton(__("Remove"))
            ->onClick('optionTransfer(\'' . $this->name . '\', false)')
            ->addClass("w-48 mt-1");

        $this->searchBox = $factory->createTextField($name . "Search")
            ->placeholder(__("Search"))
            ->setClass("w-48 mt-1")
            ->addClass("floatNone");
    }

    public function getID()
    {
        return $this->name;
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

    /**
     * Set the multi-select to required.
     * @param   bool    $value
     * @return  self
     */
    public function isRequired($required = true)
    {
        $this->destinationSelect->setRequired($required);
        return $this;
    }

    /**
     * Gets the multi-select's required state.
     * @return  bool
     */
    public function getRequired()
    {
        return $this->destinationSelect->getRequired();
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
        $output .= 'var '.$this->name.'sortBy = null;';
        $output .= 'function optionTransfer(name, add) {
            var select0 = $(\'#\'+name+(add ? \'Source\' : \'\'));
            var select1 = $(\'#\'+name+(!add ? \'Source\' : \'\'));

            select0.find(\'option:selected\').each(function(){
                select1.append($(this).clone());
                $(this).detach().remove();
            });

            sortSelects(name);

            select1.change().focus();
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

                // Select all options on submit so we can validate this select input.
                $("input[type=\'Submit\']", form).click(function() {
                    $(\'option\', destinationSelect).each(function() {
                        $(this).prop("selected", true);
                    });
                });

                $(\'#'. $this->sortBySelect->getID() .'\').change(function(){
                    '.$this->name.'sortBy = $(this).val();
                    sortSelects("'.$this->name.'");
                });

                $(\'#'. $this->searchBox->getID() .'\').keyup(function(){
                    var search = $(this).val().toLowerCase();
                    $(\'option\', sourceSelect).each(function(){
                        var option = $(this);
                        if (option.text().toLowerCase().includes(search)) {
                            option.show();
                        } else {
                            option.hide();
                        }
                    });
                });
            });

            function sortSelects(name) {
                var values = null;

                if (window[name+"sortBy"] != \'Sort by Name\' && window[name+"sortBy"] != null) {
                    values = $(\'#\' + name +\'Container\').data(\'sortable\')[window[name+"sortBy"]];
                }

                sortSelect($(\'#\' + name + "Source"), values);
                sortSelect($(\'#\' + name), values);
            }

        ';
        $output .= '</script>';

        $output .= '<div id="'.$this->name.'Container" class="w-full flex flex-wrap items-center" data-sortable="'.htmlentities(json_encode($this->sortableAttributes)).'">';

        $output .= '<div class="w-full sm:w-1/3">';
            $output .= $this->sourceSelect->getOutput();
        $output .= '</div>';

        $output .= '<div class="w-full sm:w-1/3 text-center py-2 sm:py-0">';
            $output .= $this->addButton->getOutput();
            $output .= $this->removeButton->getOutput();
            if (!empty($this->sortableAttributes)) {
                $output .= $this->sortBySelect->getOutput();
            }
            $output .= $this->searchBox->getOutput();
        $output .= '</div>';

        $output .= '<div  class="w-full sm:w-1/3">';
            $output .= $this->destinationSelect->getOutput();
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Add a LiveValidation setting to the right-hand select by type (eg: Validate.Presence)
     * @param  string  $type
     * @param  string  $params
     */
    public function addValidation($type, $params = '')
    {
        return $this->destinationSelect->addValidation($type, $params);
    }

    /**
     * Get the combined validation output from the right-hand select.
     * @return  string
     */
    public function getValidationOutput()
    {
        return $this->destinationSelect->getValidationOutput();
    }

    public function getClass() {
        return '';
    }

}
