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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Module\FreeLearning\Domain\UnitClassGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_currentUnitByClass.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__m('Current Unit By Class'));

    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $sort = $_GET['sort'] ?? 'status';

    $form = Form::create('filter', $session->get('absoluteURL') . '/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder w-full');
    $form->setTitle(__m('Choose Class'));

    $form->addHiddenValue('q', '/modules/' . $session->get('module') . '/report_currentUnitByClass.php');

    $row = $form->addRow();
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelectClass('gibbonCourseClassID', $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))
            ->required()
            ->selected($gibbonCourseClassID)
            ->placeholder();

    $sortOptions = ['status' => __('Status'), 'unit' => __('Unit'), 'student' => __('Student')];
    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray($sortOptions)->selected($sort);

    $row = $form->addRow();
    $row->addSearchSubmit($session);

    echo $form->getOutput();

    if ($gibbonCourseClassID != '') {
        $courseGateway = $container->get(CourseGateway::class);
        $values = $courseGateway->getCourseClassByID($gibbonCourseClassID);

        if (!is_array($values)) {
            $page->addError(__('There are no records to display.'));
        } else {
            $unitClassGateway = $container->get(UnitClassGateway::class);
            $studentUnits = $unitClassGateway->selectUnitsByClass($gibbonCourseClassID, $sort)->toDataSet();

            $blocks = getBlocksArray($connection2);
            $collaborationKeys = [];

            $studentUnits->transform(function (&$row) use ($blocks) {
                if (!empty($row['timestampJoined'])) {
                    $row['timing'] = null;
                    if ($blocks != false) {
                        foreach ($blocks as $block) {
                            if ($block[0] == $row['freeLearningUnitID']) {
                                if (is_numeric($block[2])) {
                                    $row['timing'] += $block[2];
                                }
                            }
                        }
                    }
                }
            });

            $table = DataTable::create('reportData');
            $table->setTitle(__m('Report Data').' - '.$values['courseNameShort'].'.'.$values['nameShort']);

            $count = 1;
            $table->addColumn('count', '')
                ->notSortable()
                ->width('35px')
                ->format(function ($row) use (&$count) {
                    return '<span class="subdued">'.$count++.'</span>';
                });

            $table->addColumn('gibbonPersonID', __('Student'))
                ->format(function ($row) use ($session, $container) {

                    $output = '<a href="'.$session->get('absoluteURL')."/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$row['gibbonPersonID'].'">'.Format::name('', $row['preferredName'], $row['surname'], 'Student', true).'</a>';

                    //Check for custom field
                    $customField = $container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'customField');

                    $fields = json_decode($row['fields'], true);
                    if (!empty($fields[$customField])) {
                        $value = $fields[$customField];
                        if ($value != '') {
                            $output .= '<br/>'.Format::small($value);
                        }
                    }

                    if (!empty($values['grouping']) && $values['grouping'] != 'Individual') {
                        $output .= '<br/>'.Format::small($values['grouping']);
                    }

                    return $output;
                });

            $table->addColumn('collaborationKey', __m('Group'))
                ->format(function ($values) use (&$collaborationKeys) {
                    $output = '';
                    if (!empty($values['collaborationKey'])) {
                        // Get the index for the group, otherwise add it to the array
                        $group = array_search($values['collaborationKey'], $collaborationKeys);
                        if ($group === false) {
                            $collaborationKeys[] = $values['collaborationKey'];
                            $group = count($collaborationKeys);
                        } else {
                            $group++;
                        }
                        $output .= $group;
                    }

                    return $output;
                });

            $table->addColumn('unit', __('Unit'))
                ->description(__m('Status'))
                ->format(function ($row) use ($session) {
                    $output = '';
                    if ($row['enrolmentMethod'] == "schoolMentor" || $row['enrolmentMethod'] == "externalMentor") {
                        $output .= "<span class=\"float-right tag message border border-blue-300 ml-2\">".__m(ucfirst(preg_replace('/(?<!\ )[A-Z]/', ' $0', $row['enrolmentMethod'])))."</span>";
                    }
                    $output .= "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&tab=2&freeLearningUnitID='.$row['freeLearningUnitID']."&gibbonDepartmentID=&difficulty=&name='>".htmlPrep($row['unitName']).'</a>';
                    $output .= "<br/><span style='font-size: 85%; font-style: italic'>".__m($row['status'] ?? '').'</span>';
                    //TODO: CHANGE FROM INLINE HTML TO OO FORMATTING :(
                    return $output;
                });

            $table->addColumn('timestampJoined', __m('Date Started'))
                ->format(function ($row) {
                    $output = '';
                    if ($row['timestampJoined'] != '') {
                        $output .= Format::date(substr($row['timestampJoined'], 0, 10));
                    }
                    return $output;
                });

            $table->addColumn('daysSince', __m('Days Since Started'))
                ->format(function ($row) {
                    $output = '';
                    if ($row['timestampJoined'] != '') {
                        $output .= round((time() - strtotime($row['timestampJoined'])) / (60 * 60 * 24));
                    }
                    return $output;
                });

            $table->addColumn('length', __m('Length'))
                ->description(__('Minutes'))
                ->format(function ($row) {
                    $output = '';
                    if ($row['timestampJoined'] != '') {
                        if (is_null($row['timing'])) {
                            $output .= '<i>'.__('N/A').'</i>';
                        } else {
                            $output .= $row['timing'];
                        }
                    }
                    return $output;
                });

            $table->addColumn('timeSpent', __m('Time Spent'))
                ->description(__('Minutes By Day\'s End'))
                ->format(function ($row) use ($unitClassGateway, $gibbonCourseClassID) {
                    if (empty($row['timestampJoined'])) return '';

                    $output = '';
                    $spent = 0;
                    $resultLessons = $unitClassGateway->selectTiming($gibbonCourseClassID, $row['timestampJoined']);

                    if ($resultLessons->rowCount() < 1) {
                        $output .= $spent;
                    } else {
                        while ($rowLessons = $resultLessons->fetch()) {
                            $start_date = new DateTime($rowLessons['date'].' '.$rowLessons['timeStart']);
                            $since_start = $start_date->diff(new DateTime($rowLessons['date'].' '.$rowLessons['timeEnd']));
                            $spent += (60*$since_start->h) + $since_start->i;
                        }

                        if (is_null($row['timing'])) { //No length to compare to, so just spit out answer
                            $output .= $spent;
                        }
                        else if ($spent<=$row['timing']) { //OK for time, spit out in green
                            $output .= "<span style='font-weight: bold; color: #390;'>$spent</span>";
                        }
                        else { //Over time, spit out in orange
                            $output .= "<span style='font-weight: bold; color: #D65602;'>$spent</span>";
                        }

                    }


                    return $output;
                });

            echo $table->render($studentUnits);
        }
    }
}
?>
