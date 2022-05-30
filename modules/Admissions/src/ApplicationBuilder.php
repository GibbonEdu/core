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

namespace Gibbon\Module\Admissions;

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\DatabaseFormFactory;

/**
 * ApplicationBuilder
 *
 * @version v24
 * @since   v24
 */
class ApplicationBuilder extends FormBuilder
{
    protected $officeFields = ['gibbonSchoolYearIDEntry', 'gibbonYearGroupIDEntry', 'dateStart', 'username', 'studentID', 'priority', 'dateStart'];


    public function isOfficeOnlyField(string $fieldName) : bool
    {
        return in_array($fieldName, $this->officeFields);
    }

    public function isFieldHidden(array $field) : bool
    {
        return $this->includeHidden != ($field['hidden'] == 'Y') && !$this->isOfficeOnlyField($field['fieldName']);
    }

    public function acquire()
    {
        $data = parent::acquire();

        foreach ($this->officeFields as $fieldName) {
            if (isset($data[$fieldName])) continue;

            $data[$fieldName] = $_POST[$fieldName] ?? null;
        }

        return $data;
    }

    public function edit(Url $action)
    {
        $form = Form::create('formBuilder'.($this->includeHidden ? 'OfficeOnly' : ''), (string)$action);
        $form->setFactory(DatabaseFormFactory::create($this->getContainer()->get('db')));

        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValue('gibbonFormID', $this->gibbonFormID);
        $form->addHiddenValue('gibbonFormPageID', $this->getDetail('gibbonFormPageID'));
        $form->addHiddenValue('page', -1);
        $form->addHiddenValues($this->urlParams);

        // Display the Office-Only fields first
        if ($this->includeHidden) {
            $form->addRow()->addHeading('For Office Use', __('For Office Use'));

            $this->addOfficeOnlyFields($form);

            foreach ($this->pages as $formPage) {
                foreach ($this->fields as $field) {
                    if ($field['hidden'] == 'N' && !$this->isOfficeOnlyField($field['fieldName'])) continue;
                    if ($field['pageNumber'] != $formPage['sequenceNumber']) continue;

                    $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                    $row = $fieldGroup->addFieldToForm($this, $form, $field);
                }
            }
        } else {

            // Display all non-hidden fields
            foreach ($this->pages as $formPage) {
                foreach ($this->fields as $field) {
                    if ($field['hidden'] == 'Y') continue;
                    if ($field['pageNumber'] != $formPage['sequenceNumber']) continue;

                    $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                    $row = $fieldGroup->addFieldToForm($this, $form, $field);
                }
            }
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit(__('Submit'));

        return $form;
    }

    protected function addOfficeOnlyFields(Form $form)
    {
        if (!empty($this->getConfig('status'))) {
            $statuses = [
                'Incomplete'   => __('Incomplete'),
                'Pending'      => __('Pending'),
                'Waiting List' => __('Waiting List'),
                'Rejected'     => __('Rejected'),
                'Withdrawn'    => __('Withdrawn'),
            ];

            $row = $form->addRow();
            if ($this->getConfig('status') != 'Accepted') {
                $row->addLabel('status', __('Status'))->description(__('Manually set status.'));
                $row->addSelect('status')->fromArray($statuses)->required()->selected($this->getConfig('status'));
            } else {
                $row->addLabel('statusField', __('Status'));
                $row->addTextField('statusField')->required()->readOnly()->setValue($this->getConfig('status'));
            }
        }

        if (!$this->hasField('priority')) {
            $row = $form->addRow();
                $row->addLabel('priority', __('Priority'))->description(__('Higher priority applicants appear first in list of applications.'));
                $row->addSelect('priority')->fromArray(range(-9, 9))->required();
        }

        if (!$this->hasField('dateStart')) {
            $row = $form->addRow();
                $row->addLabel('dateStart', __('Start Date'))->description(__('Student\'s intended first day at school.'));
                $row->addDate('dateStart')->required();
        }
    }
}
