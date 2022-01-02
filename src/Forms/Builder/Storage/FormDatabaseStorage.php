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

namespace Gibbon\Forms\Builder\Storage;

use Gibbon\Forms\Builder\AbstractFormStorage;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Domain\Forms\FormSubmissionGateway;

class FormDatabaseStorage extends AbstractFormStorage
{
    private $formSubmissionGateway;
    private $details;
    
    public function __construct(FormSubmissionGateway $formSubmissionGateway)
    {
        $this->formSubmissionGateway = $formSubmissionGateway;
    }

    public function setSubmissionDetails(FormBuilderInterface $builder, string $foreignTable, string $foreignTableID)
    {
        $this->details = [
            'gibbonFormID'     => $builder->getDetail('gibbonFormID'),
            'foreignTable'     => $foreignTable,
            'foreignTableID'   => $foreignTableID,
            'owner'            => $builder->getDetail('owner'),
        ];

        return $this;
    }
    
    public function save(string $identifier) : bool
    {
        $values = $this->formSubmissionGateway->getFormSubmissionByIdentifier($this->details['gibbonFormID'], $identifier);
        
        if (!empty($values)) {
            // Update the existing submission
            $existingData = json_decode($values['data'] ?? '', true);
            $data = array_merge($existingData, $this->getData());

            $saved = $this->formSubmissionGateway->update($values['gibbonFormSubmissionID'], [
                'data'              => json_encode($data),
                'timestampModified' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Create a new submission
            $saved = $this->formSubmissionGateway->insert($this->details + [
                'identifier'       => $identifier,
                'data'             => json_encode($this->getData()),
                'timestampCreated' => date('Y-m-d H:i:s'),
            ]);
        }

        return !empty($saved);
    }

    public function load(string $identifier) : bool
    {
        $values = $this->formSubmissionGateway->getFormSubmissionByIdentifier($this->details['gibbonFormID'], $identifier);
        $data = json_decode($values['data'] ?? '', true);

        $this->setData($data ?? []);

        return !empty($values);
    }
}