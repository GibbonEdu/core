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

use Gibbon\Http\Url;
use Gibbon\View\View;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;
use Gibbon\Module\FreeLearning\Domain\UnitOutcomeGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$publicUnits = $settingGateway->getSettingByScope('Free Learning', 'publicUnits');
$canManage = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php');
$browseAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php', 'Browse Units_all');

$unitGateway = $container->get(UnitGateway::class);
$unitStudentGateway = $container->get(UnitStudentGateway::class);

if (!(isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php') == true or ($publicUnits == 'Y' and !$session->exists('username')))) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    if ($publicUnits == 'Y' and !$session->exists('username')) {
        $highestAction = 'Browse Units_all';
        $highestActionManage = null;
        $roleCategory = null ;
    } else {
        $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
        $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);
    }
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Get params
        $freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';

        $showInactive = $canManage && isset($_GET['showInactive'])
            ? $_GET['showInactive']
            : 'N';
        $gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $name = $_GET['name'] ?? '';

        $view = $_GET['view'] ?? 'list';
        if ($view != 'grid' and $view != 'map') {
            $view = 'list';
        }

        $gibbonPersonID = ($canManage)
            ? ($_GET['gibbonPersonID'] ?? $session->get('gibbonPersonID') ?? null)
            : $session->get('gibbonPersonID') ?? null;

        $urlParams = compact('showInactive', 'gibbonDepartmentID', 'difficulty', 'name', 'view', 'gibbonPersonID', 'freeLearningUnitID');

        //Breadcrumbs
        if ($roleCategory == null) {
            $page->breadcrumbs
                ->add(__m('Browse Units'), '/modules/Free Learning/units_browse.php', $urlParams)
                ->add(__m('Unit Details'));
        } else {
            $page->breadcrumbs
                ->add(__m('Browse Units'), 'units_browse.php', $urlParams)
                ->add(__m('Unit Details'));
        }

        $returns = ['error6' => __('An error occured with your submission, most likely because a submitted file was too large.')];
		$page->return->addReturns($returns);

        if ($freeLearningUnitID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                $unitList = getUnitList($connection2, $guid, $gibbonPersonID, $roleCategory, $highestAction, null, null, null, $showInactive, $publicUnits, $freeLearningUnitID, null);
                $data = $unitList[0];
                $sql = $unitList[1];
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $values = $result->fetch();

                $prerequisitesMet = false;
                if ($highestAction == 'Browse Units_all') {
                    $prerequisitesMet = true;
                } elseif ($highestAction == 'Browse Units_prerequisites') {
                    if ($values['freeLearningUnitIDPrerequisiteList'] == null or $values['freeLearningUnitIDPrerequisiteList'] == '') {
                        $prerequisitesMet = true;
                    } else {
                        $prerequisitesActive = prerequisitesRemoveInactive($connection2, $values['freeLearningUnitIDPrerequisiteList']);
                        $prerequisitesMet = prerequisitesMet($connection2, $gibbonPersonID, $prerequisitesActive);
                    }
                }

                //Get enrolment Details
                try {
                    $dataEnrol = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $gibbonPersonID);
                    $sqlEnrol = 'SELECT freeLearningUnitStudent.*, gibbonPerson.surname, gibbonPerson.email, gibbonPerson.preferredName
                        FROM freeLearningUnitStudent
                        LEFT JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDSchoolMentor=gibbonPerson.gibbonPersonID)
                        WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID
                            AND gibbonPersonIDStudent=:gibbonPersonID';
                    $resultEnrol = $connection2->prepare($sqlEnrol);
                    $resultEnrol->execute($dataEnrol);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                $enrolled = ($resultEnrol->rowCount() == 1) ? true : false ;

                // Add prerequisitesMet if student has exemption for this unit
                if ($enrolled) {
                    $rowEnrol = $resultEnrol->fetch() ;
                    if ($rowEnrol['status'] == 'Exempt') {
                        $prerequisitesMet = true;
                    }
                }

                // UNIT DETAILS TABLE
                $table = DataTable::createDetails('unitDetails');

                // HEADER ACTIONS
                $back = false ;
                if ($gibbonDepartmentID != '' or $difficulty != '' or $name != '') {
                    $back = true ;
                    $table->addHeaderAction('back', __('Back to Search Results'))
                        ->setURL('/modules/Free Learning/units_browse.php')
                        ->setIcon('search')
                        ->displayLabel()
                        ->addParams($urlParams);
                }
                if ($canManage) {
                    $table->addHeaderAction('edit', __('Edit'))
                        ->setURL('/modules/Free Learning/units_manage_edit.php')
                        ->setIcon('config')
                        ->displayLabel()
                        ->addParams($urlParams)
                        ->prepend(($back) ? ' | ' : '');
                }
                if ($browseAll) {
                    $table->addHeaderAction('download', __('Download'))
                        ->setExternalURL($session->get('absoluteURL')."/modules/Free Learning/units_browse_details_export.php?freeLearningUnitID=$freeLearningUnitID")
                        ->setIcon('download')
                        ->displayLabel()
                        ->prepend(($canManage) ? ' | ' : '');

                    $table->addHeaderAction('export', __('Export'))
                        ->setExternalURL($session->get('absoluteURL')."/modules/Free Learning/units_manageProcessBulk.php?action=Export&freeLearningUnitID=$freeLearningUnitID&name=".$values['name'])
                        ->setIcon('delivery2')
                        ->displayLabel();
                }

                $table->addColumn('name', '')->addClass('text-lg font-bold');
                $table->addColumn('time', __m('Time'))
                    ->format(function ($values) use ($connection2, $freeLearningUnitID) {
                        $output = '';
                        $timing = null;
                        $blocks = getBlocksArray($connection2, $freeLearningUnitID);
                        if ($blocks != false) {
                            foreach ($blocks as $block) {
                                if ($block[0] == $values['freeLearningUnitID']) {
                                    if (is_numeric($block[2])) {
                                        $timing += $block[2];
                                    }
                                }
                            }
                        }
                        if (is_null($timing)) {
                            $output = __('N/A');
                        } else {
                            $minutes = intval($timing);
                            $relativeTime = __n('{count} min', '{count} mins', $minutes);
                            if ($minutes > 60) {
                                $hours = round($minutes / 60, 1);
                                $relativeTime = Format::tooltip(__n('{count} hr', '{count} '.__m('hrs'), ceil($minutes / 60), ['count' => $hours]), $relativeTime);
                            }

                            $output = !empty($timing) ? $relativeTime : Format::small(__('N/A'));
                        }

                        return $output;
                    });
                $table->addColumn('logo', '')
                    ->addClass('row-span-3 text-right')
                    ->format(function ($values) use ($session) {
                        if ($values['logo'] == null) {
                            return "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_125.jpg'/><br/>";
                        } else {
                            return "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='".$values['logo']."'/><br/>";
                        }
                    });
                $table->addColumn('difficulty', __m('Difficulty'));
                $table->addColumn('prerequisites', __m('Prerequisites'))
                    ->format(function ($values) use ($connection2) {
                        $output = '';
                        $prerequisitesActive = prerequisitesRemoveInactive($connection2, $values['freeLearningUnitIDPrerequisiteList']);
                        if ($prerequisitesActive != false) {
                            $prerequisites = explode(',', $prerequisitesActive);
                            $units = getUnitsArray($connection2);
                            foreach ($prerequisites as $prerequisite) {
                                $output .= $units[$prerequisite][0].'<br/>';
                            }
                        } else {
                            $output = __m('None');
                        }

                        return $output;
                    });
                $table->addColumn('departments', __('Departments'))
                    ->format(function ($values) use ($connection2, $guid) {
                        $output = '';
                        $learningAreas = getLearningAreas($connection2, $guid);
                        if ($learningAreas == '') {
                            $output = __m('No Learning Areas available.');
                        } else {
                            for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                                if (is_numeric(strpos($values['gibbonDepartmentIDList'] ?? '', $learningAreas[$i]))) {
                                    $output .= __($learningAreas[($i + 1)]).'<br/>';
                                }
                            }
                        }
                        return $output;
                    });
                    $table->addColumn('authors', __m('Authors'))
                        ->format(function ($values) use ($connection2, $freeLearningUnitID) {
                            $output = '';
                            $authors = getAuthorsArray($connection2, $freeLearningUnitID);
                            if (empty($authors)) return '';
                            
                            foreach ($authors as $author) {
                                if ($author[3] == '') {
                                    $output .= $author[1].'<br/>';
                                } else {
                                    $output .= "<a target='_blank' href='".$author[3]."'>".$author[1].'</a><br/>';
                                }
                            }
                            return $output;
                        });

                    $table->addColumn('groupings', __m('Groupings'))
                        ->format(function ($values) use ($connection2, $freeLearningUnitID) {
                            $output = '';
                            $authors = getAuthorsArray($connection2, $freeLearningUnitID);
                            if ($values['grouping'] != '') {
                                $groupings = explode(',', $values['grouping']);
                                foreach ($groupings as $grouping) {
                                    $output .= __m(ucwords($grouping)).'<br/>';
                                }
                            }
                            return $output;
                        });

                    $table->addColumn('gibbonYearGroupIDMinimum', __m('Minimum Year Group'))
                        ->format(function ($values) use ($guid, $connection2) {
                            return getYearGroupsFromIDList($guid, $connection2, $values["gibbonYearGroupIDMinimum"] ?? '');
                        });

                echo $table->render([$values]);


                $defaultTab = 2;
                if ($canManage || (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_approval.php') && $roleCategory == 'Staff')) {
                    $defaultTab = 3;
                }
                if (isset($_GET['tab'])) {
                    $defaultTab = $_GET['tab'];
                }

                $showContentOnEnrol = $settingGateway->getSettingByScope('Free Learning', 'showContentOnEnrol');

                echo "<div id='tabs' style='margin: 20px 0'>";
                //Tab links
                echo '<ul>';
                echo "<li><a href='#tabs0'>".__m('Unit Overview').'</a></li>';
                echo "<li><a href='#tabs1'>".__m('Enrol').'</a></li>';
                if ($prerequisitesMet) {
                    if ($canManage || (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_approval.php') && $roleCategory == 'Staff')) {
                        echo "<li><a href='#tabs2'>".__m('Manage Enrolment').'</a></li>';
                    }
                    echo "<li><a href='#tabs3'>".__m('Content').'</a></li>';
                    if ($canManage OR $showContentOnEnrol == "N" OR $enrolled) {
                        echo "<li><a href='#tabs4'>".__m('Resources').'</a></li>';
                        $disableOutcomes = $settingGateway->getSettingByScope('Free Learning', 'disableOutcomes');
                        if ($disableOutcomes != 'Y') {
                            echo "<li><a href='#tabs5'>".__m('Outcomes').'</a></li>';
                        }
                        $disableExemplarWork = $settingGateway->getSettingByScope('Free Learning', 'disableExemplarWork');
                        if ($disableExemplarWork != 'Y') {
                            echo "<li><a href='#tabs6'>".__m('Exemplar Work').'</a></li>';
                        }
                    }
                }
                echo '</ul>';

                //Tabs
                echo "<div id='tabs0'>";
                echo '<h3>';
                echo __m('Blurb');
                echo '</h3>';
                echo '<p>';
                echo $values['blurb'];
                echo '</p>';
                if ($values['license'] != '') {
                    echo '<h4>';
                    echo __m('License');
                    echo '</h4>';
                    echo '<p>';
                    echo __m('This work is shared under the following license:').' '.$values['license'];
                    echo '</p>';
                }
                if ($values['outline'] != '') {
                    echo '<h3>';
                    echo __m('Outline');
                    echo '</h3>';
                    echo '<p>';
                    echo $values['outline'];
                    echo '</p>';
                }
                echo '</div>';

                if (!$prerequisitesMet) {
                    echo "<div id='tabs1'>";
                    echo Format::alert(__m('You do not have access to this unit, as you have not yet met the prerequisites for it.'), 'warning');
                    echo '</div>';
                } else {

                    echo "<div id='tabs1'>";
                        //Enrolment screen spun into separate file for ease of coding
                        include './modules/Free Learning/units_browse_details_enrol.php';
                    echo '</div>';

                    if ($canManage || (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_approval.php') && $roleCategory == 'Staff')) {
                        echo "<div id='tabs2'>";
                            //Check to see if we have access to manage all enrolments, or only those belonging to ourselves
                            $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/enrolment_manage.php', 'Manage Enrolment_all');
                            $enrolmentType = '';
                            if ($manageAll == true) {
                                $enrolmentType = 'staffEdit';
                            } else {
                                //Check to see if we can set enrolmentType to "staffEdit" if user has rights in relevant department(s)
                                $learningAreas = getLearningAreas($connection2, $guid, true);
                                if ($learningAreas != '') {
                                    for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                                        if (is_numeric(strpos($values['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                                            $enrolmentType = 'staffEdit';
                                        }
                                    }
                                }
                            }

                            echo '<p>';
                                if ($manageAll == true) {
                                    echo __m('Below you can view the students currently enrolled in this unit, including those who are working on it, those who are awaiting approval and those who have completed it.');
                                }
                                else {
                                    echo __m('Below you can view those students currently enrolled in this unit from your classes or that you mentor. This includes those who are working on it, those who are awaiting approval and those who have completed it.');
                                }
                            echo '</p>';

                            // Get list of my classes before we start looping, for efficiency's sake
                            $myClasses = $unitGateway->selectRelevantClassesByTeacher($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))->fetchAll(PDO::FETCH_COLUMN, 0);

                            $criteria = $unitStudentGateway->newQueryCriteria()
                                ->sortBy(['statusSort', 'collaborationKey', 'surname', 'preferredName'])
                                ->fromPOST()
                                ->pageSize(50);

                            $students = $unitStudentGateway->queryCurrentStudentsByUnit($criteria, $session->get('gibbonSchoolYearID'), $values['freeLearningUnitID'], $session->get('gibbonPersonID'), $manageAll);
                            $canViewStudents = isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php');
                            $customField = $settingGateway->getSettingByScope('Free Learning', 'customField');

                            $collaborationKeys = [];

                            //Legend
                            $templateView = new View($container->get('twig'));
                            echo $templateView->fetchFromTemplate('unitLegend.twig.html');

                            // DATA TABLE
                            $table = DataTable::createPaginated('manageEnrolment', $criteria);

                            if ($enrolmentType == 'staffEdit' || isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php', 'Manage Units_learningAreas')) {
                                $table->addHeaderAction('addMultiple', __('Add Multiple'))
                                    ->setURL('/modules/Free Learning/units_browse_details_enrolMultiple.php')
                                    ->addParam('freeLearningUnitID', $values['freeLearningUnitID'])
                                    ->addParam('gibbonDepartmentID', $gibbonDepartmentID)
                                    ->addParam('difficulty', $difficulty)
                                    ->addParam('name', $name)
                                    ->addParam('showInactive', $showInactive)
                                    ->addParam('gibbonPersonID', $gibbonPersonID)
                                    ->addParam('view', $view)
                                    ->displayLabel();
                            }

                            $table->modifyRows(function ($student, $row) {
                                if ($student['status'] == 'Current - Pending') $row->addClass('currentPending');
                                if ($student['status'] == 'Current') $row->addClass('currentUnit');
                                if ($student['status'] == 'Evidence Not Yet Approved') $row->addClass('warning');
                                if ($student['status'] == 'Complete - Pending') $row->addClass('pending');
                                if ($student['status'] == 'Complete - Approved') $row->addClass('success');
                                if ($student['status'] == 'Exempt') $row->addClass('exempt');
                                return $row;
                            });

                            $unitStudentGateway = $container->get(UnitStudentGateway::class);
                            $table->addExpandableColumn('commentStudent')
                                ->format(function ($student) use (&$page, &$unitStudentGateway) {
                                    if ($student['status'] == 'Current' || $student['status'] == 'Current - Pending') return;

                                    $logs = $unitStudentGateway->selectUnitStudentDiscussion($student['freeLearningUnitStudentID'])->fetchAll();

                                    $logs = array_map(function ($item) {
                                        $item['comment'] = Format::hyperlinkAll($item['comment']);
                                        return $item;
                                    }, $logs);

                                    return $page->fetchFromTemplate('ui/discussion.twig.html', [
                                        'discussion' => $logs
                                    ]);
                                });

                            $table->addColumn('student', __('Student'))
                                ->sortable(['surname', 'preferredName'])
                                ->width('35%')
                                ->format(function ($student) use ($canViewStudents, $customField) {
                                    $output = '';
                                    $url = './index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'];
                                    $name = Format::name('', $student['preferredName'], $student['surname'], 'Student', true, true);

                                    $output = $canViewStudents
                                        ? Format::link($url, $name)
                                        : $name;

                                    if (!$canViewStudents) {
                                        $output .= '<br/>'.Format::link('mailto:'.$student['email'], $student['email']);
                                    }
                                    $fields = json_decode($student['fields'], true);
                                    if (!empty($fields[$customField])) {
                                        $output .= '<br/>'.Format::small($fields[$customField]);
                                    }
                                    return $output;
                                });

                            $table->addColumn('status', __('Status'))
                                ->description(__m('Enrolment Method'))
                                ->sortable('statusSort')
                                ->width('25%')
                                ->format(function ($student) {
                                    $enrolmentMethod = ucfirst(preg_replace('/(\w+)([A-Z])/U', '\\1 \\2', $student['enrolmentMethod']));
                                    return $student['status'] . '<br/>' . Format::small($enrolmentMethod);
                                });

                            $table->addColumn('submissions', __m('Submissions'))
                                ->notSortable()
                                ->description(__m('Most Recent'))
                                ->format(function ($student) {
                                    return $student['submissions'] . '<br/>' . Format::small(Format::relativeTime($student['timestampCompletePending']));
                                });

                            $table->addColumn('classMentor', __m('Class/Mentor'))
                                ->description(__m('Grouping'))
                                ->sortable(['course', 'class'])
                                ->width('20%')
                                ->format(function ($student) use (&$collaborationKeys) {
                                    $return = '';
                                    if ($student['enrolmentMethod'] == 'class') {
                                        if (!empty($student['course']) && !empty($student['class'])) {
                                            $return .= Format::courseClassName($student['course'], $student['class']);
                                        } else {
                                            $return .= Format::small(__('N/A'));
                                        }
                                    } else if ($student['enrolmentMethod'] == 'schoolMentor') {
                                        $return .= Format::name('', $student['mentorpreferredName'], $student['mentorsurname'], 'Student', false);
                                    } else if ($student['enrolmentMethod'] == 'externalMentor') {
                                        $return .= $student['nameExternalMentor'];
                                    }

                                    $grouping = $student['grouping'];
                                    if ($student['collaborationKey'] != '') {
                                        // Get the index for the group, otherwise add it to the array
                                        $group = array_search($student['collaborationKey'], $collaborationKeys);
                                        if ($group === false) {
                                            $collaborationKeys[] = $student['collaborationKey'];
                                            $group = count($collaborationKeys);
                                        } else {
                                            $group++;
                                        }
                                        $grouping .= " (".__m("Group")." ".$group.")";
                                    }
                                    $return .= '<br/>' . Format::small($grouping);

                                    return $return;
                                });

                            $table->addColumn('view', __('View'))
                                ->notSortable()
                                ->width('10%')
                                ->format(function ($student) {
                                    if (empty($student['evidenceLocation'])) return;

                                    $url = $student['evidenceType'] == 'Link'
                                        ? $student['evidenceLocation']
                                        : './'.$student['evidenceLocation'];

                                    return Format::link($url, __('View'), ['target' => '_blank']);
                                });

                            // ACTIONS
                            $table->addActionColumn()
                                ->addParam('freeLearningUnitStudentID')
                                ->addParam('freeLearningUnitID')
                                ->addParam('gibbonDepartmentID', $gibbonDepartmentID)
                                ->addParam('difficulty', $difficulty)
                                ->addParam('name', $name)
                                ->addParam('showInactive', $showInactive)
                                ->addParam('gibbonPersonID', $gibbonPersonID)
                                ->addParam('view', $view)
                                ->addParam('sidebar', 'true')
                                ->format(function ($student, $actions) use ($manageAll, $enrolmentType, $myClasses, $session) {
                                    // Check to see if we can edit this class's enrolment (e.g. we have $manageAll or this is one of our classes or we are the mentor)
                                    $editEnrolment = $manageAll ? true : false;
                                    if ($student['enrolmentMethod'] == 'class') {
                                        // Is teacher of this class?
                                        if (in_array($student['gibbonCourseClassID'], $myClasses)) {
                                            $editEnrolment = true;
                                        }
                                    } elseif ($student['enrolmentMethod'] == 'schoolMentor' && $student['gibbonPersonIDSchoolMentor'] == $session->get('gibbonPersonID')) {
                                        // Is mentor of this student?
                                        $editEnrolment = true;
                                    }

                                    if ($enrolmentType == 'staffEdit' || $editEnrolment) {
                                        if ($editEnrolment && ($student['status'] == 'Current' || $student['status'] == 'Complete - Pending' or $student['status'] == 'Complete - Approved' or $student['status'] == 'Evidence Not Yet Approved')) {
                                            $actions->addAction('edit', __('Edit'))
                                                ->setURL('/modules/Free Learning/units_browse_details_approval.php');
                                        }
                                        if ($editEnrolment) {
                                            $actions->addAction('delete', __('Delete'))
                                                ->setURL('/modules/Free Learning/units_browse_details_delete.php');
                                        }
                                    }

                                    if ($editEnrolment && $student['status'] == 'Current - Pending' && $student['enrolmentMethod'] == 'schoolMentor') {
                                        $actions->addAction('approve', __m('Approve'))
                                                ->setIcon('iconTick')
                                                ->addParam('confirmationKey', $student['confirmationKey'])
                                                ->addParam('response', 'Y')
                                                ->setURL('/modules/Free Learning/units_mentorProcess.php')
                                                ->directLink();

                                        $actions->addAction('reject', __m('Reject'))
                                                ->setIcon('iconCross')
                                                ->addParam('confirmationKey', $student['confirmationKey'])
                                                ->addParam('response', 'N')
                                                ->setURL('/modules/Free Learning/units_mentorProcess.php')
                                                ->directLink();
                                    }
                                });

                            echo $table->render($students);

                        echo "</div>";
                    }
                    echo '<div id="tabs3" style="border-width: 1px 0px 0px 0px !important; background-color: transparent !important; padding-left: 0; padding-right: 0; overflow: initial;">';
                        if (!$canManage AND $showContentOnEnrol == "Y" AND !$enrolled) {
                            echo Format::alert(__m("You cannot see this unit's content until you have enrolled."), 'warning');
                        } else {
                            $dataBlocks = ['freeLearningUnitID' => $freeLearningUnitID];
                            $sqlBlocks = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY sequenceNumber';

                            $blocks = $pdo->select($sqlBlocks, $dataBlocks)->fetchAll();

                            if (empty($blocks)) {
                                echo Format::alert(__('There are no records to display.'));
                            } else {
                                $templateView = $container->get(View::class);
                                $resourceContents = '';

                                if ($settingGateway->getSettingByScope('Free Learning', 'collapsedSmartBlocks') == "Y") {
                                    $template = "unitBlockCollapsed.twig.html";

                                    echo "<div class='linkTop mt-2 mb-4'>";
                                        echo "<a id='showAll' >".__m('Expand All')."<img class='ml-1' src='".$session->get('absoluteURL')."/themes/".$session->get('gibbonThemeName')."/img/plus.png'/></a>";
                                        echo "<a id='hideAll' style='display: none'>".__m('Collapse All')."<img class='ml-1' src='".$session->get('absoluteURL')."/themes/".$session->get('gibbonThemeName')."/img/minus.png'/></a>";
                                    echo "</div>";

                                    echo "<script type='text/javascript'>";
                                        echo "$(document).ready(function(){";
                                            echo "$('#showAll').click(function(){";
                                                echo "$('#showAll').css('display','none');";
                                                echo "$('#hideAll').css('display','inline');";
                                                echo "$('.content').css('display','block');";
                                                echo "$('.show').css('display','none');";
                                                echo "$('.hide').css('display','inline');";
                                            echo "});";
                                            echo "$('#hideAll').click(function(){";
                                                echo "$('#showAll').css('display','inline');";
                                                echo "$('#hideAll').css('display','none');";
                                                echo "$('.content').css('display','none');";
                                                echo "$('.show').css('display','inline');";
                                                echo "$('.hide').css('display','none');";
                                            echo "});";
                                        echo "});";
                                    echo "</script>";

                                } else {
                                    $template = "unitBlock.twig.html";
                                }

                                $blockCount = 0;
                                foreach ($blocks as $block) {
                                    echo $templateView->fetchFromTemplate($template, $block + [
                                        'roleCategory' => $roleCategory,
                                        'gibbonPersonID' => $session->get('username') ?? '',
                                        'blockCount' => $blockCount
                                    ]);
                                    $resourceContents .= $block['contents'];
                                    $blockCount++;
                                }

                                // Enable p5js widgets in smart blocks
                                if (stripos($resourceContents, '<script type="text/p5"') !== false) {
                                    echo '<script src="//toolness.github.io/p5.js-widget/p5-widget.js"></script>';
                                }
                            }
                        }

                    echo '</div>';
                    if ($canManage OR $showContentOnEnrol == "N" OR $enrolled) {
                        echo "<div id='tabs4'>";
                        //Resources
                        $noReosurces = true;

                        //Links
                        $links = '';
                        $linksArray = array();
                        $linksCount = 0;
                        if (!empty($resourceContents)) {
                            $dom = new DOMDocument();
                            @$dom->loadHTML($resourceContents);
                            foreach ($dom->getElementsByTagName('a') as $node) {
                                if ($node->nodeValue != '') {
                                    $linksArray[$linksCount] = "<li><a target='_blank' href='".$node->getAttribute('href')."'>".$node->nodeValue.'</a></li>';
                                    ++$linksCount;
                                }
                            }
                        }

                        $linksArray = array_unique($linksArray);
                        natcasesort($linksArray);

                        foreach ($linksArray as $link) {
                            $links .= $link;
                        }

                        if ($links != '') {
                            echo '<h2>';
                            echo 'Links';
                            echo '</h2>';
                            echo '<ul>';
                            echo $links;
                            echo '</ul>';
                            $noReosurces = false;
                        }

                        //Images
                        $images = '';
                        $imagesArray = array();
                        $imagesCount = 0;
                        if (!empty($resourceContents)) {
                            $dom2 = new DOMDocument();
                            @$dom2->loadHTML($resourceContents);
                            foreach ($dom2->getElementsByTagName('img') as $node) {
                                if ($node->getAttribute('src') != '') {
                                    $imagesArray[$imagesCount] = "<img class='resource' style='margin: 10px 0; max-width: 560px' src='".$node->getAttribute('src')."'/><br/>";
                                    ++$imagesCount;
                                }
                            }
                        }

                        $imagesArray = array_unique($imagesArray);
                        natcasesort($imagesArray);

                        foreach ($imagesArray as $image) {
                            $images .= $image;
                        }

                        if ($images != '') {
                            echo '<h2>';
                            echo 'Images';
                            echo '</h2>';
                            echo $images;
                            $noReosurces = false;
                        }

                        //Embeds
                        $embeds = '';
                        $embedsArray = array();
                        $embedsCount = 0;
                        if (!empty($resourceContents)) {
                            $dom2 = new DOMDocument();
                            @$dom2->loadHTML($resourceContents);
                            foreach ($dom2->getElementsByTagName('iframe') as $node) {
                                if ($node->getAttribute('src') != '') {
                                    $embedsArray[$embedsCount] = "<iframe style='max-width: 560px' width='".$node->getAttribute('width')."' height='".$node->getAttribute('height')."' src='".$node->getAttribute('src')."' frameborder='".$node->getAttribute('frameborder')."'></iframe>";
                                    ++$embedsCount;
                                }
                            }
                        }

                        $embedsArray = array_unique($embedsArray);
                        natcasesort($embedsArray);

                        foreach ($embedsArray as $embed) {
                            $embeds .= $embed.'<br/><br/>';
                        }

                        if ($embeds != '') {
                            echo '<h2>';
                            echo 'Embeds';
                            echo '</h2>';
                            echo $embeds;
                            $noReosurces = false;
                        }

                        //No resources!
                        if ($noReosurces) {
                            echo "<div class='error'>";
                            echo __('There are no records to display.');
                            echo '</div>';
                        }
                        echo '</div>';
                        if ($disableOutcomes != 'Y') {
                            echo "<div id='tabs5'>";
                                $outcomesIntroduction = $settingGateway->getSettingByScope('Free Learning', 'outcomesIntroduction');
                                if (!empty($outcomesIntroduction)) {
                                    echo "<p>".$outcomesIntroduction."</p>";
                                }
                            
                                //Spit out outcomes
                                $unitOutcomeGateway = $container->get(UnitOutcomeGateway::class);

                                $criteria = $unitOutcomeGateway->newQueryCriteria(true)
                                    ->fromPOST();

                                $outcomes = $unitOutcomeGateway->selectOutcomesByUnit($freeLearningUnitID)->fetchAll();

                                $table = DataTable::createPaginated('outcomes', $criteria);

                                $bigDataSchool = $settingGateway->getSettingByScope('Free Learning', 'bigDataSchool');
                                
                                if ($bigDataSchool != 'Y') {
                                    $table->addExpandableColumn('content');
                                    $table->addColumn('scope', __('scope'));
                                    $table->addColumn('category', __('Category'));
                                    $table->addColumn('name', __('Name'))
                                        ->format(function($outcome) {
                                            $output = $outcome['nameShort']."<br/>";
                                            $output .= "<div class=\"text-xxs\">".$outcome['name']."</div>";
                                            return $output;
                                        });
                                    $table->addColumn('yearGroups', __('Year Groups'))
                                        ->format(function($outcome) use ($guid, $connection2) {
                                            return getYearGroupsFromIDList($guid, $connection2, $outcome['gibbonYearGroupIDList']);
                                        });
                                    } else {
                                        $table->addColumn('name', __('Name'));
                                        $table->addColumn('content', __('Description'));
                                    }

                                echo $table->render($outcomes);
                            echo '</div>';
                        }
                        if ($disableExemplarWork != 'Y') {
                            echo "<div id='tabs6'>";
                                $units = $unitStudentGateway->selectShowcase($freeLearningUnitID);

                                $criteria = $unitStudentGateway->newQueryCriteria(true)
                                    ->fromPOST();

                                $table = DataTable::createPaginated('units', $criteria);

                                $table->addColumn('unit', __('Unit'))
                                    ->format(function ($values) use ($session) {
                                        $return = '';
                                        if ($values['exemplarWorkThumb'] != '') {
                                            $return .= "<img style='width: 150px; height: 150px; margin: 5px 0' class='user' src='".$values['exemplarWorkThumb']."'/><br/>";
                                            if ($values['exemplarWorkLicense'] != '') {
                                                $return .= "<span style='font-size: 85%; font-style: italic'>".$values['exemplarWorkLicense'].'</span>';
                                            }
                                        } else {
                                            if ($values['logo'] != '') {
                                                $return .= "<img style='height: 150px; width: 150px; opacity: 1.0; margin: 5px 0' class='user' src='".$values['logo']."'/><br/>";
                                            }
                                            else {
                                                $return .= "<img style='height: 150px; width: 150px; opacity: 1.0; margin: 5px 0' class='user' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/><br/>";
                                            }
                                        }

                                        return $return;
                                    });

                                $table->addColumn('students', __('Students'))
                                    ->format(function ($values) {
                                        $return = preg_replace("/,([^,]+)$/", " & $1", $values['students'])."<br/>";

                                        $return .= Format::small(__m('Shared on')." ".Format::date($values['timestampCompleteApproved']));

                                        return $return;
                                    });

                                $table->addColumn('work', __('Work'))
                                    ->format(function ($values) use ($session) {
                                        $return = '';

                                        $return .= '<p class="mt-4">';
                                        if ($values['exemplarWorkEmbed'] =='') { //It's not an embed
                                            $extension = strrchr($values['evidenceLocation'], '.');
                                            if (strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) { //Its an image
                                                if ($values['evidenceType'] == 'File') { //It's a file
                                                    $return .= "<a target='_blank' href='".$session->get('absoluteURL').'/'.$values['evidenceLocation']."'><img class='user' style='max-width: 550px' src='".$session->get('absoluteURL').'/'.$values['evidenceLocation']."'/></a>";
                                                } else { //It's a link
                                                    $return .= "<a target='_blank' href='".$session->get('absoluteURL').'/'.$values['evidenceLocation']."'><img class='user' style='max-width: 550px' src='".$values['evidenceLocation']."'/></a>";
                                                }
                                            } else { //Not an image
                                                if ($values['evidenceType'] == 'File') { //It's a file
                                                    $return .= "<a class='button' target='_blank' href='".$session->get('absoluteURL').'/'.$values['evidenceLocation']."'>".__m('Click to View Work').'</a>';
                                                } else { //It's a link
                                                    $return .= "<a class='button' target='_blank' href='".$values['evidenceLocation']."'>".__m('Click to View Work').'</a>';
                                                }
                                            }
                                        } else {
                                            if (filter_var($values['exemplarWorkEmbed'], FILTER_VALIDATE_URL)) {
                                                $return .= "<a class='button' target='_blank' href='".$values['exemplarWorkEmbed']."'>".__m('Click to View Work').'</a>';
                                            } else {
                                                $return .= $values['exemplarWorkEmbed'];
                                            }
                                        }
                                        $return .= '<p>';

                                        $return .= "<br/>";

                                        $return .= Format::bold(__m('Student Comment'))."<br/><i>".$values['commentStudent']."</i><br/><br/>";
                                        $return .= Format::bold(__m('Teacher Comment'))."<br/><i>".$values['commentApproval']."</i>";

                                        return $return;
                                    });

                                echo $table->render($units);
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                }

                echo "<script type='text/javascript'>
                    $( \"#tabs\" ).tabs({
                            active: $defaultTab,
                            ajaxOptions: {
                                error: function( xhr, status, index, anchor ) {
                                    $( anchor.hash ).html(
                                        \"Couldn't load this tab.\" );
                                }
                            }
                        });
                </script>";

            }
        }
    }
}
?>
