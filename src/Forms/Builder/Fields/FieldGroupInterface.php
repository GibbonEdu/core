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

namespace Gibbon\Forms\Builder\Fields;

use Gibbon\Forms\Form;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\FormBuilderInterface;

interface FieldGroupInterface
{
    public function getDescription() : string;

    public function getField($fieldName) : array;

    public function getFields() : array;

    public function getFieldOptions() : array;

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field) : Row;

    public function getFieldDataFromPOST(string $fieldFame, array $field);

    public function displayFieldValue(FormBuilderInterface $formBuilder, string $fieldName, array $field, array &$data = []);

    /**
     * Handle whether fields should validate based on the presence of other fields.
     *
     * @param FormBuilderInterface $formBuilder
     * @param array $data
     * @param string $fieldName
     * @return bool
     */
    public function shouldValidate(FormBuilderInterface $formBuilder, array &$data, string $fieldName);
}
