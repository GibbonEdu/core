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
use Gibbon\Services\Format;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\PersonalDocumentHandler;
use Gibbon\Domain\User\PersonalDocumentTypeGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;
class PersonalDocuments extends AbstractFieldGroup implements UploadableInterface
{
    protected $personalDocumentGateway;
    protected $personalDocumentHandler;

    public function __construct(PersonalDocumentTypeGateway $personalDocumentTypeGateway, PersonalDocumentHandler $personalDocumentHandler)
    {
        $this->personalDocumentTypeGateway = $personalDocumentTypeGateway;
        $this->personalDocumentHandler = $personalDocumentHandler;

        $this->fields = [
            'studentDocuments' => [
                'label' => __('Student'),
                'type'  => 'personalDocument',
                'columns' => 3,
            ],
            'parent1Documents' => [
                'label' => __('Parent 1'),
                'type'  => 'personalDocument',
                'columns' => 3,
            ],
            'parent2Documents' => [
                'label' => __('Parent 2'),
                'type'  => 'personalDocument',
                'columns' => 3,
            ],
            'staffDocuments' => [
                'label' => __('Staff'),
                'type'  => 'personalDocument',
                'columns' => 3,
            ],
            'otherDocuments' => [
                'label' => __('Other'),
                'type'  => 'personalDocument',
                'columns' => 3,
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Personal Documents are attached to users and can be managed in {link}.', ['link' => Format::link('./index.php?q=/modules/User Admin/personalDocuments.php', __('User Admin').' > '.__('Personal Documents'))]);
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field): Row
    {
        $foreignTable = $formBuilder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
        $foreignTableID = $formBuilder->getConfig('foreignTableID');
        $roleCategory = str_replace(['Documents', '1', '2'], '', $field['fieldName']);

        $params = [$roleCategory => true, 'applicationForm' => true, 'class' => '', 'heading' => __($field['label'])];
        if ($roleCategory == 'parent') {
            $params['prefix'] = $field['fieldName'] == 'parent1Documents' ? 'parent1' : 'parent2';
            $foreignTable .= ucfirst($params['prefix']);
        }

        $this->personalDocumentHandler->addPersonalDocumentsToForm($form, $foreignTable, $foreignTableID, $params);

        return $form->getRow();
    }

    public function getFieldDataFromPOST(string $fieldName, array $field)    
    {
        $roleCategory = str_replace(['Documents', '1', '2'], '', $fieldName);
        $documents = $this->personalDocumentTypeGateway->selectBy([])->fetchAll();

        $data = [];
        foreach ($documents as $document) {
            $id = $document['gibbonPersonalDocumentTypeID'];
            $prefix = $roleCategory == 'parent' 
                ? ($fieldName == 'parent1Documents' ? 'parent1' : 'parent2')
                : '';


            if (empty($document['activePerson'.ucfirst($roleCategory)])) continue;
            if (empty($_POST[$prefix.'document'][$id])) continue;

            $data[$fieldName][$id] = $_POST[$prefix.'document'][$id];
        }

        return $data;
    }

    public function uploadFieldData(FormBuilderInterface $formBuilder, string $fieldName, array $field)
    {
        $personalDocumentFail = false;
        
        $foreignTable = $formBuilder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
        $foreignTableID = $formBuilder->getConfig('foreignTableID');
        $roleCategory = str_replace(['Documents', '1', '2'], '', $fieldName);

        if (empty($foreignTableID)) return false;

        $params = [$roleCategory => true, 'applicationForm' => true, 'class' => ''];
        if ($roleCategory == 'parent') {
            $params['prefix'] = $fieldName == 'parent1Documents' ? 'parent1' : 'parent2';
            $foreignTable .= ucfirst($params['prefix']);
        }

        $this->personalDocumentHandler->updateDocumentsFromPOST($foreignTable, $foreignTableID, $params, $personalDocumentFail);

        return !$personalDocumentFail;
    }

    public function displayFieldValue(string $fieldName, array $field, &$data = [])
    {
        $documents = $this->personalDocumentGateway->selectPersonalDocuments($foreignTable, $foreignTableID, $params)->fetchAll();
        if (empty($documents)) return;

        $prefix = $params['prefix'] ?? '';

        if (!empty($documents)) {
            $col = $form->addRow()->setClass($params['class'] ?? '')->addColumn();
                $col->addLabel($prefix.'document', $params['heading'] ?? __('Personal Documents'));
                $col->addPersonalDocuments($prefix.'document', $documents, $this->view, $this->settingGateway);
        }

        return '';
    }
}
