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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\EnrolStudentView;
use Gibbon\Forms\Builder\Exception\FormProcessException;

class EnrolStudent extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['gibbonSchoolYearIDEntry', 'gibbonYearGroupIDEntry'];

    private $settingGateway;
    private $studentGateway;
    private $courseEnrolmentGateway;

    public function __construct(SettingGateway $settingGateway, StudentGateway $studentGateway, CourseEnrolmentGateway $courseEnrolmentGateway)
    {
        $this->settingGateway = $settingGateway;
        $this->studentGateway = $studentGateway;
        $this->courseEnrolmentGateway = $courseEnrolmentGateway;
    }

    public function getViewClass() : string
    {
        return EnrolStudentView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('enrolStudent') == 'Y';
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->hasAll(['gibbonPersonIDStudent', 'gibbonFormGroupIDEntry'])) {
            return;
        }

        // Enrol the student with the following data
        $data = [
            'gibbonPersonID'     => $formData->get('gibbonPersonIDStudent'),
            'gibbonSchoolYearID' => $formData->get('gibbonSchoolYearIDEntry'),
            'gibbonYearGroupID'  => $formData->get('gibbonYearGroupIDEntry'),
            'gibbonFormGroupID'  => $formData->get('gibbonFormGroupIDEntry'),
        ];

        $gibbonStudentEnrolmentID = $this->studentGateway->insert($data);

        if (empty($gibbonStudentEnrolmentID)) {
            return;
        }

        $formData->setResult('gibbonStudentEnrolmentID', $gibbonStudentEnrolmentID);

        // Attempt to auto-enrol this student in any synced courses
        if ($this->settingGateway->getSettingByScope('Timetable Admin', 'autoEnrolCourses') == 'Y') {
            $enrolmentDate = $this->courseEnrolmentGateway->getEnrolmentDateBySchoolYear($formData->get('gibbonSchoolYearIDEntry'));

            $inserted = $this->courseEnrolmentGateway->insertAutomaticCourseEnrolments($formData->get('gibbonFormGroupIDEntry'), $formData->get('gibbonPersonIDStudent'), $enrolmentDate);

            $formData->setResult('autoEnrolCoursesResult', $inserted);
        }
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonStudentEnrolmentID')) return;

        $this->courseEnrolmentGateway->deleteAutomaticCourseEnrolments($formData->get('gibbonFormGroupIDEntry'), $formData->get('gibbonStudentEnrolmentID'));
        $formData->setResult('autoEnrolCoursesResult', false);

        $this->studentGateway->delete($formData->get('gibbonStudentEnrolmentID'));
        $formData->set('gibbonStudentEnrolmentID', null);
    }

}
