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

use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Exception\FormProcessException;
use Gibbon\Forms\Builder\Process\ViewableProcess;
use Gibbon\Forms\Builder\View\ApplicationCheckView;

class ApplicationCheck extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = [];

    private $schoolYearGateway;
    private $yearGroupGateway;
    private $formGroupGateway;

    public function __construct(SchoolYearGateway $schoolYearGateway, YearGroupGateway $yearGroupGateway, FormGroupGateway $formGroupGateway)
    {
        $this->schoolYearGateway = $schoolYearGateway;
        $this->yearGroupGateway = $yearGroupGateway;
        $this->formGroupGateway = $formGroupGateway;
    }

    public function getViewClass() : string
    {
        return ApplicationCheckView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return true;
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        // Check and set enrolment details, to be used by other processes
        if ($builder->getConfig('enrolStudent') == 'Y') {
            $schoolYear = $this->schoolYearGateway->getByID($formData->get('gibbonSchoolYearIDEntry'), ['name', 'status']);
            if ($formData->has('gibbonSchoolYearIDEntry') && empty($schoolYear)) {
                throw new FormProcessException('Missing or invalid school year');
            }

            $yearGroup = $this->yearGroupGateway->getByID($formData->get('gibbonYearGroupIDEntry'), ['name']);
            if ($formData->has('gibbonYearGroupIDEntry') && empty($yearGroup)) {
                throw new FormProcessException('Missing or invalid year group');
            }

            $formGroup = $this->formGroupGateway->getByID($formData->get('gibbonFormGroupIDEntry'), ['name']);
            if ($formData->has('gibbonFormGroupIDEntry') && empty($formGroup)) {
                throw new FormProcessException('Missing or invalid form group');
            }

            $formData->setResult('schoolYearName', $schoolYear['name'] ?? '');
            $formData->setResult('schoolYearStatus', $schoolYear['status'] ?? '');
            $formData->setResult('yearGroupName', $yearGroup['name'] ?? '');
            $formData->setResult('formGroupName', $formGroup['name'] ?? '');
        }

        // Check for post data and record it
        $formData->setResult('informStudent', $_POST['informStudent'] ?? 'N');
        $formData->setResult('informParents', $_POST['informParents'] ?? 'N');
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if ($builder->getConfig('enrolStudent') == 'Y') {
            $formData->setResult('schoolYearName', '');
            $formData->setResult('schoolYearStatus', '');
            $formData->setResult('yearGroupName', '');
            $formData->setResult('formGroupName', '');
        }
        
        $formData->setResult('informStudent', '');
        $formData->setResult('informParents', '');
    }
}
