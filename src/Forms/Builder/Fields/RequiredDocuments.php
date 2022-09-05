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

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\FileUploader;
use Gibbon\Forms\Layout\Row;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Forms\FormUploadGateway;
use Gibbon\Forms\Builder\AbstractFieldGroup;
use Gibbon\Forms\Builder\FormBuilderInterface;

class RequiredDocuments extends AbstractFieldGroup implements UploadableInterface
{
    protected $view;
    protected $session;
    protected $fileUploader;

    public function __construct(Session $session, FormUploadGateway $formUploadGateway, FileUploader $fileUploader, View $view)
    {
        $this->view = $view;
        $this->session = $session;
        $this->formUploadGateway = $formUploadGateway;
        $this->fileUploader = $fileUploader;
    }

    public function getDescription() : string
    {
        return __('Documents which must be submitted electronically, either with the form at the time of submission, or afterwards through the form interface.');
    }

    public function getField($fieldName) : array 
    {
        return ['type'  => 'files', 'columns' => 3];
    }

    public function addFieldToForm(FormBuilderInterface $formBuilder, Form $form, array $field): Row
    {
        $row = $form->addRow();

        $documents = array_map('trim', explode(',', $field['options'] ?? ''));
        if (empty($documents)) return $row;

        $required = $this->getRequired($formBuilder, $field);

        $foreignTable = $formBuilder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
        $foreignTableID = $formBuilder->getConfig('foreignTableID');
        $uploads = $this->formUploadGateway->selectAllUploadsByContext($formBuilder->getFormID(), $foreignTable, $foreignTableID)->fetchKeyPair();

        $col = $row->addColumn();
            $col->addLabel($field['fieldName'], __($field['label']))->description(__($field['description'] ?? ''));
            $col->addDocuments($field['fieldName'], $documents, $this->view, $this->session->get('absoluteURL'), $formBuilder->getConfig('mode'))
                ->required($required)
                ->setAttachments($uploads);

        return $row;
    }

    public function getFieldDataFromPOST(string $fieldName, array $field) 
    {
        return [];
    }

    public function uploadFieldData(FormBuilderInterface $formBuilder, string $fieldName, array $field)
    {
        $requiredDocumentFail = false;

        $documents = array_map('trim', explode(',', $field['options'] ?? ''));
        if (empty($documents)) return true;

        $foreignTable = $formBuilder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
        $foreignTableID = $formBuilder->getConfig('foreignTableID');
        if (empty($foreignTableID)) return false;
        
        foreach ($documents as $index => $document) {
            $documentFieldName = $fieldName.$index.'filePath';

            $filePath = $_POST[$documentFieldName.'File'] ?? '';

            // Upload attached file, if there is one
            if (!empty($_FILES[$documentFieldName]['tmp_name'])) {
                $file = $_FILES[$documentFieldName] ?? null;
                $filePath = $this->fileUploader->uploadFromPost($file, $documentFieldName);
            }

            // Update the database record in gibbonFormUpload
            $existing = $this->formUploadGateway->getUploadByContext($formBuilder->getFormID(), $foreignTable, $foreignTableID, $document);
            if (!empty($existing) && $existing['path'] != $filePath) {
                if (file_exists($this->session->get('absolutePath').'/'.$existing['path'])) {
                    unlink($this->session->get('absolutePath').'/'.$existing['path']);
                }

                if (empty($filePath)) {
                    $this->formUploadGateway->delete($existing['gibbonFormUploadID']);
                } else {
                    $this->formUploadGateway->update($existing['gibbonFormUploadID'], [
                        'gibbonFormFieldID' => $field['gibbonFormFieldID'] ?? null,
                        'path'              => $filePath,
                        'timestamp'         => date('Y-m-d H:i:s'),
                    ]);
                }
            } elseif (empty($existing) && !empty($filePath)) {
                $this->formUploadGateway->insert([
                    'gibbonFormID'      => $formBuilder->getFormID(),
                    'gibbonFormFieldID' => $field['gibbonFormFieldID'] ?? null,
                    'foreignTable'      => $foreignTable,
                    'foreignTableID'    => $foreignTableID,
                    'name'              => $document,
                    'path'              => $filePath,
                ]);
            }
        }

        return !$requiredDocumentFail;
    }

    public function displayFieldValue(FormBuilderInterface $formBuilder, string $fieldName, array $field, &$data = [], View $view = null)
    {
        $foreignTable = $formBuilder->getDetail('type') == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
        $foreignTableID = $formBuilder->getConfig('foreignTableID');
        
        if (empty($view) || empty($foreignTableID)) return '';

        $uploads = $this->formUploadGateway->selectAllUploadsByContext($formBuilder->getFormID(), $foreignTable, $foreignTableID);

        return $view->fetchFromTemplate('requiredDocuments.twig.html', ['documents' => $uploads]);
    }
}
