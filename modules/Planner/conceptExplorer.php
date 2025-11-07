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

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Concept Explorer'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/conceptExplorer.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    // Get all concepts in current year and convert to ordered array
    $tagsAll = getTagList($connection2, $session->get('gibbonSchoolYearID'));

    // Deal with paramaters
    $tags = array();
    if (isset($_GET['tags'])) {
        $tags = $_GET['tags'] ?? '';
    }
    else if (isset($_GET['tag'])) {
        $tags[0] = $_GET['tag'] ?? '';
    }
    $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : '';

    // Display concept cloud
    if (count($tags) == 0) {
        echo '<h2>';
        echo __('Concept Cloud');
        echo '</h2>';
        echo getTagCloud($guid, $connection2, $session->get('gibbonSchoolYearID'));
    }

    // Allow tag selection
    $form = Form::create('conceptExplorer', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->setTitle(__('Choose Concept'));
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/conceptExplorer.php');

    $row = $form->addRow();
        $row->addLabel('tags', __('Concepts & Keywords'));
        $row->addSelect('tags')->fromArray(array_column($tagsAll, 1))->selectMultiple()->required()->selected($tags);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    if (count($tags) > 0) {
        // Set up for edit access
        $highestAction = getHighestGroupedAction($guid, '/modules/Planner/units.php', $connection2);
        $departments = array();
        if ($highestAction == 'Unit Planner_learningAreas') {
            $departmentCount = 1 ;
            try {
                $dataSelect = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                $sqlSelect = "SELECT gibbonDepartment.gibbonDepartmentID FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ORDER BY gibbonDepartment.name";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) { }
            while ($rowSelect = $resultSelect->fetch()) {
                $departments[$departmentCount] = $rowSelect['gibbonDepartmentID'];
                $departmentCount ++;
            }
        }

        // Search for units with these tags
        try {
            $data = [];

            // Tag filter
            $sqlWhere = ' AND (';
            $count = 0;
            foreach ($tags as $tag) {
                $data["tag$count"] = "%,$tag,%";
                $sqlWhere .= "concat(',',tags,',') LIKE :tag$count OR ";
                $count ++;
            }
            if ($sqlWhere == ' AND (')
                $sqlWhere = '';
            else
                $sqlWhere = substr($sqlWhere, 0, -3).')';

            // Year group Filters
            if ($gibbonYearGroupID != '') {
                $data['gibbonYearGroupID'] = '%'.$gibbonYearGroupID.'%';
                $sqlWhere .= ' AND gibbonYearGroupIDList LIKE :gibbonYearGroupID ';
            }


            $data['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
            $sql = "SELECT gibbonUnitID, gibbonUnit.name, gibbonUnit.description, attachment, tags, gibbonCourse.name AS course, gibbonDepartmentID, gibbonCourse.gibbonCourseID, gibbonSchoolYearID FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonUnit.map='Y' AND gibbonCourse.map='Y' $sqlWhere ORDER BY gibbonUnit.name";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }

        $units = $result->fetchAll();

        if (empty($units)) {
            echo $page->getBlankSlate();
        }
        else {
            $table = DataTable::create('conceptResults');
            $table->setTitle(__('Results'));

            $table->addColumn('name', __('Unit'))
                ->description(__('Course'))
                ->format(function ($row) {
                     return $row['name'].'<br/><span style="font-style: italic; font-size: 85%">'.$row['course'].'</span>';
                });

            $table->addColumn('description', __('Description'))
                ->format(function ($row) use ($session) {
                   $output = $row['description'].'<br/>';
                    if (!empty($row['attachment'])) {
                        $url = $session->get('absoluteURL').'/'.$row['attachment'];
                        $output .= Format::link($url, __('Download Unit Outline'));
                    }
                   return $output;
                });

            $table->addColumn('tags', __('Concepts & Keywords'))
                ->format(function ($row) use ($session, $tags) {
                    $tagsUnit = array_filter(array_map('trim', explode(',', $row['tags'])));
                    $out = [];
                    foreach ($tagsUnit as $tag) {
                        $style = '';
                        foreach ($tags as $tagInner) {
                            if ($tagInner == $tag) {
                                $style = "style='color: #000; font-weight: bold'";
                                break;
                            }
                        }
                        $url = $session->get('absoluteURL')."/index.php?q=/modules/Planner/conceptExplorer.php&tag=".urlencode($tag);
                        $out[] = "<a $style href=\"$url\">".htmlentities($tag)."</a>";
                    }
                    return implode(', ', $out);
                });

            $table->addActionColumn()
                ->addParam('gibbonUnitID')
                ->addParam('gibbonCourseID')
                ->addParam('gibbonSchoolYearID')
                ->format(function ($row, $actions) use ($highestAction, $departments) {
                    $canEdit = false;
                    if ($highestAction == 'Unit Planner_all') {
                        $canEdit = true;
                    } else if ($highestAction == 'Unit Planner_learningAreas') {
                        foreach ($departments as $department) {
                            if ($department == $row['gibbonDepartmentID']) {
                                $canEdit = true;
                                break;
                            }
                        }
                    }

                    if ($canEdit) 
                    {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Planner/units_edit.php');

                        $actions->addAction('view', __('View'))
                            ->addParam('sidebar', 'false')
                            ->setURL('/modules/Planner/units_dump.php');
                    }              
                });

            echo $table->render($units);
        }
    }
}