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

use Gibbon\Forms\Form;
use Gibbon\Forms\MultiPartForm;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Domain\Forms\FormFieldGateway;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\Exception\NotFoundException;
use Gibbon\Http\Url;


class FormBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $formGateway;
    protected $formPageGateway;
    protected $formFieldGateway;

    protected $form;

    protected $fieldGroups;

    public function __construct(FormGateway $formGateway, FormPageGateway $formPageGateway, FormFieldGateway $formFieldGateway)
    {
        $this->formGateway = $formGateway;
        $this->formPageGateway = $formPageGateway;
        $this->formFieldGateway = $formFieldGateway;
    }

    public function build(string $gibbonFormID, int $pageNumber, string $action)
    {
        $this->form = MultiPartForm::create('formBuilder', $action);
        $this->form->setFactory(DatabaseFormFactory::create($this->getContainer()->get('db')));

        $criteria = $this->formPageGateway->newQueryCriteria()
            ->sortBy('sequenceNumber', 'ASC');
        
        $gibbonFormPageID = $this->formPageGateway->getPageIDByNumber($gibbonFormID, $pageNumber);
        $pages = $this->formPageGateway->queryPagesByForm($criteria, $gibbonFormID)->toArray();
        $finalPage = end($pages);

        if (count($pages) > 1) {
            $this->form->setCurrentPage($pageNumber);

            foreach ($pages as $formPage) {
                $pageUrl = Url::fromModuleRoute('System Admin', 'formBuilder_preview.php')->withQueryParams(['gibbonFormID' => $gibbonFormID, 'page' => $formPage['sequenceNumber']]);
                $this->form->addPage($formPage['sequenceNumber'], $formPage['name'], $pageUrl);
            }
        }

        // If form is not complete, add fields to current page
        if ($pageNumber <= $finalPage['sequenceNumber']) {
            $fields = $this->formFieldGateway->queryFieldsByPage($criteria, $gibbonFormPageID);

            foreach ($fields as $field) {
                $fieldGroup = $this->getFieldGroupClass($field['fieldGroup']);
                $row = $fieldGroup->addFieldToForm($this->form, $field);
            }

            $row = $this->form->addRow();
                $row->addFooter();
                $row->addSubmit($pageNumber == $finalPage['sequenceNumber'] ? __('Submit') : __('Next'));
        } else {
            // Form is complete, display any results?
            
        }

        return $this->form;
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
            return null;
        }
    }
}
