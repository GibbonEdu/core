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

namespace Gibbon\Forms\Builder\Storage;

use Gibbon\Forms\Builder\AbstractFormStorage;
use Gibbon\Domain\Forms\FormSubmissionGateway;

class FormDatabaseStorage extends AbstractFormStorage
{
    private $formSubmissionGateway;
    private $context;
    
    public function __construct(FormSubmissionGateway $formSubmissionGateway)
    {
        $this->formSubmissionGateway = $formSubmissionGateway;
    }

    public function setContext(string $gibbonFormID, ?string $gibbonFormPageID, string $foreignTable, string $foreignTableID, ?string $owner)
    {
        $this->context = [
            'gibbonFormID'     => $gibbonFormID,
            'gibbonFormPageID' => $gibbonFormPageID,
            'foreignTable'     => $foreignTable,
            'foreignTableID'   => $foreignTableID,
            'owner'            => $owner,
        ];

        return $this;
    }

    public function identify(string $identifier) : int
    {
        $values = $this->formSubmissionGateway->getFormSubmissionByIdentifier($this->context['gibbonFormID'], $identifier, ['gibbonFormSubmissionID']);

        return $values['gibbonFormSubmissionID'] ?? 0;
    }
    
    public function save(string $identifier) : bool
    {
        $values = $this->formSubmissionGateway->getFormSubmissionByIdentifier($this->context['gibbonFormID'], $identifier);
        
        if (!empty($values)) {
            // Update the existing submission
            $existingData = json_decode($values['data'] ?? '', true);
            $data = array_merge($existingData, $this->getData());

            $saved = $this->formSubmissionGateway->update($values['gibbonFormSubmissionID'], [
                'data'              => json_encode($data),
                'status'            => $data['status'] ?? 'Incomplete',
                'timestampModified' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Create a new submission
            $saved = $this->formSubmissionGateway->insert($this->context + [
                'identifier'       => $identifier,
                'data'             => json_encode($this->getData()),
                'timestampCreated' => date('Y-m-d H:i:s'),
            ]);
        }

        return !empty($saved);
    }

    public function load(string $identifier) : bool
    {
        $values = $this->formSubmissionGateway->getFormSubmissionByIdentifier($this->context['gibbonFormID'], $identifier);
        $this->setData(json_decode($values['data'] ?? '', true) ?? []);

        return !empty($values);
    }
}
