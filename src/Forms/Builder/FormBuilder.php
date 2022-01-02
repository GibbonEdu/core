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

class FormBuilder implements ContainerAwareInterface, FormBuilderInterface
{
    use ContainerAwareTrait;

    protected $formGateway;
    protected $formPageGateway;

    protected $gibbonFormID;
    protected $gibbonFormPageID;
    protected $pageNumber;
    protected $finalPageNumber;

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

    public function getFieldGroupClass($fieldGroup)
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
    
    public function populate(string $gibbonFormID, int $pageNumber = 1, string $identifier = null)
    {
        $this->gibbonFormID = $gibbonFormID;
        $this->pageNumber = $pageNumber;
        
        // Load form details
        $this->details = $this->formGateway->getByID($this->gibbonFormID);
        $this->config = json_decode($this->details['config'] ?? '', true);

        // Load all page data
        $criteria = $this->formPageGateway->newQueryCriteria()->sortBy('sequenceNumber', 'ASC');
        $this->pages = $this->formPageGateway->queryPagesByForm($criteria, $this->gibbonFormID)->toArray();
        $this->gibbonFormPageID = $this->formPageGateway->getPageIDByNumber($this->gibbonFormID, $this->pageNumber);

        // Determine the final page number
        $finalPage = end($this->pages);
        $this->finalPageNumber = $finalPage['sequenceNumber'] ?? $this->pageNumber;

        // Load all field data
        $this->fields = $this->formGateway->selectFieldsByForm($this->gibbonFormID)->fetchGroupedUnique();
    
        return $this;
    }

    public function build(string $action)
    {
        $form = MultiPartForm::create('formBuilder', $action);
        $form->setFactory(DatabaseFormFactory::create($this->getContainer()->get('db')));

        $form->addHiddenValue('gibbonFormID', $this->gibbonFormID);
        $form->addHiddenValue('gibbonFormPageID', $this->gibbonFormPageID);
        $form->addHiddenValue('page', $this->pageNumber);
        
        // Add pages to the multi-part form
        if (count($this->pages) > 1) {
            $form->setCurrentPage($this->pageNumber);

            foreach ($this->pages as $formPage) {
                $pageUrl = Url::fromModuleRoute('System Admin', 'formBuilder_preview.php')->withQueryParams(['gibbonFormID' => $this->gibbonFormID, 'page' => $formPage['sequenceNumber']]);
                $form->addPage($formPage['sequenceNumber'], $formPage['name'], $pageUrl);
            }
        }

        // Form is not complete, add fields to current page
        if ($this->pageNumber <= $this->finalPageNumber) {
            foreach ($this->fields as $field) {
                if ($field['pageNumber'] != $this->pageNumber) continue;

                $fieldGroup = $this->getFieldGroupClass($field['fieldGroup']);
                $row = $fieldGroup->addFieldToForm($form, $field);
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit($this->pageNumber == $this->finalPageNumber ? __('Submit') : __('Next'));
        }

        return $form;
    }

    public function validate(array $data)
    {
        $validated = true;
        foreach ($this->fields as $fieldName => $field) {
            if (!isset($data[$fieldName])) continue;

            if ($field['required'] != 'N' && empty($data[$fieldName])) {
                $validated = false;
            }
        }

        return $validated;
    }
}
