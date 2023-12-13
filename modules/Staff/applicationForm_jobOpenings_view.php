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

//Module includes from User Admin (for custom fields)
use Gibbon\Domain\System\SettingGateway;

include './modules/User Admin/moduleFunctions.php';

$proceed = false;
$public = false;
if (!$session->has('username')) {
    $public = true;
    //Get public access
    $access = $container->get(SettingGateway::class)->getSettingByScope('Staff Application Form', 'staffApplicationFormPublicApplications');
    if ($access == 'Y') {
        $proceed = true;
    }
} else {
    if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm.php') != false) {
        $proceed = true;
    }
}

if ($proceed == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('{organisationName} Application Form', [
        'organisationName' => $session->get('organisationNameShort'),
    ]));

    //Check for job openings
    try {
        $data = array('dateOpen' => date('Y-m-d'));
        $sql = "SELECT * FROM gibbonStaffJobOpening WHERE active='Y' AND dateOpen<=:dateOpen ORDER BY jobTitle";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no job openings at this time: please try again later.');
        echo '</div>';
    } else {
        $jobOpenings = $result->fetchAll();

        $page->navigator->addHeaderAction('submit', __('Submit Application Form'))
            ->setURL('/modules/Staff/applicationForm.php')
            ->setIcon('plus')
            ->displayLabel();
    
        echo $page->fetchFromTemplate('jobOpenings.twig.html', [
            'jobOpenings' => $jobOpenings,
        ]);

    }
}
