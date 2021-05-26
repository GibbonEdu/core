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

namespace Gibbon\Forms;

use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Domain\User\PersonalDocumentGateway;

class PersonalDocumentHandler
{
    protected $personalDocumentGateway;
    protected $documents;
    protected $fields;

    public function __construct(PersonalDocumentGateway $personalDocumentGateway, FileUploader $fileUploader)
    {
        $this->personalDocumentGateway = $personalDocumentGateway;
        $this->fileUploader = $fileUploader;

        $this->documents = [
            'Passport' => __('Passport'),
            'ID Card'  => __('ID Card'),
            'Document' => __('Document'),
        ];

        $this->fields = [
            'documentName'   => __('Name on Document'),
            'documentNumber' => __('Document Number'),
            'documentType'   => __('Residency/Visa Type'),
            'country'        => __('Issuing Country'),
            'dateIssue'      => __('Issue Date'),
            'dateExpiry'     => __('Expiry Date'),
            'filePath'       => __('File Upload'),
        ];
    }

    public function getDocuments()
    {
        return $this->documents;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function updateDocumentsFromPOST($foreignTable = null, $foreignTableID = null, $params = [], &$personalDocumentFail = false)
    {
        $documents = $this->personalDocumentGateway->selectPersonalDocuments(null, null, $params);
        if (empty($documents)) return;

        foreach ($documents as $document) {
            $fields = json_decode($document['fields']);
            $data = [];

            foreach ($fields as $field) {
                $value = $_POST['document'][$document['gibbonPersonalDocumentTypeID']][$field] ?? null;

                if ($field == 'dateIssue' || $field == 'dateExpiry') {
                    // Handle date conversion
                    $data[$field] = !empty($value) ? Format::dateConvert($value) : null;
                } elseif ($field == 'filePath') {
                    // Handle file uploads
                    $file = $_FILES['document'.$document['gibbonPersonalDocumentTypeID'].$field] ?? null;
                    $attachment = $_POST['attachment'][$document['gibbonPersonalDocumentTypeID']][$field] ?? null;

                    if (!empty($file['tmp_name'])) {
                        $this->fileUploader->setFileSuffixType(FileUploader::FILE_SUFFIX_ALPHANUMERIC);
                        $data[$field] = $this->fileUploader->uploadFromPost($file, $foreignTable.$foreignTableID.'_scan');

                        if (empty($value)) {
                            $personalDocumentFail = true;
                        }
                    } else {
                        $data[$field] = $attachment;
                    }
                } else {
                    // Handle all other data
                    $data[$field] = $value;
                }
            }

            $omit = $_POST['document'][$document['gibbonPersonalDocumentTypeID']]['omit'] ?? null;
            $exists = $_POST['document'][$document['gibbonPersonalDocumentTypeID']]['gibbonPersonalDocumentID'] ?? null;

            // Skip any documents that are entirely empty
            if (count(array_filter($data)) == 0 && $omit != 'Y' && !$exists) continue;

            $data['gibbonPersonalDocumentTypeID'] = $document['gibbonPersonalDocumentTypeID'];
            $data['foreignTable'] = $foreignTable;
            $data['foreignTableID'] = $foreignTableID;
            $data['timestamp'] = date('Y-m-d H:i:s');

            $success = $this->personalDocumentGateway->insertAndUpdate($data, $data);
            $personalDocumentFail &= !$success;
        }
    }
}
