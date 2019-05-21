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
use Gibbon\Services\Format;
use Gibbon\Domain\Badges\BadgeGateway;

//Module includes
include './modules/'.$gibbon->session->get('module').'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_grant.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $page->breadcrumbs->add(__('Grant Badges'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = '';
    if (isset($_GET['gibbonSchoolYearID'])) {
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    }
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $gibbon->session->get('gibbonSchoolYearID')) {
        $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');
        $gibbonSchoolYearName = $gibbon->session->get('gibbonSchoolYearName');
    }

    if ($gibbonSchoolYearID != $gibbon->session->get('gibbonSchoolYearID')) {
        try {
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
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
            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/badges_grant.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Previous Year').'</a> ';
        } else {
            echo __('Previous Year').' ';
        }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/badges_grant.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Next Year').'</a> ';
        } else {
            echo __('Next Year').' ';
        }
        echo '</div>';

        $gibbonPersonID2 = null;
        if (isset($_GET['gibbonPersonID2'])) {
            $gibbonPersonID2 = $_GET['gibbonPersonID2'];
        }
        $badgesBadgeID2 = null;
        if (isset($_GET['badgesBadgeID2'])) {
            $badgesBadgeID2 = $_GET['badgesBadgeID2'];
        }
        $gibbonYearGroupID = null;
        if (isset($_GET['gibbonYearGroupID'])) {
            $gibbonYearGroupID = $_GET['gibbonYearGroupID'];
        }
        $type = null;
        if (isset($_GET['type'])) {
            $type = $_GET['type'];
        }

        $form = Form::create('grantbadges',$gibbon->session->get('absoluteURL').'/index.php?q=/modules/Badges/badges_grant.php','GET');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addRow()->addHeading(__('Filter'));
        $form->addRow();

        $row = $form->addRow();
        $row->addLabel('gibbonPersonIDMulti',__('User'));
        $row->addSelectStudent('gibbonPersonIDMulti', $gibbon->session->get('gibbonSchoolYearID'))->selectMultiple();

        $sql = "SELECT badgesBadgeID as value, name, category FROM badgesBadge WHERE active='Y' ORDER BY category, name";
        $row = $form->addRow();
        $row->addLabel('badgesBadgeID',__('Badges'));
        $row->addSelect('badgesBadgeID')->fromQuery($pdo, $sql, [], 'category')->placeholder();

        $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session);

        $form->addHiddenValue('q',$_GET['q']);
        $form->addRow();
        $form->addRow()->addHeading(__('Badges'));
        echo $form->getOutput();
        ?>
        
        
        <?php
        

        echo '<h3>';
        echo __('Badges');
        echo '</h3>';
        //Set pagination variable
        $page = 1;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        //Get gibbonHookID for link to Student Profile
        $gibbonHookID = null;
        try {
            $dataHook = array();
            $sqlHook = "SELECT gibbonHookID FROM gibbonHook WHERE name='Badges' AND type='Student Profile' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name='Badges')";
            $resultHook = $connection2->prepare($sqlHook);
            $resultHook->execute($dataHook);
        } catch (PDOException $e) {

        }
        if ($resultHook->rowCount() == 1) {
            $rowHook = $resultHook->fetch();
            $gibbonHookID = $rowHook['gibbonHookID'];
        }

        $badgesGateway = $container->get(BadgeGateway::class);
        $criteria = $badgesGateway->newQueryCriteria()
            ->filterBy('studentIdMulti',$_GET['gibbonPersonIDMulti'] ?? '')
            ->filterBy('badgeId',$_GET['badgesBadgeID'] ?? '')
            ->fromPOST();

        $badges = $badgesGateway->queryBadges($criteria,$gibbonSchoolYearID);
        $table = DataTable::createPaginated('badges',$criteria);

        //Setup params
        $table
            ->addHeaderAction('add',__('Add'))
            ->setURL('/modules/Badges/badges_grant_add.php')
            ->addParam('gibbonPersonID2',$gibbonPersonID2)
            ->addParam('badgesBadgeID2',$badgesBadgeID2)
            ->addParam('gibbonSchoolYearID',$gibbonSchoolYearID);

        //Setup columns
        $table
            ->addColumn('badge',__('Badge'))
            ->format(function ($row){
                return (Format::userPhoto($row['logo'])) . "<br/>" . $row['name']; //TODO: Set image to 150x150. Current available sizes are 75 and 240.
            });
        $table
            ->addColumn('student',__('Student'))
            ->format(function($row) use ($gibbonHookID,$gibbon)
            {
                $link = Format::link($gibbon->session->get('absoluteURL').'/index.php?q=modules/Students/student_view_details.php',Format::name(null,$row['preferredName'],$row['surname'],'Student',true,false));
                /* TODO
                Need to add these params:
                ,[
                    "gibbonPersonID" => $row['gibbonPersonID'],
                    "hook" => "Badges",
                    "action" => "View Badges_all",
                    "gibbonHookID" => $gibbonHookID,
                    "search" => "",
                    "allStudents" => "",
                    "sort" => "surname, preferredName"
                ]);*/
                return $link;
            });

        $table->addColumn('date',__('Date'))->format(Format::using('date','date'));

        //Setup actions
        $actions = $table->addActionColumn();
        $actions->format(function ($row,$actions) use ($gibbon){
            $actions->addAction('delete',__('Delete'))
                ->setURL('/modules/Badges/badges_grant_delete.php')
                ->addParam('badgesBadgeStudentID',$row['badgesBadgeStudentID']);

            $actions->addAction('view',__('View'))
                ->setIcon('page_down')
                ->onClick("#");
        });
            
            //&gibbonPersonID2='.$gibbonPersonID2.'&badgesBadgeID2='.$badgesBadgeID2.'&gibbonSchoolYearID='.$gibbonSchoolYearID.'&width=650&height=135');
        echo $table->render($badges);

        //TODO: Italicised text in the badge logo footer
        //TODO: Add logos using the class/style below
        /*
            if ($row['logo'] != '') {
                echo "<img class='user' style='margin-bottom: 10px; max-width: 150px' src='".$gibbon->session->get('absoluteURL').'/'.$row['logo']."'/>";
            } else {
                echo "<img class='user' style='margin-bottom: 10px; max-width: 150px' src='".$gibbon->session->get('absoluteURL').'/themes/'.$gibbon->session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/>";
            }
        */
        //TODO: Add expander column containing the comment                
    }
}
?>
