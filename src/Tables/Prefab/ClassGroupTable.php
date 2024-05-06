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

namespace Gibbon\Tables\Prefab;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Forms\Input\Checkbox;
use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;

/**
 * ClassGroupTable
 *
 * @version v18
 * @since   v18
 */
class ClassGroupTable extends DataTable
{
    protected $db;
    protected $session;
    protected $enrolmentGateway;

    public function __construct(GridView $renderer, CourseEnrolmentGateway $enrolmentGateway, Connection $db, Session $session)
    {
        parent::__construct($renderer);

        $this->db = $db;
        $this->session = $session;
        $this->enrolmentGateway = $enrolmentGateway;
    }

    public function build($gibbonSchoolYearID, $gibbonCourseClassID)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);

        $canViewStaff = isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php');
        $canEditEnrolment = isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php');

        $canViewStudents = isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_brief')
            || ($highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes' || $highestAction == 'View Student Profile_fullEditAllNotes');
        $canViewConfidential = $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes'  || $highestAction == 'View Student Profile_fullEditAllNotes';

        $criteria = $this->enrolmentGateway
            ->newQueryCriteria()
            ->sortBy(['roleSortOrder', 'surname', 'preferredName'])
            ->filterBy('nonStudents', !$canViewStudents);

        $participants = $this->enrolmentGateway->queryCourseEnrolmentByClass($criteria, $gibbonSchoolYearID, $gibbonCourseClassID);
        $this->withData($participants);

        $this->setTitle(__('Participants'));

        $this->addMetaData('gridClass', 'rounded-sm bg-blue-100 border');
        $this->addMetaData('gridItemClass', 'w-1/2 sm:w-1/3 md:w-1/5 my-2 sm:my-4 text-center');

        if ($canEditEnrolment && count($participants) > 0) {
            $this->addHeaderAction('edit', __('Edit Enrolment'))
                ->setURL('/modules/Timetable Admin/courseEnrolment_manage_class_edit.php')
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('gibbonCourseID', $participants->getRow(0)['gibbonCourseID'] ?? '')
                ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                ->displayLabel()
                ->append('&nbsp;&nbsp;|&nbsp;&nbsp;');
        }

        if ($canViewConfidential) {
            $this->addHeaderAction('export', __('Export to Excel'))
                ->setURL('/modules/Departments/department_course_classExport.php')
                ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
                ->addParam('address', $_GET['q'])
                ->setIcon('download')
                ->directLink()
                ->displayLabel();
        }

        if ($canViewConfidential) {
            $checkbox = (new Checkbox('confidential'.$gibbonCourseClassID))
                ->description(__('Show Confidential Data'))
                ->checked(true)
                ->inline()
                ->wrap('<div class="mt-2 text-right text-xxs text-gray-700 italic">', '</div>');

            $this->addMetaData('gridHeader', $checkbox->getOutput());
            $this->addMetaData('gridFooter', $this->getCheckboxScript($gibbonCourseClassID));

            $this->addColumn('alerts')
                ->format(function ($person) use ($guid, $connection2, $gibbonCourseClassID) {
                    $divExtras = ' data-conf="confidential'.$gibbonCourseClassID.'"';
                    return getAlertBar($guid, $connection2, $person['gibbonPersonID'], $person['privacy'], $divExtras);
                });
        }

        $this->addColumn('image_240')
            ->setClass('relative')
            ->format(function ($person) use ($canViewStaff, $canViewStudents) {
                $photo = Format::userPhoto($person['image_240'], 'md', '');
                $icon = Format::userBirthdayIcon($person['dob'], $person['preferredName']);

                if ($person['role'] == 'Student') {
                    $url = Url::fromModuleRoute('Students', 'student_view_details')
                        ->withQueryParams(['gibbonPersonID' => $person['gibbonPersonID']]);
                    return $canViewStudents
                        ? Format::link($url, $photo).$icon
                        : $photo.$icon;
                } else {
                    $url = Url::fromModuleRoute('Staff', 'staff_view_details')
                        ->withQueryParams(['gibbonPersonID' => $person['gibbonPersonID']]);
                    return $canViewStaff
                        ? Format::link($url, $photo).$icon
                        : $photo.$icon;
                }
            });

        $this->addColumn('name')
            ->setClass('text-xs font-bold mt-1')
            ->format(function ($person) use ($canViewStaff, $canViewStudents) {
                if ($person['role'] == 'Student') {
                    $name = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Student', false, true);
                    $url = Url::fromModuleRoute('Students', 'student_view_details')
                        ->withQueryParams(['gibbonPersonID' => $person['gibbonPersonID']]);
                    $canViewProfile = $canViewStudents;
                } else {
                    $name = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', false, false);
                    $url = Url::fromModuleRoute('Staff', 'staff_view_details')
                        ->withQueryParams(['gibbonPersonID' => $person['gibbonPersonID']]);
                    $canViewProfile = $canViewStaff;
                }

                return $canViewProfile
                    ? Format::link($url, $name)
                    : $name;
            });

        $this->addColumn('role')
            ->setClass('text-xs text-gray-600 italic leading-snug')
            ->translatable();
    }

    private function getCheckboxScript($id)
    {
        return '
        <script type="text/javascript">
        $(function () {
            $("#confidential'.$id.'").click(function () {
                $("[data-conf=\'confidential'.$id.'\']").slideToggle(!$(this).is(":checked"));
            });
        });
        </script>';
    }
}
