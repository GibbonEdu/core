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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Module\Planner\Tables\LessonTable;
use Gibbon\Domain\Students\StudentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $plannerEntryGateway = $container->get(PlannerEntryGateway::class);

        //Set variables
        $today = date('Y-m-d');
        $settingGateway = $container->get(SettingGateway::class);
        $homeworkNameSingular = $settingGateway->getSettingByScope('Planner', 'homeworkNameSingular');
        $homeworkNamePlural = $settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');

        $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
        //Proceed!
        //Get viewBy, date and class variables
        $viewBy = $_GET['viewBy'] ?? '';
        $search = $_GET['search'] ?? '';
        $subView = $_GET['subView'] ?? '';

        if ($viewBy != 'date' and $viewBy != 'class') {
            $viewBy = 'date';
        }

        $gibbonCourseClassID = null;
        $date = null;
        $dateStamp = null;

        if ($viewBy == 'date') {
            if (isset($_GET['date'])) {
                $date = $_GET['date'] ?? '';
            }
            if (isset($_GET['dateHuman'])) {
                $date = Format::dateConvert($_GET['dateHuman']);
            }
            if ($date == '') {
                $date = date('Y-m-d');
            }
            [$dateYear, $dateMonth, $dateDay] = explode('-', $date);
            $dateStamp = mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);
        } elseif ($viewBy == 'class') {
            $class = null;
            if (isset($_GET['class'])) {
                $class = $_GET['class'] ?? '';
            }
            $gibbonCourseClassID = null;
            if (isset($_GET['gibbonCourseClassID'])) {
                $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
            }
        }
        [$todayYear, $todayMonth, $todayDay] = explode('-', $today);
        $todayStamp = mktime(12, 0, 0, $todayMonth, $todayDay, $todayYear);

        if ($viewBy == 'date' && isSchoolOpen($guid, date('Y-m-d', $dateStamp), $connection2) == false) {
            $page->addWarning(__('School is closed on the specified day.'));
        }

        if ($viewBy == 'class' && empty($gibbonCourseClassID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        //My children's classes
        if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
            
            $page->breadcrumbs->add(__('My Children\'s Classes'));

            $studentGateway = $container->get(StudentGateway::class);
            $children = $studentGateway
                ->selectActiveStudentsByFamilyAdult($gibbonSchoolYearID, $session->get('gibbonPersonID'))
                ->fetchGroupedUnique();

            if (empty($children)) {
                echo $page->getBlankSlate();
            } elseif (count($children) == 1) {
                $gibbonPersonID = key($children);
            } else {
                $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');
                $form->setTitle(__('Choose'));
                $form->setClass('noIntBorder w-full');

                $form->addHiddenValue('address', $session->get('address'));
                $form->addHiddenValue('q', '/modules/'.$session->get('module').'/planner.php');
                $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID ?? '');
                $form->addHiddenValue('viewBy', !empty($gibbonCourseClassID) ? 'class' : 'date');

                $row = $form->addRow();
                $row->addLabel('search', __('Student'));
                $row->addSelect('search')
                    ->fromArray(Format::nameListArray($children, 'Student'))
                    ->selected($search)
                    ->placeholder();

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSearchSubmit($session);

                echo $form->getOutput();

                $gibbonPersonID = $search;
            }

            if (!empty($gibbonPersonID) && !empty($children[$gibbonPersonID])) {
                $student = $container->get(StudentGateway::class)->selectActiveStudentByPerson($gibbonSchoolYearID, $gibbonPersonID)->fetch();

                if (empty($student)) {
                    echo $page->getBlankSlate();
                } else {
                    $table = $container->get(LessonTable::class)->create($gibbonSchoolYearID, $gibbonCourseClassID, $gibbonPersonID, $date, $viewBy);
                    $table->setTitle(__('Lessons'));

                    echo $table->getOutput();
                }
            }
            
        }
        //My Classes
        elseif ($highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewOnly') {
            $gibbonPersonID = $session->get('gibbonPersonID');

            $page->return->addReturns([
                'success1' =>  __('Bump was successful. It is possible that some lessons have not been moved (if there was no space for them), but a reasonable effort has been made.'),
            ]);

            if ($viewBy == 'date') {
                $page->breadcrumbs->add(__('Planner for {classDesc}', [
                    'classDesc' => Format::date($date),
                ]));
            } elseif ($viewBy == 'class') {
                $planner = $plannerEntryGateway->getPlannerClassDetails($gibbonCourseClassID);
                if (empty($planner)) {
                    $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                    return;
                }

                $page->breadcrumbs->add(__('Planner for {classDesc}', [
                    'classDesc' => $planner['course'].'.'.$planner['class'],
                ]));
            }

            $viewBy = $subView == 'year' ? 'year' : $viewBy;
                    
            $table = $container->get(LessonTable::class)->create($gibbonSchoolYearID, $gibbonCourseClassID, $gibbonPersonID, $date, $viewBy);
            echo $table->getOutput(); 
        }
    }

    if (!empty($gibbonPersonID)) {
        //Print sidebar
        $session->set('sidebarExtra', sidebarExtra($guid, $connection2, $todayStamp, $gibbonPersonID, $dateStamp, $gibbonCourseClassID));
    }
}
