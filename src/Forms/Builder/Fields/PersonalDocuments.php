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

class PersonalDocuments extends AbstractFieldGroup
{
    protected $personalDocumentGateway;
    protected $personalDocumentHandler;

    public function __construct(PersonalDocumentTypeGateway $personalDocumentTypeGateway, PersonalDocumentHandler $personalDocumentHandler)
    {
        $this->personalDocumentTypeGateway = $personalDocumentTypeGateway;
        $this->personalDocumentHandler = $personalDocumentHandler;

        // $criteria = $this->personalDocumentTypeGateway->newQueryCriteria();
        // $personalDocumentTypes = $this->personalDocumentTypeGateway->queryDocumentTypes($criteria)->toArray();

        // foreach ($personalDocumentTypes as $field) {
        //     $id = $field['gibbonPersonalDocumentTypeID'];
        //     $this->fields[$id] = [
        //         'type'                => $field['type'],
        //         'label'               => __($field['name']),
        //         'description'         => __($field['description']),
        //         'options'             => [],
        //         'activePersonStudent' => $field['activePersonStudent'],
        //         'activePersonParent'  => $field['activePersonParent'],
        //         'activePersonStaff'   => $field['activePersonStaff'],
        //         'activePersonOther'   => $field['activePersonOther'],
        //     ];
        // }

        $this->fields = [
            'staff' => [
                'label' => __('Staff'),
                'type'  => 'personalDocument',
            ],
            'student' => [
                'label' => __('Student'),
                'type'  => 'personalDocument',
            ],
            'parent' => [
                'label' => __('Parent'),
                'type'  => 'personalDocument',
            ],
            'other' => [
                'label' => __('Other'),
                'type'  => 'personalDocument',
            ],
        ];
    }

    public function getDescription() : string
    {
        return __('Personal Documents are attached to users and can be managed in {link}.', ['link' => Format::link('./index.php?q=/modules/User Admin/personalDocuments.php', __('User Admin').' > '.__('Personal Documents'))]);
    }

    public function addFieldToForm(Form $form, array $field): Row
    {
        $params = [$field['fieldName'] => true, 'applicationForm' => true, 'class' => ''];
        $this->personalDocumentHandler->addPersonalDocumentsToForm($form, null, null, $params);

        return $form->getRow();
    }
}
