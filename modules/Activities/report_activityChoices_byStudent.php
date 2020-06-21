<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Activities\ActivityReportGateway;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityChoices_byStudent.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Activity Choices By Student'));

    echo '<h2>';
    echo __('Choose Student');
    echo '</h2>';

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    $form = Form::create('action',  $_SESSION[$guid]['absoluteURL']."/index.php", "get");

    $form->setClass('noIntBorder fullWidth');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_activityChoices_byStudent.php");

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'], array("allStudents" => false, "byName" => true, "byRoll" => true))->required()->placeholder()->selected($gibbonPersonID);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if($gibbonPersonID != '')
    {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
        $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
        if ($dateType == 'Term') {
            $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
        }

        $gateway = $container->get(ActivityReportGateway::class);
        $criteriaYears = $gateway
          ->newQueryCriteria()
          ->filterBy('gibbonPersonID',$gibbonPersonID)
          ->fromPOST();
        $enroledYears = $gateway->queryStudentYears($criteriaYears);
        foreach($enroledYears as $enroledYear)
        {
          //Bools for storing error message state. Shown per year for better visibility.
          $showTermError = false;
          $showDateError = false;

          $criteriaActivities = $gateway
            ->newQueryCriteria()
            ->filterBy('gibbonPersonID',$gibbonPersonID)
            ->filterBy('gibbonSchoolYearID',$enroledYear['gibbonSchoolYearID']);

          $activities = $gateway->queryStudentActivities($criteriaActivities);
          $table = DataTable::createPaginated('activities',$criteriaActivities);
          $table->setTitle(__($enroledYear['name']));
          $table->addColumn('activityName',__('Activity'));
          if($options != '')
          {
            $table->addColumn('activityType',__('Type'));
          }

          if($dateType != 'Date')
          {
            //If system is configured for term based activities, show the term assigned to the assigned activity
            $table->addColumn('terms',__('Terms'))
                  ->format(function($item){
                    //Check terms have been assigned to this activity
                    if($item['terms'] == '')
                    {
                      if($item['programStart'] != '' || $item['programEnd'] != '')
                      {
                        return Format::small(__('Assigned to date'));
                      }
                      return Format::small(__('Not assigned to term'));
                    }
                    return $item['terms'];
                  });
          }
          else
          {
            //If system is configured for date based activities, summarise the date range
            //e.g. 01-01-2020 -> 31-07-2020 into July 2020
            $table->addColumn('dates',__('Dates'))
                  ->format(function($item) {
                    if($item['programStart'] == '' || $item['programEnd'] == '')
                    {
                      if($item['terms'] != '')
                      {
                        return Format::small(__('Assigned to term'));
                      }
                      return Format::small(__('Not assigned to date range'));
                    }
                    else
                    {
                      return Format::dateRangeReadable($item['programStart'],$item['programEnd']);
                    }
                  });
          }

          $table->addColumn('status',__('Status'));
          $table->addActionColumn()
                ->addParam('gibbonActivityID')
                ->format(function ($item,$actions)
                {
                  $actions
                    ->addAction('view',__('View Details'))
                    ->setURL('/modules/Activities/activities_view_full.php')
                    ->isModal();
                });

          //Error handling where a required column doesn't exist. Won't break the table but should be highlighted.

          foreach($activities as $activity)
          {
            switch($dateType)
            {
              case 'Date':
                if($activity['programStart'] || $activity['programEnd'])
                {
                  $showDateError = true;
                }
                break;

              case 'Term':
              default:
                if($activity['terms'] == '')
                {
                  $showTermError = true;
                }
                break;
            }
          }

          if($showTermError) {
            echo Format::alert(__('Some activities shown in the table below are not assigned to terms. Check the term column to find out which activities are affected. These may assigned to dates as part of a migration, in which case you should consider changing these to term based activities.'));
          }

          if($showDateError) {
            echo Format::alert(__('Some activities shown in the table below are not assigned to dates. Check the dates column to find out which activities are affected. These may be assigned to terms as part of a migration, in which case you should consider changing these to date based activities.'));
          }

          echo $table->render($activities);
        }

    }

    if ($gibbonPersonID != '') {
        $output = '';
        
        try {
            $dataYears = array('gibbonPersonID' => $gibbonPersonID);
            $sqlYears = 'SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC';
            $resultYears = $connection2->prepare($sqlYears);
            $resultYears->execute($dataYears);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($resultYears->rowCount() < 1) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            $yearCount = 0;
            while ($rowYears = $resultYears->fetch()) {
                $class = '';
                if ($yearCount == 0) {
                    $class = "class='top'";
                }
                echo "<h3 $class>";
                echo $rowYears['name'];
                echo '</h3>';

                ++$yearCount;

                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The specified record does not exist.');
                    echo '</div>';
                } else {
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $rowYears['gibbonSchoolYearID']);
                        $sql = "SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($result->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __('There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __('Activity');
                        echo '</th>';
                        if ($options != '') {
                            echo '<th>';
                            echo __('Type');
                            echo '</th>';
                        }
                        echo '<th>';
                        if ($dateType != 'Date') {
                            echo  __('Term');
                        } else {
                            echo  __('Dates');
                        }
                        echo '</th>';
                        echo '<th>';
                        echo __('Status');
                        echo '</th>';
                        echo '<th>';
                        echo __('Actions');
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';
                        while ($row = $result->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }

                            ++$count;

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo $row['name'];
                            echo '</td>';
                            if ($options != '') {
                                echo '<td>';
                                echo trim($row['type']);
                                echo '</td>';
                            }
                            echo '<td>';
                            if ($dateType != 'Date') {
                                $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID'], true);
                                $termList = '';
                                for ($i = 0; $i < count($terms); $i = $i + 2) {
                                    if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                                        $termList .= $terms[($i + 1)].'<br/>';
                                    }
                                }
                                echo $termList;
                            } else {
                                if (substr($row['programStart'], 0, 4) == substr($row['programEnd'], 0, 4)) {
                                    if (substr($row['programStart'], 5, 2) == substr($row['programEnd'], 5, 2)) {
                                        echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4);
                                    } else {
                                        echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).'<br/>'.substr($row['programStart'], 0, 4);
                                    }
                                } else {
                                    echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).' -<br/>'.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programEnd'], 0, 4);
                                }
                            }
                            echo '</td>';
                            echo '<td>';
                            if ($row['status'] != '') {
                                echo __($row['status']);
                            } else {
                                echo '<i>'.__('NA').'</i>';
                            }
                            echo '</td>';
                            echo '<td>';
                            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_view_full.php&gibbonActivityID='.$row['gibbonActivityID']."&width=1000&height=550'><img title='".__('View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                }
            }
        }
    }
}
?>
