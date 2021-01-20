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

if (isActionAccessible($guid, $connection2, '/modules/Finance/billingSchedule_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Billing Schedule'));

    echo '<p>';
    echo __('The billing schedule allows you to layout your overall timing for issueing invoices, making it easier to specify due dates in bulk. Invoices can be issued outside of the billing schedule, should ad hoc invoices be required.');
    echo '</p>';

    $gibbonSchoolYearID = '';
    if (isset($_GET['gibbonSchoolYearID'])) {
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    }
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $gibbon->session->get('gibbonSchoolYearID')) {
        $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');
        $gibbonSchoolYearName = $gibbon->session->get('gibbonSchoolYearName');
    }

    if ($gibbonSchoolYearID != $gibbon->session->get('gibbonSchoolYearID')) {
        
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        if ($result->rowcount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    if ($gibbonSchoolYearID != '') {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/billingSchedule_manage.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Previous Year').'</a> ';
            } else {
                echo __('Previous Year').' ';
            }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/billingSchedule_manage.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Next Year').'</a> ';
        } else {
            echo __('Next Year').' ';
        }
        echo '</div>';

        echo '<h3>';
        echo __('Search');
        echo '</h3>'; 

        $form = Form::create("searchBox", $gibbon->session->get('absoluteURL') . "/index.php", "get", "noIntBorder fullWidth standardForm");

        $form->addHiddenValue("q", "/modules/Finance/billingSchedule_manage.php");

        $row = $form->addRow();
            $row->addLabel("search", __("Search For"))->description(__("Billing schedule name."));
            $row->addTextField("search")->maxLength(20)->setValue(isset($_GET['search']) ? $_GET['search'] : "");

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __("Clear Search"));

        echo $form->getOutput();

        echo '<h3>';
        echo __('View');
        echo '</h3>';
        //Set pagination variable
        $page = 1;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY invoiceIssueDate, name';
            if ($search != '') {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'search' => "%$search%");
                $sql = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND name LIKE :search ORDER BY invoiceIssueDate, name';
            }
            $sqlPage = $sql.' LIMIT '.$gibbon->session->get('pagination').' OFFSET '.(($page - 1) * $gibbon->session->get('pagination'));
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<div class='linkTop'>";
        echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module')."/billingSchedule_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/page_new.png'/></a>";
        echo '</div>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            if ($result->rowCount() > $gibbon->session->get('pagination')) {
                printPagination($guid, $result->rowCount(), $page, $gibbon->session->get('pagination'), 'top', "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __('Name');
            echo '</th>';
            echo '<th>';
            echo __('Invoice Issue Date').'<br/>';
            echo "<span style='font-style: italic; font-size: 85%'>".__('Intended Date').'</span>';
            echo '</th>';
            echo '<th>';
            echo __('Invoice Due Date').'<br/>';
            echo "<span style='font-style: italic; font-size: 85%'>".__('Final Payment Date').'</span>';
            echo '</th>';
            echo '<th>';
            echo __('Actions');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            
                $resultPage = $connection2->prepare($sqlPage);
                $resultPage->execute($data);
            while ($row = $resultPage->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

				//Color rows based on start and end date
				if ($row['active'] != 'Y') {
					$rowNum = 'error';
				} else {
					if ($row['invoiceIssueDate'] < date('Y-m-d')) {
						$rowNum = 'warning';
					}
					if ($row['invoiceDueDate'] < date('Y-m-d')) {
						$rowNum = 'error';
					}
				}

                echo "<tr class=$rowNum>";
                echo '<td>';
                echo '<b>'.$row['name'].'</b><br/>';
                echo '</td>';
                echo '<td>';
                echo dateConvertBack($guid, $row['invoiceIssueDate']);
                echo '</td>';
                echo '<td>';
                echo dateConvertBack($guid, $row['invoiceDueDate']);
                echo '</td>';
                echo '<td>';
                echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/billingSchedule_manage_edit.php&gibbonFinanceBillingScheduleID='.$row['gibbonFinanceBillingScheduleID']."&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img title='".__('Edit')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/config.png'/></a> ";
                echo "<script type='text/javascript'>";
                echo '$(document).ready(function(){';
                echo "\$(\".comment-$count-$count\").hide();";
                echo "\$(\".show_hide-$count-$count\").fadeIn(1000);";
                echo "\$(\".show_hide-$count-$count\").click(function(){";
                echo "\$(\".comment-$count-$count\").fadeToggle(1000);";
                echo '});';
                echo '});';
                echo '</script>';
                if ($row['description'] != '') {
                    echo "<a title='".__('View Description')."' class='show_hide-$count-$count' onclick='false' href='#'><img style='padding-right: 5px' src='".$gibbon->session->get('absoluteURL').'/themes/'.$gibbon->session->get('gibbonThemeName')."/img/page_down.png' alt='".__('Show Comment')."' onclick='return false;' /></a>";
                }
                echo '</td>';
                echo '</tr>';
                if ($row['description'] != '') {
                    echo "<tr class='comment-$count-$count' id='comment-$count-$count'>";
                    echo '<td colspan=6>';
                    echo $row['description'];
                    echo '</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';

            if ($result->rowCount() > $gibbon->session->get('pagination')) {
                printPagination($guid, $result->rowCount(), $page, $gibbon->session->get('pagination'), 'bottom', "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
            }
        }
    }
}
