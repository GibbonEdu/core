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

namespace Gibbon\Forms\Builder\Process;

use Gibbon\Domain\IndividualNeeds\INGateway;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\CreateINRecordView;

class CreateINRecord extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['sen'];

    protected $inGateway;
    protected $customFieldGateway;

    public function __construct(INGateway $inGateway, CustomFieldGateway $customFieldGateway)
    {
        $this->inGateway = $inGateway;
        $this->customFieldGateway = $customFieldGateway;
    }

    public function getViewClass() : string
    {
        return CreateINRecordView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('createINRecord') == 'Y';
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->hasAll(['gibbonPersonIDStudent', 'sen'])) {
            return;
        }
        // Create a new IN record
        $gibbonINID = $this->inGateway->insert([
            'gibbonPersonID' => $formData->get('gibbonPersonIDStudent'),
            'notes'          => $formData->get('senDetails', ''),
            'strategies'     => '',
            'targets'        => '',
            'fields'         => $this->getCustomFields($formData),
        ]);

        $formData->set('gibbonINID', $gibbonINID);
        $this->setResult($gibbonINID);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonINID')) return;

        $this->inGateway->delete($formData->get('gibbonINID'));
        
        $formData->set('gibbonINID', null);
    }

    /**
     * Transfer values from form data into json custom field data
     *
     * @param FormDataInterface $formData
     */
    protected function getCustomFields(FormDataInterface $formData)
    {
        $customFields = $this->customFieldGateway->selectCustomFields('Individual Needs', [])->fetchAll();
        $fields = [];

        foreach ($customFields as $field) {
            $id = 'custom'.$field['gibbonCustomFieldID'];
            if (!$formData->has($id)) continue;

            $fields[$field['gibbonCustomFieldID']] = $formData->get($id);
        }

        return json_encode($fields);
    }
}
