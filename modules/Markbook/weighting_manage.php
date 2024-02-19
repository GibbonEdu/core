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
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage.php') == false) {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {

        if ($container->get(SettingGateway::class)->getSettingByScope('Markbook', 'enableColumnWeighting') != 'Y') {
            //Acess denied
            $page->addError(__('Your request failed because you do not have access to this action.'));
            return;
        }

        //Get class variable
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';

        if ($gibbonCourseClassID == '') {
            $gibbonCourseClassID = $session->get('markbookClass') ?? '';
        }

        if ($gibbonCourseClassID == '') {
            echo '<h1>';
            echo __('Manage Weighting');
            echo '</h1>';
            echo "<div class='warning'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';

            //Get class chooser
            echo classChooser($guid, $pdo, $gibbonCourseClassID);
            return;
        }
        //Check existence of and access to this class.
        else {
            try {
                if ($highestAction == 'Manage Weightings_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() != 1) {
                echo '<h1>';
                echo __('Manage Weightings');
                echo '</h1>';
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                $row = $result->fetch();

                $page->breadcrumbs->add(__('Manage {courseClass} Weightings', [
                    'courseClass' => Format::courseClassName($row['course'], $row['class']),
                ]));

                //Get teacher list
                $teacherList = getTeacherList($pdo, $gibbonCourseClassID);
                $teaching = (isset($teacherList[ $session->get('gibbonPersonID') ]) );

                //Print mark
                echo '<h3>';
                echo __('Markbook Weightings');
                echo '</h3>';

                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT * FROM gibbonMarkbookWeight WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY calculate, weighting DESC';
                $result = $connection2->prepare($sql);
                $result->execute($data);

                if ($teaching || $highestAction == 'Manage Weightings_everything') {
                    $params = [
                        "gibbonCourseClassID" => $gibbonCourseClassID
                    ];
                    $page->navigator->addHeaderAction('add', __('Add'))
                        ->setURL('/modules/Markbook/weighting_manage_add.php')
                        ->addParams($params)
                        ->setIcon('page_new')
                        ->displayLabel();
                }

                if ($result->rowCount() < 1) {
                    echo $page->getBlankSlate();
                } else {
                    echo "<table class='colorOddEven' cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __('Type');
                    echo '</th>';
                    echo '<th width="200px">';
                    echo __('Description');
                    echo '</th>';
                    echo '<th>';
                    echo __('Weighting');
                    echo '</th>';
                    echo '<th>';
                    echo __('Percent of');
                    echo '</th>';
                    echo '<th>';
                    echo __('Reportable?');
                    echo '</th>';
                    echo '<th style="width:80px">';
                    echo __('Actions');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $totalTermWeight = 0;
                    $totalYearWeight = 0;

                    $weightings = $result->fetchAll();
                    foreach ($weightings as $row) {

                        if ($row['calculate'] == 'term' && $row['reportable'] == 'Y') {
                            $totalTermWeight += floatval($row['weighting']);
                        } else if ($row['calculate'] == 'year' && $row['reportable'] == 'Y') {
                            $totalYearWeight += floatval($row['weighting']);
                        }

                        echo "<tr>";
                        echo '<td>';
                        echo $row['type'];
                        echo '</td>';
                        echo '<td>';
                        echo $row['description'];
                        echo '</td>';
                        echo '<td>';
                        echo floatval($row['weighting']).'%';
                        echo '</td>';
                        echo '<td>';
                        echo ($row['calculate'] == 'term')? __('Cumulative Average') : __('Final Grade');
                        echo '</td>';
                        echo '<td>';
                        echo ($row['reportable'] == 'Y')? __('Yes') : __('No');
                        echo '</td>';
                        echo '<td>';
                        echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/weighting_manage_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookWeightID=".$row['gibbonMarkbookWeightID']."'><img title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                        echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module')."/weighting_manage_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookWeightID=".$row['gibbonMarkbookWeightID']."&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
                        echo '</td>';
                        echo '</tr>';

                        ++$count;
                    }
                    echo '</table><br/>';


                    // Sample term calculation
                    if ($totalTermWeight > 0) {

                        echo '<h4>';
                        echo __('Sample Calculation') .': '. __('Cumulative Average');
                        echo '</h4>';

                        if ($totalTermWeight != 100) {
                            echo "<div class='warning'>";
                            printf ( __('Total cumulative weighting is %s. Calculated averages may not be accurate if the total weighting does not add up to 100%%.'), floatval($totalTermWeight).'%' );
                            echo '</div>';
                        }

                        echo '<table class="blank" style="font-size:100%;min-width: 250px;">';
                        $count = 0;
                        foreach ($weightings as $row) {
                            if ($row['calculate'] != 'term') continue;
                            if ($row['reportable'] == 'N') continue;
                            echo '<tr>';
                                echo '<td style="width:20px;text-align:right;">'. (($count != 0)? '+' : '') .'</td>';
                                printf( '<td style="text-align:left">%s%% of %s</td>', floatval($row['weighting']), $row['description'] );
                            echo '</tr>';

                            $count++;
                        }
                        echo '<tr>';
                            echo '<td colspan=2 style="border-top: 2px solid #999999 !important;height: 5px !important;"></td>';
                        echo '</tr>';
                        echo '<tr>';
                            echo '<td style="text-align:right;">=</td>';
                            echo '<td style="text-align:left">'. __('Cumulative Average') .'</td>';
                        echo '</tr>';
                        echo '</table><br/>';
                    }

                    if ($totalYearWeight > 0) {
                        echo '<h4>';
                        echo __('Sample Calculation') .': '. __('Final Grade');
                        echo '</h4>';

                        if ($totalYearWeight >= 100 || (100 - $totalYearWeight) <= 0) {
                            echo "<div class='warning'>";
                            printf ( __('Total final grade weighting is %s. Calculated averages may not be accurate if the total weighting  exceeds 100%%.'), floatval($totalYearWeight).'%' );
                            echo '</div>';
                        }

                        // Sample whole year calculation
                        echo '<table class="blank" style="font-size:100%;min-width: 250px;">';

                        echo '<tr>';
                            echo '<td style="width:20px;"></td>';
                            printf( '<td style="text-align:left">%s%% of %s</td>', floatval( max(0, 100 - $totalYearWeight) ), __('Cumulative Average') );
                        echo '</tr>';

                        foreach ($weightings as $row) {
                            if ($row['calculate'] != 'year') continue;
                            if ($row['reportable'] == 'N') continue;
                            echo '<tr>';
                                echo '<td style="width:20px;text-align:right;">'. '+' .'</td>';
                                printf( '<td style="text-align:left">%s%% of %s</td>', floatval($row['weighting']), $row['description'] );
                            echo '</tr>';

                            $count++;
                        }
                        echo '<tr>';
                            echo '<td colspan=2 style="border-top: 2px solid #999999 !important;height: 5px !important;"></td>';
                        echo '</tr>';
                        echo '<tr>';
                            echo '<td style="text-align:right;">=</td>';
                            echo '<td style="text-align:left">'. __('Final Grade') .'</td>';
                        echo '</tr>';
                        echo '</table>';
                    }


                }

                echo '<br/>&nbsp;<br/>';

                echo '<h3>';
                echo __('Copy Weightings');
                echo '</h3>';

                $form = Form::create('searchForm', $session->get('absoluteURL').'/modules/'.$session->get('module').'/weighting_manage_copyProcess.php?gibbonCourseClassID='.$gibbonCourseClassID);
                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setClass('noIntBorder fullWidth');

                $col = $form->addRow()->addColumn()->addClass('inline right');
                    $col->addContent(__('Copy from').' '.__('Class').':');
                    $col->addSelectClass('gibbonWeightingCopyClassID', $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))->setClass('mediumWidth');
                    $col->addSubmit(__('Go'));

                echo $form->getOutput();
            }
        }
    }

    // Print the sidebar
    $session->set('sidebarExtra',sidebarExtra($guid, $pdo, $session->get('gibbonPersonID'), $gibbonCourseClassID, 'weighting_manage.php'));
}
