<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\View\View;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Form;
use Gibbon\Forms\MultiPartForm;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Fields\NullFieldGroup;
use Gibbon\Forms\Builder\Fields\FieldGroupInterface;
use Gibbon\Forms\Builder\Fields\UploadableInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\Exception\NotFoundException;
use Gibbon\Contracts\Services\Session;

class FormBuilder implements ContainerAwareInterface, FormBuilderInterface
{
    use ContainerAwareTrait;

    protected $session;
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
    protected $urlParams = [];
    protected $config = [];
    protected $type = '';

    protected $fieldGroups;

    public function __construct(Session $session, FormGateway $formGateway, FormPageGateway $formPageGateway)
    {
        $this->session = $session;
        $this->formGateway = $formGateway;
        $this->formPageGateway = $formPageGateway;
        $this->details['honeyPot'] = 'emailAddress';
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

    public function addConfig($values)
    {
        $this->config = array_merge($this->config ?? [], $values ?? []);
    }

    public function getFormID()
    {
        return $this->gibbonFormID;
    }

    public function getPageID()
    {
        return $this->details['gibbonFormPageID'] ?? null;
    }

    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    public function getFinalPageNumber()
    {
        return $this->finalPageNumber;
    }

    public function getCurrentPage() : array
    {
        return $this->pages[$this->pageNumber] ?? [];
    }

    public function includeHidden(bool $includeHidden = true)
    {
        $this->includeHidden = $includeHidden;

        return $this;
    }

    public function isFieldHidden(array $field) : bool
    {
        return $this->includeHidden != ($field['hidden'] == 'Y');
    }

    public function hasHiddenValue(array $field) : bool
    {
        return $field['hidden'] == 'Y' && !$this->includeHidden && !empty($field['defaultValue']);
    }

    public function getFieldGroup($fieldGroup): FieldGroupInterface
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
        $this->details = array_merge($this->details, $this->formGateway->getByID($this->gibbonFormID));
        $this->config = json_decode($this->details['config'] ?? '', true);

        // Load all page data
        $this->pages = $this->formPageGateway->selectPagesByForm($this->gibbonFormID)->fetchGroupedUnique();
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
            if ($this->isFieldHidden($field) && !$this->hasHiddenValue($field)) continue;
            if ($field['pageNumber'] != $this->pageNumber && $this->pageNumber > 0) continue;

            $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
            $fieldInfo = $fieldGroup->getField($fieldName);
            $fieldValue = $fieldGroup->getFieldDataFromPOST($fieldName, $field);

            if (!is_null($fieldValue) || (!empty($field['type']) && $field['type'] == 'checkbox')) {
                $data[$fieldName] = $fieldValue;
            }

            if (!empty($fieldInfo['acquire'])) {
                foreach ($fieldInfo['acquire'] as $subFieldName => $subFieldType) {
                    $fieldValue = $fieldGroup->getFieldDataFromPOST($subFieldName, ['fieldType' => $subFieldType]);

                    if (!is_null($fieldValue)) {
                        $data[$subFieldName] = $fieldValue;
                    }
                }
            }
        }

        return $data;
    }

    public function upload()
    {
        $partialFail = false;

        foreach ($this->fields as $fieldName => $field) {
            if ($this->isFieldHidden($field)) continue;
            if ($field['pageNumber'] != $this->pageNumber && $this->pageNumber > 0) continue;

            $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
            if (!$fieldGroup instanceof UploadableInterface) continue;

            $success = $fieldGroup->uploadFieldData($this, $fieldName, $field);
            $partialFail &= !$success;
        }

        return !$partialFail;
    }

    public function validate(array $data)
    {
        $invalid = [];

        foreach ($this->fields as $fieldName => $field) {
            if ($this->isFieldHidden($field)) continue;
            if ($this->includeHidden != ($field['hidden'] == 'Y')) continue;
            if ($field['pageNumber'] != $this->pageNumber && $this->pageNumber > 0) continue;

            $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
            $fieldInfo = $fieldGroup->getField($fieldName);
            
            if (!$fieldGroup->shouldValidate($this, $data, $fieldName)) continue;

            $fieldValue = &$data[$fieldName];

            if (empty($fieldValue) && !empty($fieldInfo['acquire']) && is_array($fieldInfo['acquire'])) {
                $subFieldKey = key($fieldInfo['acquire']);
                $fieldValue = &$data[$subFieldKey] ?? null;
            }

            if ($field['required'] != 'N' && (is_null($fieldValue) || $fieldValue == '' || $fieldValue == 'Please select...')) {
                $invalid[] = $fieldName;
            }
        }

        return $invalid;
    }

    public function build(Url $action, Url $pageUrl)
    {
        $form = MultiPartForm::create('formBuilder', (string)$action);
        $form->setFactory(DatabaseFormFactory::create($this->getContainer()->get('db')));

        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValue('gibbonFormID', $this->gibbonFormID);
        $form->addHiddenValue('gibbonFormPageID', $this->getDetail('gibbonFormPageID'));
        $form->addHiddenValue('page', $this->pageNumber);
        $form->addHiddenValue('direction', 'next');
        $form->addHiddenValues($this->urlParams);

        // Get the current page data
        $currentPage = $this->pages[$this->pageNumber];

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
                if ($field['pageNumber'] != $this->pageNumber) continue;
                if ($field['hidden'] == 'Y' && !$this->includeHidden) {
                    if (!empty($field['defaultValue'])) {
                        $form->addHiddenValue($field['fieldName'], $field['defaultValue']);
                    }
                    continue;
                }

                $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                $row = $fieldGroup->addFieldToForm($this, $form, $field);

                $invalid = in_array($field['fieldName'], $this->getConfig('invalid', []));
                $row->addClass($invalid ? 'bg-red-200 text-red-700' : '');
            }

            // Honey pot field?
            $form->addRow()->addClass('hidden')->addTextField($this->getDetail('honeyPot'));

            // Add submit with back button
            $button = $this->pageNumber > 1 ?"<a href='".(string)$pageUrl->withQueryParams($this->urlParams + ['gibbonFormID' => $this->gibbonFormID, 'page' => ($this->pageNumber-1)])->withAbsoluteUrl()."' class='button inline-block rounded-sm border-gray-400 text-gray-400 text-center w-24 mr-4'>".__('Back')."</a>" : '';

            $row = $form->addRow();
                $row->addFooter()->prepend($button);
                $row->addSubmit($this->pageNumber == $this->finalPageNumber ? __('Submit') : __('Next'));
        }

        return $form;
    }

    public function edit(Url $action)
    {
        $form = Form::create('formBuilder', (string)$action);
        $form->setFactory(DatabaseFormFactory::create($this->getContainer()->get('db')));

        $form->addHiddenValue('address', $this->session->get('address'));
        $form->addHiddenValue('gibbonFormID', $this->gibbonFormID);
        $form->addHiddenValue('gibbonFormPageID', $this->getDetail('gibbonFormPageID'));
        $form->addHiddenValue('page', -1);
        $form->addHiddenValues($this->urlParams);

        // Display the Office-Only fields first
        if ($this->includeHidden) {
            $form->addRow()->addHeading('For Office Use', __('For Office Use'));

            foreach ($this->pages as $formPage) {
                foreach ($this->fields as $field) {
                    if ($field['pageNumber'] != $formPage['sequenceNumber']) continue;
                    if ($field['hidden'] == 'N') continue;

                    $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                    $row = $fieldGroup->addFieldToForm($this, $form, $field);
                }
            }
        }

        // Display all non-hidden fields
        foreach ($this->pages as $formPage) {
            foreach ($this->fields as $field) {
                if ($field['pageNumber'] != $formPage['sequenceNumber']) continue;
                if ($field['hidden'] == 'Y') continue;

                $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
                $row = $fieldGroup->addFieldToForm($this, $form, $field);
            }
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit(__('Submit'));

        return $form;
    }

    public function display()
    {
        $view = $this->getContainer()->get(View::class);
        $table = DataTable::createDetails('formBuilder');

        $table->setTitle(__($this->getDetail('name')));

        $addFieldToTable = function ($field) use (&$col, &$formPage, &$table, &$lastType, &$view) {
            if ($field['pageNumber'] != $formPage['sequenceNumber']) return;

            if ($field['fieldType'] == 'heading' ) {
                $col = $table->addColumn($field['label'], __($field['label']));
                return;
            }

            if (empty($col)) {
                $col = $table->addColumn($formPage['name'], $formPage['name']);
            }

            if ($field['fieldType'] == 'subheading' ) {
                $border = $col->hasNestedColumns() ? 'border-t' : '';
                $col->addColumn($field['label'], __($field['label']))->addClass('uppercase bg-gray-300 col-start-0 col-span-3 pt-3 '.$border);
                return;
            }

            if ($field['fieldType'] == 'layout' || $field['fieldName'] == 'secondParent') {
                return;
            }

            $fieldGroup = $this->getFieldGroup($field['fieldGroup']);
            $fieldOptions = $fieldGroup->getField($field['fieldName']) ?? [];

            $col->addColumn($field['fieldName'], __($field['label']))
                ->addClass(!empty($fieldOptions['columns']) ? 'col-span-'.$fieldOptions['columns'] : '')
                ->format(function ($values) use (&$field, &$fieldGroup, &$view) {
                    return $fieldGroup->displayFieldValue($this, $field['fieldName'], $field, $values, $view);
                });
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

    public function getReturns()
    {
        $returnExtra = '';

        if ($this->hasConfig('foreignTableID')) {
            $returnExtra .= '<br/><br/>'.__('If you need to contact the school in reference to this application, please quote the following number:').' <b><u>'.str_pad($this->getConfig('foreignTableID'),10,'0', STR_PAD_LEFT).'</b></u>.';
        }

        if ($this->session->has('organisationAdmissionsName') && $this->session->has('organisationAdmissionsEmail')) {
            $returnExtra .= '<br/><br/>'.sprintf(__('Please contact %1$s if you have any questions, comments or complaints.'), "<a href='mailto:".$this->session->get('organisationAdmissionsEmail')."'>".$this->session->get('organisationAdmissionsName').'</a>');
        }

        return [
            'error3' => __('Your submitted data has been saved, however one or more required fields were incomplete. Please check the highlighted fields and submit the form again.'),
            'success0' => __('Your application was successfully submitted. Our admissions team will review your application and be in touch in due course.').$returnExtra,
            'success1' => __('Your application was successfully submitted and payment has been made to your credit card. Our admissions team will review your application and be in touch in due course.').$returnExtra,
            'success2' => __('Your application was successfully submitted, but payment could not be made to your credit card. Our admissions team will review your application and be in touch in due course.').$returnExtra,
            'success3' => __('Your application was successfully submitted, payment has been made to your credit card, but there has been an error recording your payment. Please print this screen and contact the school ASAP. Our admissions team will review your application and be in touch in due course.').$returnExtra,
            'success4' => __("Your application was successfully submitted, but payment could not be made as the payment gateway does not support the system's currency. Our admissions team will review your application and be in touch in due course.").$returnExtra,
            'success5' => __('Your application was successfully submitted, but payment options were unavailable at this time. Our admissions team will review your application and be in touch in due course.'),
        ];
    }

    public function getJavascript()
    {
        if (!empty($_GET['return']) && stripos($_GET['return'], 'success') !== false) {
            $output = "";
        } else {
            $output = "
            $('input,textarea,select').on('input', function() {
                window.onbeforeunload = function(event) {
                    if (event.explicitOriginalTarget.value=='Submit' || event.explicitOriginalTarget.value=='Next') return;
                    return '".__('There are unsaved changes on this page.')."';
                };
            });
        ";
        }

        return "<script type='text/javascript'>{$output}</script>";
    }
}
