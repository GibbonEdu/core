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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Set returnTo point for upcoming pages
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Activities').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h2>';
    echo __($guid, 'Search & Filter');
    echo '</h2>';

    $search = isset($_GET['search'])? $_GET['search'] : null;
    $gibbonSchoolYearTermID = isset($_GET['gibbonSchoolYearTermID'])? $_GET['gibbonSchoolYearTermID'] : null;
    $dateType = getSettingByScope($connection2, 'Activities', 'dateType');

    $paymentOn = true;
    if (getSettingByScope($connection2, 'Activities', 'payment') == 'None' or getSettingByScope($connection2, 'Activities', 'payment') == 'Single') {
        $paymentOn = false;
    }

    $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/activities_manage.php");

    $row = $form->addRow();
        $row->addLabel('search', __('Search'))->description('Activity name.');
        $row->addTextField('search')->setValue($search);

    if ($dateType != 'Date') {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonSchoolYearTermID as value, name FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber";
        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearTermID', __('Term'));
            $row->addSelect('gibbonSchoolYearTermID')->fromQuery($pdo, $sql, $data)->selected($gibbonSchoolYearTermID)->placeholder();
    }

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __($guid, 'Activities');
    echo '</h2>';

    //Set pagination variable
    $page = isset($_GET['page'])? $_GET['page'] : 1;
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    //Should we show date as term or date?
    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
    $sqlOrderBy = 'ORDER BY programStart DESC, name';
    if ($dateType != 'Date') {
        $sqlOrderBy = 'ORDER BY gibbonSchoolYearTermIDList, name';
    }
    $sqlWhere = '';
    if ($search != '') {
        $data['search'] = "%$search%";
        $sqlWhere = ' AND name LIKE :search';
    }
    if ($gibbonSchoolYearTermID != '') {
        $data['gibbonSchoolYearTermID'] = "%$gibbonSchoolYearTermID%";
        $sqlWhere .= ' AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermID';
    }

    $sql = "SELECT gibbonActivity.*, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere $sqlOrderBy";

    $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
    try {
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed due to a database error.');
        echo '</div>';
    }

    if ($result) {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_manage_add.php&search='.$search."&gibbonSchoolYearTermID=".$gibbonSchoolYearTermID."'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "search=$search");
            }

            try {
                $resultPage = $connection2->prepare($sqlPage);
                $resultPage->execute($data);
            } catch (PDOException $e) {
            }

            $form = Form::create('bulkAction', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_manageProcessBulk.php');
            $form->getRenderer()->setWrapper('form', 'div');
            $form->getRenderer()->setWrapper('row', 'div');
            $form->getRenderer()->setWrapper('cell', 'fieldset');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('search', $search);

            $actions = array(
                'Duplicate' => __('Duplicate'),
                'DuplicateParticipants' => __('Duplicate With Participants'),
                'Delete' => __('Delete'),
            );
            $sql = "SELECT gibbonSchoolYearID as value, gibbonSchoolYear.name FROM gibbonSchoolYear WHERE (status='Upcoming' OR status='Current') ORDER BY sequenceNumber LIMIT 0, 2";
            
            $row = $form->addRow()->setClass('right');
            $bulkAction = $row->addColumn()->addClass('inline right');
                $bulkAction->addSelect('action')
                    ->fromArray($actions)
                    ->isRequired()
                    ->setClass('mediumWidth floatNone')
                    ->placeholder(__('Select action'));
                $bulkAction->addSelect('gibbonSchoolYearIDCopyTo')
                    ->fromQuery($pdo, $sql)
                    ->setClass('shortWidth floatNone schoolYear');
                $bulkAction->addSubmit(__('Go'));
        
            $form->toggleVisibilityByClass('schoolYear')->onSelect('action')->when(array('Duplicate', 'DuplicateParticipants'));
            $form->addConfirmation(__('Are you sure you wish to process this action? It cannot be undone.'));

            $table = $form->addRow()->addTable()->addClass('colorOddEven');

            $header = $table->addHeaderRow();
            $header->addContent(__('Activity'));
            $header->addContent(__('Days'));
            $header->addContent(__('Years'));
            $header->addContent(($dateType != 'Date')? __('Term') : __('Dates'));
            if ($paymentOn) {
                $header->addContent(__('Cost'))->append('<br/><span class="small emphasis">'.$_SESSION[$guid]['currency'].'</span>');
            }
            $header->addContent(__('Provider'));
            $header->addContent(__('Waiting'));
            $header->addContent(__('Actions'));
            $header->addCheckbox('checkall')->setClass('floatNone textCenter checkall');

            while ($activity = $resultPage->fetch()) {
                $rowClass = ($activity['active'] == 'N')? 'error' : '';

                $dataSlots = array('gibbonActivityID' => $activity['gibbonActivityID']);
                $sqlSlots = "SELECT DISTINCT nameShort FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber";
                $resultSlots = $pdo->executeQuery($dataSlots, $sqlSlots);
                $timeSlots = ($resultSlots->rowCount() > 0)? $resultSlots->fetchAll(\PDO::FETCH_COLUMN, 0) : array('<i>'.__('None').'</i>');

                if ($dateType != 'Date') {
                    $schoolTerms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
                    $termList = array_map(function ($item) use ($schoolTerms) {
                        $index = array_search($item, $schoolTerms);
                        return ($index !== false && isset($schoolTerms[$index+1]))? $schoolTerms[$index+1] : '';
                    }, explode(',', $activity['gibbonSchoolYearTermIDList']));
                    $dateRange = implode(', ', $termList);
                } else {
                    $dateRange = formatDateRange($activity['programStart'], $activity['programEnd']);
                }

                $row = $table->addRow()->addClass($rowClass);
                $row->addContent($activity['name'])->append('<br/><span class="small emphasis">'.$activity['type'].'</span>');
                $row->addContent(implode(', ', $timeSlots));
                $row->addContent(getYearGroupsFromIDList($guid, $connection2, $activity['gibbonYearGroupIDList']));
                $row->addContent($dateRange);
                if ($paymentOn) {
                    if ($activity['payment'] == 0) {
                        $row->addContent('<i>'.__('None').'</i>');
                    } else {
                        $payment = $row->addContent(number_format($activity['payment'], 2))->append('<br/>'.__($activity['paymentType']));
                        if (substr($_SESSION[$guid]['currency'], 4) != '') {
                            $payment->prepend(substr($_SESSION[$guid]['currency'], 4));
                        }
                        if ($activity['paymentFirmness'] != 'Finalised') {
                            $payment->append('<br/>'.__($activity['paymentFirmness']));
                        }
                    }
                }
                $row->addContent(($activity['provider'] == 'School')? $_SESSION[$guid]['organisationNameShort'] : __('External'));
                $row->addContent($activity['waiting']);
                $column = $row->addColumn('actions')->addClass('inline');
                    $column->addContent("<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_manage_edit.php&gibbonActivityID='.$activity['gibbonActivityID'].'&search='.$search."&gibbonSchoolYearTermID=".$gibbonSchoolYearTermID."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ");
                    $column->addContent("<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_manage_delete.php&gibbonActivityID='.$activity['gibbonActivityID'].'&search='.$search."&gibbonSchoolYearTermID=".$gibbonSchoolYearTermID."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ");
                    $column->addContent("<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_manage_enrolment.php&gibbonActivityID='.$activity['gibbonActivityID'].'&search='.$search."&gibbonSchoolYearTermID=".$gibbonSchoolYearTermID."'><img title='".__($guid, 'Enrolment')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/attendance.png'/></a> ");

                $row->addCheckbox('gibbonActivityID[]')->setValue($activity['gibbonActivityID'])->setClass('');
            }
            
            echo $form->getOutput();

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search");
            }
        }
    }
}
?>
