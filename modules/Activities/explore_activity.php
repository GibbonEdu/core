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

use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\ActivityPhotoGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;
use Gibbon\Domain\Activities\ActivityStudentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/explore_activity.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonActivityCategoryID = $_REQUEST['gibbonActivityCategoryID'] ?? '';
    $gibbonActivityID = $_REQUEST['gibbonActivityID'] ?? '';

    $page->breadcrumbs
        ->add(__('Explore Activities'), 'explore.php')
        ->add(__('Category'), 'explore_category.php', ['gibbonActivityCategoryID' => $gibbonActivityCategoryID, 'sidebar' => 'false'])
        ->add(__('Activity'));

    $page->return->addReturns([
        'error4' => __m('Sign up is currently not available for this activity.'),
        'error5' => __m('There was an error verifying your activity choices. Please try again.'),
    ]);

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $canSignUp = isActionAccessible($guid, $connection2, '/modules/Activities/explore_activity_signUp.php', 'Explore Activities_studentRegister');
    $canViewInactive = isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php');

    // Check records exist and are available

    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);
    $enrolmentGateway = $container->get(ActivityStudentGateway::class);
    $activityPhotoGateway = $container->get(ActivityPhotoGateway::class);
    $staffGateway = $container->get(ActivityStaffGateway::class);

    if (empty($gibbonActivityID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $activity = $activityGateway->getActivityDetailsByID($gibbonActivityID);
    $category = $categoryGateway->getCategoryDetailsByID($activity['gibbonActivityCategoryID'] ?? '');

    if (empty($activity) || empty($category)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if (($activity['active'] != 'Y' || $category['active'] != 'Y') && !$canViewInactive) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if ($category['viewable'] != 'Y'  && !$canViewInactive) {
        $page->addMessage(__m('This activity is not viewable at this time. Please return to the categories page to explore a different activity.'));
        return;
    }

    // Get photos & blocks
    $activity['photos'] = $activityPhotoGateway->selectPhotosByActivity($gibbonActivityID)->fetchAll();
    // $activity['blocks'] = $unitBlockGateway->selectBlocksByUnit($activity['deepLearningUnitID'])->fetchAll();

    // Check sign-up access
    $now = (new DateTime('now'))->format('U');
    $signUpIsOpen = false;
    $isPastEvent = false;

    if (!empty($category['accessOpenDate']) && !empty($category['accessCloseDate'])) {
        $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $category['accessOpenDate'])->format('U');
        $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $category['accessCloseDate'])->format('U');

        $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
    }

    if (!empty($category['endDate'])) {
        $endDate = DateTime::createFromFormat('Y-m-d', $category['endDate'])->format('U');
        $isPastEvent = $now >= $endDate;
    }

    $signUpCategory = $categoryGateway->getCategorySignUpAccess($activity['gibbonActivityCategoryID'], $session->get('gibbonPersonID'));
    $signUpActivity = $activityGateway->getActivitySignUpAccess($gibbonActivityID, $session->get('gibbonPersonID'));

    // echo '<pre>';
    // print_r("canSignUp = ".$canSignUp);
    // print_r("signUpIsOpen = ".$signUpIsOpen);
    // print_r("signUpCategory = ".$signUpCategory);
    // print_r("signUpActivity = ".$signUpActivity);
    // print_r("signUpAccess = ".(!empty($signUpCategory) && !empty($signUpActivity)) );
    // echo '</pre>';

    // $enrolment = $enrolmentGateway->getActivityDetailsByEnrolment($activity['gibbonActivityCategoryID'], $session->get('gibbonPersonID'), $gibbonActivityID);

    $canEdit = isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php');
    $isStaff = $staffGateway->getActivityAccessByStaff($gibbonActivityID, $session->get('gibbonPersonID'));

    $page->writeFromTemplate('activity.twig.html', [
        'category'      => $category,
        'activity'      => $activity,

        'nextActivity' => $activityGateway->getNextActivityByID($gibbonActivityCategoryID, $gibbonActivityID),
        'prevActivity' => $activityGateway->getPreviousActivityByID($gibbonActivityCategoryID, $gibbonActivityID),

        'canViewInactive' => $canViewInactive,
        'canSignUp'  => $canSignUp,
        'signUpIsOpen' => $signUpIsOpen,
        'signUpAccess' => $signUpCategory && $signUpActivity,

        'isPastEvent' => $isPastEvent,
        'isEnrolled' => !empty($enrolment) && $enrolment['gibbonActivityID'] == $gibbonActivityID,
        // 'enrolment' => $enrolment,

        'canEdit' => $canEdit,
        'isStaff' => !empty($isStaff),
    ]);
}
