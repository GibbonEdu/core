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

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$publicUnits = $settingGateway->getSettingByScope('Free Learning', 'publicUnits');

if (!(isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php') == true or ($publicUnits == 'Y' and !$session->exists('username')))) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    if ($publicUnits == 'Y' and !$session->exists('username')) {
        $highestAction = 'Browse Units_all';
        $roleCategory = null ;
    } else {
        $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
        $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);
    }
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //UPDATE CODE FOR v5.19.00 - Populate new table if freeLearningUnitIDPrerequisiteList still exists and if module updater
        if ($roleCategory == "Staff" && isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_update.php')) {
            $sql = "SHOW COLUMNS FROM freeLearningUnit LIKE 'freeLearningUnitIDPrerequisiteList'";
            $exists = ($connection2->query($sql)->rowCount() > 0) ? true : false ;

            if ($exists) {
                $sql = "SELECT freeLearningUnitID, freeLearningUnitIDPrerequisiteList FROM freeLearningUnit";
                $units = $connection2->query($sql)->fetchAll();

                foreach ($units AS $unit) {
                    if (!empty($unit['freeLearningUnitIDPrerequisiteList'])) {
                        $prerequisites = explode(",", $unit['freeLearningUnitIDPrerequisiteList']);

                        foreach ($prerequisites AS $prerequisite) {
                            $data = ['freeLearningUnitID' => $unit['freeLearningUnitID'], 'freeLearningUnitIDPrerequisite' => $prerequisite];
                            $sql = "INSERT INTO freeLearningUnitPrerequisite SET freeLearningUnitID=:freeLearningUnitID, freeLearningUnitIDPrerequisite=:freeLearningUnitIDPrerequisite";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        }
                    }
                }

                $sql = "ALTER TABLE freeLearningUnit DROP COLUMN freeLearningUnitIDPrerequisiteList";
                $connection2->query($sql) ;
            }
        }


        // Breadcrumbs
        $page->breadcrumbs->add(__m('Browse Units'));

        $templateView = new View($container->get('twig'));
        $defaultBrowseView = $settingGateway->getSettingByScope('Free Learning', 'defaultBrowseView');
        $defaultBrowseCourse = $settingGateway->getSettingByScope('Free Learning', 'defaultBrowseCourse');

        // Get params
        $canManage = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') && $highestAction == 'Browse Units_all';
        $showInactive = $canManage && !empty($_GET['showInactive'])
            ? $_GET['showInactive']
            : 'N';

        $gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? $defaultBrowseCourse;
        $difficulty = $_GET['difficulty'] ?? '';
        $name = $_GET['name'] ?? '';

        $viewForm = $view = $_GET['view'] ?? $defaultBrowseView;
        if ($view != 'grid' and $view != 'list') {
            $view = $defaultBrowseView;
        }

        $advancedOptions = ($showInactive == "Y" || !empty($_GET['difficulty']) || !empty($_GET['name'])) ? true : false;

        // View the current user by default
        $gibbonPersonID = $session->get('gibbonPersonID');
        $viewingAsUser = false;

        // Allow viewing other users based on permissions/role
        if (($canManage || $roleCategory == 'Parent') && !empty($_GET['gibbonPersonID']) && $_GET['gibbonPersonID'] != $gibbonPersonID) {
            $gibbonPersonID = $_GET['gibbonPersonID'];
            $viewingAsUser = true;
        }

        // Setup default URLs
        $urlParams = compact('showInactive', 'gibbonDepartmentID', 'difficulty', 'name', 'gibbonPersonID');

        $defaultImage = $session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/anonymous_125.jpg';
        $viewUnitURL = "./index.php?q=/modules/Free Learning/units_browse_details.php&".http_build_query($urlParams)."&view=$view&sidebar=true";
        $browseUnitsURL = "./index.php?q=/modules/Free Learning/units_browse.php&".http_build_query($urlParams)."&sidebar=false";

        // CRITERIA
        $unitGateway = $container->get(UnitGateway::class);
        $unitStudentGateway = $container->get(UnitStudentGateway::class);
        $criteria = $unitGateway->newQueryCriteria()
            ->searchBy($unitGateway->getSearchableColumns(), $name)
            ->filterBy('showInactive', $showInactive)
            ->filterBy('department', $gibbonDepartmentID)
            ->filterBy('difficulty', $difficulty);

        // ADJUST VIEW BASED ON NUMBER OF UNITS
        $unitCheck = $unitGateway->queryAllUnits($criteria, $gibbonPersonID, $publicUnits, true)->toArray();
        $unitCount = $unitCheck[0]['count'] ?? 0;

        // There has to be a criteria and query to count the total number of units before we can
        // create the criteria that actually gets the number of units, because of the following
        // lines of code that switch to map view by default when maxMapSize is exceeded :(
        $maxMapSize = $settingGateway->getSettingByScope('Free Learning', 'maxMapSize');
        if ($unitCount > $maxMapSize && $view == "map") {
            $view = "grid";
        }

        $criteria = $unitGateway->newQueryCriteria()
            ->searchBy($unitGateway->getSearchableColumns(), $name)
            ->sortBy(['difficultyOrder', 'name'])
            ->filterBy('showInactive', $showInactive)
            ->filterBy('department', $gibbonDepartmentID)
            ->filterBy('difficulty', $difficulty)
            ->pageSize(($view == 'list' or $view == 'grid') ? 100 : 0)
            ->fromPOST('browseUnits');

        // FORM
        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->setClass('noIntBorder w-full');
        $form->addHiddenValue('q', '/modules/Free Learning/units_browse.php');
        $form->addHiddenValue('view', $viewForm);

        $row = $form->addRow();
            $row->addContent('<a class="show_hide" onclick="false" href="#">'.__('Advanced Options').'</a>')
                ->wrap('<span class="small">', '</span>')
                ->setClass('right');

        $disableLearningAreas = $settingGateway->getSettingByScope('Free Learning', 'disableLearningAreas');

        $courses = $unitStudentGateway->selectCoursesByStudent($session->get('gibbonPersonID'), $session->get('gibbonSchoolYearID'));
        $learningAreas = $unitGateway->selectLearningAreasAndCourses($session->get('gibbonPersonID'), $disableLearningAreas, $roleCategory, $session->get('gibbonSchoolYearID'), $highestAction, 'Browse');
        $row = $form->addRow();
            if ($disableLearningAreas != 'Y') {
                $row->addLabel('gibbonDepartmentID', __m('Learning Area & Course'));
            } else {
                $row->addLabel('gibbonDepartmentID', __('Course'));
            }
            $row->addSelect('gibbonDepartmentID')
                ->fromResults($courses, 'groupBy')
                ->fromResults($learningAreas, 'groupBy')
                ->selected($gibbonDepartmentID)
                ->placeholder();

        if ($canManage) {
            // Allow admins to view the map for any user
            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __m('View As'));
                $row->addSelectUsers('gibbonPersonID', $session->get('gibbonSchoolYearID'), ['includeStudents' => true])->selected($gibbonPersonID);
        } elseif ($roleCategory == 'Parent') {
            // Allow parents to view the map for their children
            $children = $container->get(StudentGateway::class)->selectActiveStudentsByFamilyAdult($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))->fetchAll();
            $children = Format::nameListArray($children, 'Student', false, true);

            if (empty($children[$gibbonPersonID])) {
                $gibbonPersonID = null;
            }

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __m('View As'));
                $row->addSelectPerson('gibbonPersonID', $session->get('gibbonSchoolYearID'), ['includeStudents' => true])
                    ->fromArray($children)
                    ->selected($gibbonPersonID);
        }

        $difficultyOptions = $settingGateway->getSettingByScope('Free Learning', 'difficultyOptions');
        $difficulties = array_map('trim', explode(',', $difficultyOptions));
        $row = $form->addRow()->addClass('advancedOptions');
            $row->addLabel('difficulty', __m('Difficulty'));
            $row->addSelect('difficulty')->fromArray($difficulties)->selected($difficulty)->placeholder();

        $row = $form->addRow()->addClass('advancedOptions');
            $row->addLabel('name', __m('Unit/Course Name'));
            $row->addTextField('name')->setValue($criteria->getSearchText());

        if ($canManage) {
            // Allow admins to view the map for any user
            $row = $form->addRow()->addClass('advancedOptions');
                $row->addLabel('showInactive', __m('Show Inactive Units?'));
                $row->addYesNo('showInactive')->selected($showInactive);
        }

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();

        // Control the show/hide for login options
        echo "<script type='text/javascript'>";
            if (!$advancedOptions) {
                echo '$(".advancedOptions").hide();';
            }
            echo '$(".show_hide").click(function(){';
            echo '$(".advancedOptions").fadeToggle(1000);';
            echo '});';
        echo '</script>';

        // QUERY
        if ($highestAction == 'Browse Units_all' && !$viewingAsUser) {
            $units = $unitGateway->queryAllUnits($criteria, $gibbonPersonID, $publicUnits);
        } else {
            $units = $unitGateway->queryUnitsByPrerequisites($criteria, $session->get('gibbonSchoolYearID'), $gibbonPersonID, !$viewingAsUser ? $roleCategory : '');
        }

        if ($unitCount > 999 && empty($gibbonDepartmentID)) {
            $page->addMessage(__m('There are too many units to display: please filter by Learning Area & Course.'));
        }
        else {
            // Join a set of author data per unit
            $unitAuthors = $unitGateway->selectUnitAuthors()->fetchGrouped();
            $units->joinColumn('freeLearningUnitID', 'authors', $unitAuthors);

            // Join a set of prerequisite data per unit
            $unitPrereq = $unitGateway->selectUnitPrerequisitesByPerson($gibbonPersonID)->fetchGrouped();
            $units->joinColumn('freeLearningUnitID', 'prerequisites', $unitPrereq);

            // Check prerequisites for each unit
            $units->transform(function (&$unit) use ($highestAction, $viewingAsUser) {
                if ($highestAction == 'Browse Units_prerequisites' || $viewingAsUser) {
                    $prerequisitesMet = count(array_filter($unit['prerequisites'] ?? [], function ($prereq) {
                        return $prereq['complete'] == 'Y';
                    })) >= count($unit['prerequisites']);
                    $unit['prerequisitesMet'] = $prerequisitesMet ? 'Y' : 'N';
                } else {
                    $unit['prerequisitesMet'] = null;
                }

                switch ($unit['status']) {
                    case 'Complete - Approved':
                        $unit['statusClass'] = 'success';
                        break;
                    case 'Exempt':
                        $unit['statusClass'] = 'exempt';
                        break;
                    case 'Current':
                        $unit['statusClass'] = 'currentUnit';
                        break;
                    case 'Current - Pending':
                        $unit['statusClass'] = 'currentPending';
                        break;
                    case 'Complete - Pending':
                        $unit['statusClass'] = 'pending';
                        break;
                    case 'Evidence Not Yet Approved':
                        $unit['statusClass'] = 'warning';
                        break;
                    default:
                        $unit['statusClass']  = '';
                }
            });

            // NAVIGATION BUTTONS
            echo $templateView->fetchFromTemplate('unitButtons.twig.html', [
                'browseUnitsURL' => $browseUnitsURL,
                'publicUnits'    => $publicUnits,
                'gibbonPersonID' => $gibbonPersonID,
                'view'           => $view,
                'maxMapSize'     => $maxMapSize,
                'count'          => $unitCount
            ]);

            if ($units->getResultCount() == 0) {
                echo Format::alert(__('There are no records to display.'), 'error');
            } else {
                if ($view == 'list') {
                    // DATA TABLE
                    $table = DataTable::createPaginated('browseUnitsList', $criteria);
                    $table->setTitle(__('Units'));

                    $table->modifyRows(function ($unit, $row) {
                        if ($unit['active'] != 'Y') $row->addClass('error');
                        if (!empty($unit['statusClass'])) $row->addClass($unit['statusClass']);
                        return $row;
                    });

                    $table->addColumn('name', __m('Unit Name'))
                        ->description(__('Status'))
                        ->context('primary')
                        ->width('15%')
                        ->format(function ($unit) use ($defaultImage) {
                            $imageClass = 'w-20 h-20 sm:w-32 sm:h-32 p-1 block mx-auto shadow bg-white border border-gray-600';

                            $output = '<div class="text-sm sm:text-base font-bold text-center mt-1 mb-2">'.$unit['name'].'</div>';
                            $output .= sprintf('<img class="%1$s" src="%2$s">', $imageClass, $unit['logo'] ?? $defaultImage);
                            $output .= !empty($unit['status']) ? '<div class="text-sm text-center mt-2">'.$unit['status'].'</div>' : '';

                            return $output;
                        });

                    $table->addColumn('learningArea', __('Learning Areas'))
                        ->description(__m('Authors'))
                        ->context('secondary')
                        ->sortable(['learningArea'])
                        ->width('12%')
                        ->format(function ($unit) {
                            $unit['authors'] = array_map(function ($author) use (&$unit) {
                                $name = $author['preferredName'].' '.$author['surname'];
                                return !empty($author['website'])
                                    ? Format::link($author['website'], $name)
                                    : $name;
                            }, $unit['authors'] ?? []);

                            $output = !empty($unit['learningArea']) ? '<div class="text-xs mb-2">'.$unit['learningArea'].'</div>' : '';
                            $output .= !empty($unit['course']) && ($unit['learningArea'] != $unit['course']) ? '<div class="text-xs mb-2">'.$unit['course'].'</div>' : '';
                            $output .= '<div class="text-xxs">'.implode('<br/>', $unit['authors']).'</div>';
                            return $output;
                        });

                    $table->addColumn('difficultyOrder', __m('Difficulty'))
                        ->description(__m('Blurb'))
                        ->width('40%')
                        ->format(function ($unit) {
                            $output = '<div class="text-xs font-bold mb-1">'.$unit['difficulty'].'</div>';
                            $output .= '<div class="text-xs">'.$unit['blurb'].'</div>';

                            return $output;
                        });

                    $table->addColumn('length', __m('Length'))
                        ->format(function ($unit) {
                            $minutes = intval($unit['length']);
                            $relativeTime = __n('{count} min', '{count} mins', $minutes);
                            if ($minutes > 60) {
                                $hours = round($minutes / 60, 1);
                                $relativeTime = Format::tooltip(__n('{count} hr', '{count} '.__m('hrs'), ceil($minutes / 60), ['count' => $hours]), $relativeTime);
                            }

                            return !empty($unit['length']) ? $relativeTime : Format::small(__('N/A'));
                        });

                    $table->addColumn('grouping', __m('Grouping'))
                        ->format(function ($unit) {
                            $output = '';
                            foreach (explode(',', $unit['grouping']) as $grouping) {
                                $output .= __m($grouping)."<br/>";
                            }
                            return $output ;
                        });

                    $table->addColumn('prerequisites', __m('Prerequisites'))
                        ->context('primary')
                        ->sortable('freeLearningUnitIDPrerequisiteList')
                        ->format(function ($unit) use (&$viewUnitURL, &$highestAction, $viewingAsUser) {
                            $output = '';
                            $prerequisiteList = array_map(function ($prereq) use (&$unit, &$viewUnitURL) {
                                $url = $viewUnitURL.'&freeLearningUnitID='.$unit['freeLearningUnitID'];
                                return Format::link($url, $prereq['name']);
                            }, $unit['prerequisites'] ?? []);

                            if (($highestAction == 'Browse Units_prerequisites' || $viewingAsUser) && empty($unit['status']) && !empty($unit['prerequisites'])) {
                                if ($unit['prerequisitesMet'] == 'Y') {
                                    $output = '<span class="tag inline-block success mb-2">'.__m('OK!').'</span><br/>';
                                } elseif ($unit['prerequisitesMet'] == 'N') {
                                    $output = '<span class="tag inline-block dull mb-2">'.__m('Not Met').'</span><br/>';
                                }
                            }

                            $output .= !empty($prerequisiteList)
                                ? implode('<br/>', $prerequisiteList)
                                : Format::small(__('None'));

                            return $output;
                        });

                    // ACTIONS
                    $table->addActionColumn()
                        ->addParam('gibbonDepartmentID', $gibbonDepartmentID)
                        ->addParam('difficulty', $difficulty)
                        ->addParam('name', $name)
                        ->addParam('freeLearningUnitID')
                        ->format(function ($unit, $actions) use ($highestAction, $viewingAsUser) {
                            $actions->addAction('view', __('View'))
                                    ->addParam('sidebar', 'true')
                                    ->addParam('showInactive', $highestAction == 'Browse Units_all' && !$viewingAsUser ? 'Y' : 'N')
                                    ->setURL('/modules/Free Learning/units_browse_details.php');
                        });

                    echo $table->render($units);

                } elseif ($view == 'grid') {
                    // GRID TABLE
                    $gridRenderer = $container->get(GridView::class)->setCriteria($criteria);
                    $table = DataTable::create('browseUnits')->setRenderer($gridRenderer);
                    $table->setTitle(__('Units'));

                    $table->addMetaData('gridClass', 'flex items-stretch -mx-4');
                    $table->addMetaData('gridItemClass', 'foo');

                    $table->addColumn('logo')
                        ->setClass('h-full pb-8')
                        ->format(function ($unit) use (&$templateView, &$defaultImage, &$viewUnitURL, $viewingAsUser) {
                            return $templateView->fetchFromTemplate(
                                'unitCard.twig.html',
                                $unit + ['defaultImage' => $defaultImage, 'viewUnitURL' => $viewUnitURL, 'viewingAsUser' => $viewingAsUser]
                            );
                        });

                    echo $table->render($units);

                } elseif ($view == 'map') {
                    // VISUAL MAP
                    echo '<p>';
                    echo __m('The map below shows all units selected by the filters above. Lines between units represent prerequisites. Units without prerequisites, which make good starting units, are highlighted by a blue border.');
                    echo '</p>';
                    echo '<div class="text-xs py-2">';
                        echo __m('{count} Records', ['count' => count($units)]);
                    echo '</div>';

                    ?>
                    <script type="text/javascript" src="<?php echo $session->get('absoluteURL') ?>/lib/vis/dist/vis.js"></script>
                    <link href="<?php echo $session->get('absoluteURL') ?>/lib/vis/dist/vis.css" rel="stylesheet" type="text/css" />

                    <div id="map" class="w-full border rounded shadow-inner mb-4" style="height: 800px;"></div>

                    <?php
                    //PREP NODE AND EDGE ARRAYS DATA
                    $nodeArray = array();
                    $edgeArray = array();
                    $nodeList = '';
                    $edgeList = '';
                    $idList = '';
                    $countNodes = 0;
                    foreach ($units as $unit) {
                        if ($units->getResultCount() <= 125) {
                            if ($unit['logo'] != '') {
                                $image = $unit['logo'];
                            } else {
                                $image = $session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/anonymous_240_square.jpg';
                            }
                        } else {
                            $image = 'undefined';
                        }

                        $minutes = intval($unit['length']);
                        $relativeTime = __n('{count} min', '{count} mins', $minutes);
                        if ($minutes > 60) {
                            $hours = round($minutes / 60, 1);
                            $relativeTime = Format::tooltip(__n('{count} hr', '{count} '.__m('hrs'), ceil($minutes / 60), ['count' => $hours]), $relativeTime);
                        }

                        $time = !empty($unit['length']) ? $relativeTime : __('N/A');

                        $titleTemp = $string = trim(preg_replace('/\s\s+/', ' ', $unit['blurb']));
                        $title = '<div class="text-base font-bold">'.addSlashes($unit['name']).'</div>';
                        $title .= '<div class="text-xs text-gray-600 italic mb-2">'.__m('Difficulty').": ".addSlashes($unit['difficulty'])." | ".__m("Length").": ".$time.'</div>';

                        if ($unit['active'] != 'Y') {
                            $title .= '<span class="z-10 tag error block absolute right-0 top-0 mt-2 mr-2">'.__('Not Active').'</span>';
                        } else if (!empty($unit['status'])) {
                            $title .= '<span class="z-10 tag '.$unit['statusClass'].' block absolute right-0 top-0 mt-2 mr-2">'.$unit['status'].'</span>';
                        } else if ($highestAction == 'Browse Units_prerequisites' || $viewingAsUser) {
                            if ($unit['prerequisitesMet'] == 'Y') {
                                $title .= '<span class="z-10 tag success block absolute right-0 top-0 mt-2 mr-2">'.__('Ok!').'</span>';
                            } else if ($unit['prerequisitesMet'] == 'N') {
                                $title .= '<span class="z-10 tag dull block absolute right-0 top-0 mt-2 mr-2">'.__('Not Met').'</span>';
                            }
                        }

                        if (strlen($unit['blurb']) > 250) {
                            $title .= addSlashes(substr($titleTemp, 0, 250)).'...';
                        } else {
                            $title .= addSlashes($titleTemp);
                        }

                        if ($unit['status'] == 'Complete - Approved') {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($unit['name'])."', title: '".$title."', color: {border:'#390', background:'#D4F6DC'}, borderWidth: 2},";
                        } elseif ($unit['status'] == 'Exempt') {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($unit['name'])."', title: '".$title."', color: {border:'#e520b7', background:'#f9dbf2'}, borderWidth: 2},";
                        } elseif ($unit['status'] == 'Current') {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($unit['name'])."', title: '".$title."', color: {border:'#0EA5E9', background:'#BAE6FD'}, borderWidth: 2},";
                        } elseif ($unit['status'] == 'Current - Pending') {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($unit['name'])."', title: '".$title."', color: {border:'#d69e2e', background:'#faf089'}, borderWidth: 2},";
                        } elseif ($unit['status'] == 'Evidence Not Yet Approved') {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($unit['name'])."', title: '".$title."', color: {border:'#D65602', background:'#FFD2A9'}, borderWidth: 2},";
                        } elseif ($unit['status'] == 'Complete - Pending') {
                            $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: 'undefined', label: '".addSlashes($unit['name'])."', title: '".$title."', color: {border:'#78529e', background:'#c6a5e8'}, borderWidth: 2},";
                        }
                        else {
                            if ($unit['freeLearningUnitIDPrerequisiteList'] == '') {
                                $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: '$image', label: '".addSlashes($unit['name'])."', title: '".$title."', color: {border:'blue'}, borderWidth: 7},"; //#2b7ce9
                            } else {
                                $nodeList .= '{id: '.$countNodes.", shape: 'circularImage', image: '$image', label: '".addSlashes($unit['name'])."', title: '".$title."', color: {border:'#555555'}, borderWidth: 3},";
                            }
                        }

                        $nodeArray[$unit['freeLearningUnitID']][0] = $countNodes;
                        $nodeArray[$unit['freeLearningUnitID']][1] = $unit['freeLearningUnitID'];
                        $nodeArray[$unit['freeLearningUnitID']][2] = $unit['freeLearningUnitIDPrerequisiteList'];
                        $idList .= "'".$unit['freeLearningUnitID']."',";
                        ++$countNodes;
                    }
                    if ($nodeList != '') {
                        $nodeList = substr($nodeList, 0, -1);
                    }
                    if ($idList != '') {
                        $idList = substr($idList, 0, -1);
                    }

                    foreach ($nodeArray as $node) {
                        if (isset($node[2])) {
                            $edgeExplode = explode(',', $node[2]);
                            foreach ($edgeExplode as $edge) {
                                if (isset($nodeArray[$edge][0])===true) {
                                    if (is_numeric($nodeArray[$edge][0])) {
                                        $edgeList .= '{from: '.$nodeArray[$node[1]][0].', to: '.$nodeArray[$edge][0].", arrows:'from'},";
                                    }
                                }
                            }
                        }
                    }
                    if ($edgeList != '') {
                        $edgeList = substr($edgeList, 0, -1);
                    }

                    ?>
                    <script type="text/javascript">
                        htmx.onLoad(function (content) {
                        // https://visjs.org/docs/network/

                        //CREATE NODE ARRAY
                        var nodes = new vis.DataSet([<?php echo $nodeList; ?>]);

                        //CREATE EDGE ARRAY
                        var edges = new vis.DataSet([<?php echo $edgeList ?>]);

                        //CREATE NODE TO freeLearningUnitID ARRAY
                        var ids = new Array(<?php echo $idList ?>);

                        //CREATE NETWORK
                        var container = document.getElementById('map');
                        var data = {
                        nodes: nodes,
                        edges: edges
                        };
                        var options = {
                            nodes: {
                                borderWidth: 4,
                                size:30,
                                color: {
                                    border: '#222222',
                                    background: '#dddddd'
                                },
                                font:{
                                    color:'#333',
                                },
                                shadow: true
                            },
                            edges: {
                                width: 3,
                                selectionWidth: 0,
                                color: {
                                    color: '#bbbbbb',
                                    inherit: false
                                },
                                shadow: false,
                                arrows: {
                                    from: {
                                        enabled: true,
                                        scaleFactor: 0.6
                                    }
                                },
                            },

                            interaction:{
                                navigationButtons: true,
                                zoomView: false
                            },
                            layout: {
                                randomSeed: 0.5,
                                improvedLayout:true
                            }
                        };
                        var network = new vis.Network(container, data, options);

                        //CLICK LISTENER
                        network.on( 'click', function(properties) {
                            var nodeNo = properties.nodes ;
                            if (nodeNo != '') {
                                window.location = '<?php echo $session->get('absoluteURL') ?>/index.php?q=/modules/Free Learning/units_browse_details.php&sidebar=true&freeLearningUnitID=' + ids[nodeNo] + '&gibbonDepartmentID=<?php echo $gibbonDepartmentID ?>&difficulty=<?php echo $difficulty ?>&showInactive=<?php echo $showInactive; ?>&name=<?php echo $name ?>&view=<?php echo $view ?>';
                            }
                        });

                        });
                    </script>
                    <?php
                }

                echo $templateView->fetchFromTemplate('unitLegend.twig.html');
            }
        }
    }
}
?>
