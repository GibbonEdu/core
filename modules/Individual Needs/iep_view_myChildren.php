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
use Gibbon\Domain\User\FamilyAdultGateway;
use Gibbon\Domain\IndividualNeeds\INGateway;
use Gibbon\Domain\User\FamilyChildGateway;

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/iep_view_myChildren.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $entryCount = 0;
    $page->breadcrumbs->add(__('View Individual Education Plans'));

    echo '<p>';
    echo __('This section allows you to view individual education plans, where they exist, for children within your family.').'<br/>';
    echo '</p>';

    //Test data access field for permission

    $result = $container->get(FamilyAdultGateway::class)->selectBy(['gibbonPersonID' => $session->get('gibbonPersonID'), 'childDataAccess' => 'Y']);

    if ($result->rowCount() < 1) {
        echo $page->getBlankSlate();
    } else {
        //Get child list
        $count = 0;
        $options = array();

        while ($row = $result->fetch()) {

            $resultChild = $container->get(FamilyChildGateway::class)->selectChildByFamilyID($row['gibbonFamilyID'], $session->get('gibbonSchoolYearID'));

            while ($rowChild = $resultChild->fetch()) {
                $options[$rowChild['gibbonPersonID']]=Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
            }
        }

        $gibbonPersonID = (isset($_GET['gibbonPersonID']))? $_GET['gibbonPersonID'] : null;

        if (count($options) == 0) {
            echo $page->getBlankSlate();
        } elseif (count($options) == 1) {
            $gibbonPersonID = key($options);
        } else {
            echo '<h2>';
            echo 'Choose Student';
            echo '</h2>';

            $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
            $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', '/modules/'.$session->get('module').'/iep_view_myChildren.php');
            $form->addHiddenValue('address', $session->get('address'));

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Student'));
                $row->addSelect('gibbonPersonID')->fromArray($options)->selected($gibbonPersonID)->placeholder();

            $row = $form->addRow();
                $row->addSearchSubmit($session);

            echo $form->getOutput();
        }

        if ($gibbonPersonID != '' && count($options) > 0) {
            //Confirm access to this student

                $resultChild = $container->get(FamilyChildGateway::class)->selectChildByFamilyAdultID($gibbonPersonID, $session->get('gibbonPersonID'));

            if ($resultChild->rowCount() < 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                $rowChild = $resultChild->fetch();

                $result = $container->get(INGateway::class)->selectBy(['gibbonPersonID' => $gibbonPersonID]);

                if ($result->rowCount() != 1) {
                    echo '<h3>';
                    echo __('View');
                    echo '</h3>';

                    echo $page->getBlankSlate();
                } else {
                    echo '<h3>';
                    echo __('View');
                    echo '</h3>';

                    $row = $result->fetch(); ?>
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr>
							<td colspan=2 style='padding-top: 25px'>
								<span style='font-weight: bold; font-size: 135%'><?php echo __('Targets') ?></span><br/>
								<?php
                                echo '<p>'.$row['targets'].'</p>'; ?>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<span style='font-weight: bold; font-size: 135%'><?php echo __('Teaching Strategies') ?></span><br/>
								<?php
                                echo '<p>'.$row['strategies'].'</p>'; ?>
							</td>
						</tr>
					</table>
					<?php

                }
            }
        }
    }
}
?>