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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Builder\AbstractFieldGroup;

class DocumentsFields extends AbstractFieldGroup
{
    protected $settingGateway;

    public function __construct(SettingGateway $settingGateway)
    {
        $this->settingGateway = $settingGateway;
        $this->fields = [
            'headingPrivacyStatement' => [
                'label'       => __('Privacy Statement'),
                'description' => __('This is example text. Edit it to suit your school context.'),
                'type'        => 'subheading',
            ],
            'privacyOptions' => [
                'label'       => __('Privacy Options'),
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('');
    }

    public function addFieldToForm(Form $form, array $field) : Row
    {
        $required = $field['required'] != 'N';

        $row = $form->addRow();

        $requiredDocuments = $this->settingGateway->getSettingByScope('Application Form', 'requiredDocuments');

        // if (!empty($requiredDocuments)) {
        //     $requiredDocumentsText = $settingGateway->getSettingByScope('Application Form', 'requiredDocumentsText');
        //     $requiredDocumentsCompulsory = $settingGateway->getSettingByScope('Application Form', 'requiredDocumentsCompulsory');

        //     $heading = $form->addRow()->addHeading('Supporting Documents', __('Supporting Documents'));

        //     if (!empty($requiredDocumentsText)) {
        //         $heading->append($requiredDocumentsText);

        //         if ($requiredDocumentsCompulsory == 'Y') {
        //             $heading->append(__('All documents must all be included before the application can be submitted.'));
        //         } else {
        //             $heading->append(__('These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here.'));
        //         }
        //     }

        //     $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

        //     $requiredDocumentsList = explode(',', $requiredDocuments);

        //     for ($i = 0; $i < count($requiredDocumentsList); $i++) {
        //         $form->addHiddenValue('fileName'.$i, $requiredDocumentsList[$i]);

        //         $row = $form->addRow();
        //             $row->addLabel('file'.$i, $requiredDocumentsList[$i]);
        //             $row->addFileUpload('file'.$i)
        //                 ->accepts($fileUploader->getFileExtensions())
        //                 ->setRequired($requiredDocumentsCompulsory == 'Y')
        //                 ->setMaxUpload(false);
        //     }

        //     $row = $form->addRow()->addContent(getMaxUpload($guid));
        //     $form->addHiddenValue('fileCount', count($requiredDocumentsList));
        // }

        return $row;
    }
}
