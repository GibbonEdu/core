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

use Gibbon\Data\UsernameGenerator;
use Gibbon\Domain\User\FamilyChildGateway;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\CreateFamilyView;
use Gibbon\Forms\Builder\Exception\FormProcessException;

class CreateFamily extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['homeAddress'];

    private $familyGateway;
    private $familyChildGateway;

    public function __construct(FamilyGateway $familyGateway, FamilyChildGateway $familyChildGateway)
    {
        $this->familyGateway = $familyGateway;
        $this->familyChildGateway = $familyChildGateway;
    }

    public function getViewClass() : string
    {
        return CreateFamilyView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('createFamily') == 'Y';
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonPersonIDStudent')) {
            throw new FormProcessException('Missing data to create family');
        }

        if (!$formData->has('gibbonFamilyID')) {
            $this->generateFamilyName($formData);
            $this->generateNameAddress($formData);

            // Create the family
            $gibbonFamilyID = $this->familyGateway->insert([
                'name'                  => $formData->get('familyName', ''),
                'nameAddress'           => $formData->get('nameAddress', ''),
                'homeAddress'           => $formData->get('homeAddress', ''),
                'homeAddressDistrict'   => $formData->get('homeAddressDistrict', ''),
                'homeAddressCountry'    => $formData->get('homeAddressCountry', ''),
                'languageHomePrimary'   => $formData->get('languageHomePrimary', ''),
                'languageHomeSecondary' => $formData->get('languageHomeSecondary'),
                'status'                => $formData->get('familyStatus', 'Other'),
            ]);

            if (empty($gibbonFamilyID)) {
                throw new FormProcessException('Failed to insert family into the database');
            }

            $formData->set('gibbonFamilyID', $gibbonFamilyID);
            $formData->set('familyCreated', true);
        } else {
            $gibbonFamilyID = $formData->get('gibbonFamilyID');
            $family = $this->familyGateway->getByID($gibbonFamilyID);

            $formData->set('familyName', $family['name'] ?? '');
            $formData->set('nameAddress', $family['nameAddress'] ?? '');
            $formData->set('homeAddress', $family['homeAddress'] ?? '');
        }

        // Add the student to the family as a child
        $gibbonFamilyChildID = $this->familyChildGateway->insert([
            'gibbonFamilyID' => $gibbonFamilyID,
            'gibbonPersonID' => $formData->get('gibbonPersonIDStudent'),
        ]);

        $formData->set('gibbonFamilyChildID', $gibbonFamilyChildID);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonFamilyID')) return;

        $this->familyChildGateway->deleteWhere(['gibbonFamilyID' => $formData->get('gibbonFamilyID')]);
        $formData->set('gibbonFamilyChildID', null);

        if ($formData->has('familyCreated')) {
            $this->familyGateway->delete($formData->get('gibbonFamilyID'));
            $formData->set('gibbonFamilyID', null);
        }
    }

    protected function generateFamilyName(FormDataInterface $formData)
    {
        if ($formData->has('familyName')) return;

        if ($formData->hasAll(['parent1preferredName', 'parent1surname'])) {
            $familyName = $formData->get('parent1preferredName').' '.$formData->get('parent1surname');

            if ($formData->hasAll(['parent2preferredName', 'parent2surname'])) {
                $familyName .= ' & '.$formData->get('parent2preferredName').' '.$formData->get('parent2surname');
            }
        } else {
            $familyName = $formData->get('preferredName').' '.$formData->get('surname');
        }
        
        $formData->set('familyName', trim($familyName));
    }

    protected function generateNameAddress(FormDataInterface $formData)
    {
        if ($formData->has('nameAddress')) return;

        $nameAddress = $formData->get('title').' '.$formData->get('preferredName').' '.$formData->get('surname');

        if ($formData->hasAll(['parent1preferredName', 'parent1surname', 'parent2preferredName', 'parent2surname'])) {
            if ($formData->get('parent1surname') == $formData->get('parent2surname')) {
                $nameAddress = $formData->get('parent1title').' & '.$formData->get('parent2title').' '.$formData->get('parent1surname');
            } else {
                $nameAddress = $formData->get('parent1title').' '.$formData->get('parent1surname').' & '.$formData->get('parent2title').' '.$formData->get('parent1surname');
            }
        } else if ($formData->hasAll(['parent1preferredName', 'parent1surname'])) {
            $nameAddress = $formData->get('parent1title').' '.$formData->get('parent1surname');
        }

        $formData->set('nameAddress', trim($nameAddress));
    }

    public function verify(FormBuilderInterface $builder, FormDataInterface $formData = null)
    {
        if ($formData && $formData->has('gibbonFamilyID')) return;

        parent::verify($builder, $formData);
    }
}
