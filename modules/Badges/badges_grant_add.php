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

//Module includes
include './modules/'.$gibbon->session->get('module').'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_grant_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Grant Badges'), 'badges_grant.php')
        ->add(__('Add'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    echo "<div class='linkTop'>";

    //Get the gibbon persion and badge IDs
    //Set '' for safety
    $gibbonPersonID2 = '';
    $badgesBadgeID2 = '';
    if (isset($_GET['gibbonPersonID2']) ||  isset($_GET['badgesBadgeID2'])) {
        if($_GET['gibbonPersonID2'] != '' && $_GET['badgesBadgeID2'] != '')
        {
            //Only assign variable when it exists
            $gibbonPersonID2 = $_GET['gibbonPersonID2'];
            $badgesBadgeID2 = $_GET['badgesBadgeID2'];
            //Add a "Back to Results" link.
            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/Badges/badges_grant.php&gibbonPersonID2='.$_GET['gibbonPersonID2'].'&badgesBadgeID2='.$_GET['badgesBadgeID2']."'>".__('Back to Search Results').'</a>';
        }
    }
    echo '</div>';

    $form = Form::create('grantBadges', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/badges_grant_addProcess.php?gibbonPersonID2='.$gibbonPersonID2.'&badgesBadgeID2='.$badgesBadgeID2."&gibbonSchoolYearID=$gibbonSchoolYearID");

    $form->setFactory(DatabaseFormFactory::create($pdo));
            
    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('gibbonSchoolYearID', $gibbon->session->get('gibbonSchoolYearID'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDMulti', __('Students'));
        $row->addSelectUsers('gibbonPersonIDMulti', $gibbon->session->get('gibbonSchoolYearID'), ['includeStudents' => true])->selectMultiple()->isRequired();

    $sql = "SELECT badgesBadgeID as value, name, category FROM badgesBadge WHERE active='Y' ORDER BY category, name";
    $row = $form->addRow();
        $row->addLabel('badgesBadgeID', __('Badge'));
        $row->addSelect('badgesBadgeID')->fromQuery($pdo, $sql, [], 'category')->isRequired()->placeholder();

    $row = $form->addRow();
        $row->addLabel('date', __('Date'));
        $row->addDate('date')->setValue(date($gibbon->session->get('i18n')['dateFormatPHP']))->isRequired();

    $col = $form->addRow()->addColumn();
        $col->addLabel('comment', __('Comment'));
        $col->addTextArea('comment')->setRows(8)->setClass('w-full');

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();
}
