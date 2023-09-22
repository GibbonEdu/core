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

namespace Gibbon\Forms\Builder\Fields;

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\PersonalDocumentHandler;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Domain\User\PersonalDocumentTypeGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\View\View;

class PersonalDocuments extends AbstractFieldGroup implements UploadableInterface
{
    protected $personalDocumentTypeGateway;
    protected $personalDocumentGateway;
    protected $personalDocumentHandler;
    
    public function __construct(PersonalDocumentTypeGateway $personalDocumentTypeGateway, PersonalDocumentGateway $personalDocumentGateway, PersonalDocumentHandler $personalDocumentHandler)
    {
        $this->personalDocumentTypeGateway = $personalDocumentTypeGateway;
        $this->personalDocumentGateway = $personalDocumentGateway;
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
        $params = $this->getParams($formBuilder, $field['fieldName']);
        $params['heading'] = __($field['label']);

        $this->personalDocumentHandler->addPersonalDocumentsToForm($form, $params['foreignTable'], $params['foreignTableID'], $params);

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
        $params = $this->getParams($formBuilder, $fieldName);
        $personalDocumentFail = false;

        if (empty($params['foreignTableID'])) return false;

        $this->personalDocumentHandler->updateDocumentsFromPOST($params['foreignTable'], $params['foreignTableID'], $params, $personalDocumentFail);

        return !$personalDocumentFail;
    }

    public function displayFieldValue(FormBuilderInterface $formBuilder, string $fieldName, array $field, &$data = [], View $view = null)
    {
        $params = $this->getParams($formBuilder, $fieldName);

        if (empty($view) || empty($params['foreignTableID'])) return '';

        $documents = $this->personalDocumentGateway->selectPersonalDocuments($params['foreignTable'], $params['foreignTableID'], $params)->fetchAll();

        return $view->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents, 'noTitle' => true]);
    }

    private function getParams(FormBuilderInterface $formBuilder, string $fieldName)
    {
        $roleCategory = str_replace(['Documents', '1', '2'], '', $fieldName);

        $params = [
            'foreignTable'    => $formBuilder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission',
            'foreignTableID'  => $formBuilder->getConfig('foreignTableID'),
            'applicationForm' => true,
            'class'           => $fieldName == 'parent2Documents' ? 'parentSection2' : ($fieldName == 'parent1Documents' ? 'parentSection1' : ''),
            $roleCategory     => true,
        ];
    
        if ($roleCategory == 'parent') {
            $params['prefix'] = $fieldName == 'parent1Documents' ? 'parent1' : 'parent2';
            $params['foreignTable'] .= ucfirst($params['prefix']);
        }

        return $params;
    }
}
