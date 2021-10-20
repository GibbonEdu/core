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
            ->setClass("w-9/12 mt-1")
            ->addClass("floatNone")
            ->wrap('<div class="w-full">', '</div>');

        $this->addButton = $factory->createButton(__("Add"), '', $name . 'Add')
            ->addClass("w-9/12")
            ->wrap('<div class="w-full">', '</div>');
        $this->removeButton = $factory->createButton(__("Remove"), '', $name . 'Remove')
            ->addClass("w-9/12 mt-1")
            ->wrap('<div class="w-full">', '</div>');

        $this->searchBox = $factory->createTextField($name . "Search")
            ->placeholder(__("Search"))
            ->setClass("w-9/12 mt-1")
            ->addClass("floatNone")
            ->wrap('<div class="w-full">', '</div>');
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
        
        $output .= '<script type="text/javascript">
            $(function(){
                $("#'.$this->name.'Container").gibbonMultiSelect("'.$this->name.'");
            });
        </script>';

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
