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

namespace Gibbon\Forms\Builder;

use Gibbon\Http\Url;
use Gibbon\Forms\MultiPartForm;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Forms\Builder\Fields\NullFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\Exception\NotFoundException;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Form;

class FormBuilder implements ContainerAwareInterface, FormBuilderInterface
{
    use ContainerAwareTrait;

    protected $formGateway;
    protected $formPageGateway;

    protected $gibbonFormID;
    protected $gibbonFormPageID;
    protected $pageNumber;
    protected $finalPageNumber;
    protected $includeHidden = false;

    protected $pages = [];
    protected $fields = [];
    protected $details = [];
    protected $config = [];
    protected $type = '';

    protected $fieldGroups;

    public function __construct(FormGateway $formGateway, FormPageGateway $formPageGateway)
    {
        $this->formGateway = $formGateway;
        $this->formPageGateway = $formPageGateway;
    }

    public function hasField($fieldName) : bool
    {
        return !empty($this->fields[$fieldName]);
    }

    public function getField($fieldName)
    {
        return $this->fields[$fieldName] ?? [];
    }

    public function hasDetail($name) : bool
    {
        return !empty($this->details[$name]);
    }

    public function getDetail($name, $default = null)
    {
        return $this->details[$name] ?? $default;
    }

    public function hasConfig($name) : bool
    {
        return !empty($this->config[$name]);
    }

    public function getConfig($name, $default = null)
    {
        return $this->config[$name] ?? $default;
    }

    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    public function getFinalPageNumber()
    {
        return $this->finalPageNumber;
    }

    public function includeHidden(bool $includeHidden = true)
    {
        $this->includeHidden = $includeHidden;

        return $this;
    }

    public function getFieldGroup($fieldGroup)
    {
        if (isset($this->fieldGroups[$fieldGroup])) {
            return $this->fieldGroups[$fieldGroup];
        }

        try {
            $this->fieldGroups[$fieldGroup] = $this->getContainer()->get("\\Gibbon\\Forms\\Builder\\Fields\\".$fieldGroup);
            return $this->fieldGroups[$fieldGroup];
        } catch (NotFoundException $e) {
            return new NullFieldGroup();
        }
    }
    
    public function populate(string $gibbonFormID, int $pageNumber = 1, array $urlParams = [])
    {
        $this->gibbonFormID = $gibbonFormID;
        $this->pageNumber = $pageNumber;
        $this->urlParams = $urlParams;        
        
        // Load form details
        $this->details = $this->formGateway->getByID($this->gibbonFormID);
        $this->config = json_decode($this->details['config'] ?? '', true);

        // Load all page data
        $criteria = $this->formPageGateway->newQueryCriteria()->sortBy('sequenceNumber', 'ASC');
        $this->pages = $this->formPageGateway->queryPagesByForm($criteria, $this->gibbonFormID)->toArray();
        $this->details['gibbonFormPageID'] = $this->formPageGateway->getPageIDByNumber($this->gibbonFormID, $this->pageNumber);

        // Determine the final page number
        $finalPage = end($this->pages);
        $this->finalPageNumber = $finalPage['sequenceNumber'] ?? $this->pageNumber;

        // Load all field data
        $this->fields = $this->formGateway->selectFieldsByForm($this->gibbonFormID)->fetchGroupedUnique();
    
        return $this;
    }

    public function acquire()
    {
        $data = [];

        foreach ($this->fields as $fieldName => $field) {
            if ($field['hidden'] == 'Y' && !$this->includeHidden) continue;
            if ($field['pageNumber'] != $this->pageNumber) continue;

            $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
            $fieldValue = $fieldGroup->getFieldDataFromPOST($fieldName, $field['fieldType']);

            if (!is_null($fieldValue)) {
                $data[$fieldName] = $fieldValue;
            }
        }

        return $data;
    }

    public function validate(array $data)
    {
        $invalid = [];
        foreach ($this->fields as $fieldName => $field) {
            if ($field['hidden'] == 'Y' && !$this->includeHidden) continue;
            if ($field['pageNumber'] != $this->pageNumber) continue;

            $fieldValue = &$data[$fieldName];
            if ($field['required'] != 'N' &&  (is_null($fieldValue) || $fieldValue == '')) {
                $invalid[] = $fieldName;
            }
        }

        return !empty($invalid);
    }

    public function build(Url $action, Url $pageUrl)
    {
        $form = MultiPartForm::create('formBuilder', (string)$action);
        $form->setFactory(DatabaseFormFactory::create($this->getContainer()->get('db')));

        $form->addHiddenValue('gibbonFormID', $this->gibbonFormID);
        $form->addHiddenValue('gibbonFormPageID', $this->getDetail('gibbonFormPageID'));
        $form->addHiddenValue('page', $this->pageNumber);
        $form->addHiddenValues($this->urlParams);
        
        // Add pages to the multi-part form
        if (count($this->pages) > 1) {
            $form->setCurrentPage($this->pageNumber);

            foreach ($this->pages as $formPage) {
                $form->addPage($formPage['sequenceNumber'], $formPage['name'], (string)$pageUrl->withQueryParams($this->urlParams + ['gibbonFormID' => $this->gibbonFormID, 'page' => $formPage['sequenceNumber']]));
            }
        }

        // Form is not complete, add fields to current page
        if ($this->pageNumber <= $this->finalPageNumber) {
            foreach ($this->fields as $field) {
                if ($field['hidden'] == 'Y' && !$this->includeHidden) continue;
                if ($field['pageNumber'] != $this->pageNumber) continue;

                $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                $row = $fieldGroup->addFieldToForm($form, $field);
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit($this->pageNumber == $this->finalPageNumber ? __('Submit') : __('Next'));
        }

        return $form;
    }

    public function edit(Url $action, $application = null)
    {
        $form = Form::create('formBuilder', (string)$action);
        $form->setFactory(DatabaseFormFactory::create($this->getContainer()->get('db')));

        $form->addHiddenValue('gibbonFormID', $this->gibbonFormID);
        $form->addHiddenValue('gibbonFormPageID', $this->getDetail('gibbonFormPageID'));
        $form->addHiddenValue('page', -1);
        $form->addHiddenValues($this->urlParams);

        // Display the Office-Only fields first
        if ($this->includeHidden) {
            $form->addRow()->addHeading('For Office Use', __('For Office Use'));

            if (!empty($application['gibbonAdmissionsApplicationID'])) {
                $row = $form->addRow();
                    $row->addLabel('gibbonAdmissionsApplicationID', __('Application ID'));
                    $row->addTextField('gibbonAdmissionsApplicationID')->readOnly()->setValue($application['gibbonAdmissionsApplicationID']);
            }

            if (!empty($application['status'])) {
                $row = $form->addRow();
                    $row->addLabel('status', __('Status'));
                    $row->addTextField('status')->readOnly()->setValue($application['status']);
            }

            foreach ($this->pages as $formPage) {
                foreach ($this->fields as $field) {
                    if ($field['hidden'] == 'N') continue;
                    if ($field['pageNumber'] != $formPage['sequenceNumber']) continue;

                    $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                    $row = $fieldGroup->addFieldToForm($form, $field);
                }
            }
        }

        // Display all non-hidden fields
        foreach ($this->pages as $formPage) {
            foreach ($this->fields as $field) {
                if ($field['hidden'] == 'Y') continue;
                if ($field['pageNumber'] != $formPage['sequenceNumber']) continue;

                $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                $row = $fieldGroup->addFieldToForm($form, $field);
            }
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit(__('Submit'));

        return $form;
    }

    public function display()
    {
        $table = DataTable::createDetails('formBuilder');

        $table->setTitle(__($this->getDetail('name')));

        $addFieldToTable = function ($field) use (&$col, &$formPage, &$table) {
            if ($field['pageNumber'] != $formPage['sequenceNumber']) return;

            if ($field['fieldType'] == 'heading' || $field['fieldType'] == 'subheading') {
                $col = $table->addColumn($field['label'], __($field['label']));
                return;
            }
            
            if (empty($col)) {
                $col = $table->addColumn($formPage['name'], $formPage['name']);
            }

            $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
            $fieldOptions = $fieldGroup->getField($field['fieldName']) ?? [];

            $col->addColumn($field['fieldName'], __($field['label']))
                ->addClass(!empty($fieldOptions['columns']) ? 'col-span-'.$fieldOptions['columns'] : '');
        };

        // Display the Office-Only fields first
        if ($this->includeHidden) {
            $col = $table->addColumn('For Office Use', __('For Office Use'));

            foreach ($this->pages as $formPage) {
                foreach ($this->fields as $field) {
                    if ($field['hidden'] == 'N') continue;
                    $addFieldToTable($field);
                }
            }
        }

        // Display all non-hidden fields
        foreach ($this->pages as $formPage) {
            foreach ($this->fields as $field) {
                if ($field['hidden'] == 'Y') continue;
                $addFieldToTable($field);
            }
        }
        
        return $table;
    }
   
}
