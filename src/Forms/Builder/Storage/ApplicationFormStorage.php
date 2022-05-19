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
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

class ApplicationFormStorage extends AbstractFormStorage
{
    private $admissionsApplicationGateway;
    private $context;
    
    public function __construct(AdmissionsApplicationGateway $admissionsApplicationGateway)
    {
        $this->admissionsApplicationGateway = $admissionsApplicationGateway;
    }

    public function setContext(FormBuilderInterface $builder, string $foreignTable, string $foreignTableID, string $owner)
    {
        $this->context = [
            'gibbonFormID'     => $builder->getDetail('gibbonFormID'),
            'gibbonFormPageID' => $builder->getDetail('gibbonFormPageID'),
            'foreignTable'     => $foreignTable,
            'foreignTableID'   => $foreignTableID,
            'owner'            => $owner,
        ];

        return $this;
    }

    public function identify(string $identifier) : int
    {
        $values = $this->admissionsApplicationGateway->getApplicationByIdentifier($this->context['gibbonFormID'], $identifier, ['gibbonAdmissionsApplicationID']);

        return $values['gibbonAdmissionsApplicationID'] ?? 0;
    }
    
    public function save(string $identifier) : bool
    {
        $values = $this->admissionsApplicationGateway->getApplicationByIdentifier($this->context['gibbonFormID'], $identifier);
        
        if (!empty($values)) {
            // Update the existing submission
            $existingData = json_decode($values['data'] ?? '', true) ?? [];
            $data = array_merge($existingData, $this->getData());

            $saved = $this->admissionsApplicationGateway->update($values['gibbonAdmissionsApplicationID'], [
                'data'               => json_encode($data),
                'result'             => json_encode($this->getResults()),
                'status'             => $this->getStatus(),
                'gibbonSchoolYearID' => $this->getOrNull('gibbonSchoolYearIDEntry'),
                'gibbonYearGroupID'  => $this->getOrNull('gibbonYearGroupIDEntry'),
                'gibbonFormGroupID'  => $this->getOrNull('gibbonFormGroupIDEntry'),
                'timestampModified'  => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Create a new submission
            $saved = $this->admissionsApplicationGateway->insert($this->context + [
                'identifier'         => $identifier,
                'data'               => json_encode($this->getData()),
                'result'             => json_encode($this->getResults()),
                'gibbonSchoolYearID' => $this->getOrNull('gibbonSchoolYearIDEntry'),
                'gibbonYearGroupID'  => $this->getOrNull('gibbonYearGroupIDEntry'),
                'gibbonFormGroupID'  => $this->getOrNull('gibbonFormGroupIDEntry'),
                'timestampCreated'   => date('Y-m-d H:i:s'),
            ]);
        }

        return !empty($saved);
    }

    public function load(string $identifier) : bool
    {
        $values = $this->admissionsApplicationGateway->getApplicationByIdentifier($this->context['gibbonFormID'], $identifier);

        $this->setStatus($values['status'] ?? 'Incomplete');
        $this->setData(json_decode($values['data'] ?? '', true) ?? []);
        $this->setResults(json_decode($values['result'] ?? '', true) ?? []);

        return !empty($values);
    }
}
