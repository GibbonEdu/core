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

use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\EnrolmentGateway;
use Gibbon\Domain\Activities\UnitPhotoGateway;
use Gibbon\Domain\Activities\UnitBlockGateway;
use Gibbon\Domain\Activities\UnitGateway;
use Gibbon\Domain\Activities\StaffGateway;

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

    $canSignUp = isActionAccessible($guid, $connection2, '/modules/Activities/view.php', 'Deep Learning Events_signUp');
    $canViewInactive = isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php');

    // Check records exist and are available
    $unitGateway = $container->get(UnitGateway::class);
    $categoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);
    $enrolmentGateway = $container->get(EnrolmentGateway::class);
    $unitPhotoGateway = $container->get(UnitPhotoGateway::class);
    $unitBlockGateway = $container->get(UnitBlockGateway::class);
    $staffGateway = $container->get(StaffGateway::class);

    if (empty($gibbonActivityID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $experience = $activityGateway->getExperienceDetailsByID($gibbonActivityID);
    $event = $categoryGateway->getCategoryDetailsByID($experience['gibbonActivityCategoryID'] ?? '');

    if (empty($experience) || empty($event)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if (($experience['active'] != 'Y' || $event['active'] != 'Y') && !$canViewInactive) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if ($event['viewable'] != 'Y'  && !$canViewInactive) {
        $page->addMessage(__m('This event is not viewable at this time. Please return to the Events page to explore a different event.'));
        return;
    }

    // Get photos & blocks
    $experience['photos'] = $unitPhotoGateway->selectPhotosByExperience($gibbonActivityID)->fetchAll();
    $experience['blocks'] = $unitBlockGateway->selectBlocksByUnit($experience['deepLearningUnitID'])->fetchAll();

    // Check sign-up access
    $now = (new DateTime('now'))->format('U');
    $signUpIsOpen = false;
    $isPastEvent = false;

    if (!empty($event['accessOpenDate']) && !empty($event['accessCloseDate'])) {
        $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessOpenDate'])->format('U');
        $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessCloseDate'])->format('U');

        $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
    }

    if (!empty($event['endDate'])) {
        $endDate = DateTime::createFromFormat('Y-m-d', $event['endDate'])->format('U');
        $isPastEvent = $now >= $endDate;
    }

    $signUpEvent = $categoryGateway->getEventSignUpAccess($experience['gibbonActivityCategoryID'], $session->get('gibbonPersonID'));
    $signUpExperience = $activityGateway->getExperienceSignUpAccess($gibbonActivityID, $session->get('gibbonPersonID'));

    $enrolment = $enrolmentGateway->getExperienceDetailsByEnrolment($experience['gibbonActivityCategoryID'], $session->get('gibbonPersonID'), $gibbonActivityID);

    $canEditAll = getHighestGroupedAction($guid, '/modules/Activities/unit_manage_edit.php', $connection2) == 'Manage Units_all';
    $canEditUnit = $unitGateway->getUnitEditAccess($experience['deepLearningUnitID'], $session->get('gibbonPersonID')) ?? 'N';
    $isStaff = $staffGateway->getStaffExperienceAccess($gibbonActivityID, $session->get('gibbonPersonID'));

    $page->writeFromTemplate('experience.twig.html', [
        'event'      => $event,
        'experience' => $experience,

        'nextExperience' => $activityGateway->getNextExperienceByID($gibbonActivityCategoryID, $gibbonActivityID),
        'prevExperience' => $activityGateway->getPreviousExperienceByID($gibbonActivityCategoryID, $gibbonActivityID),

        'canViewInactive' => $canViewInactive,
        'canSignUp'  => $canSignUp,
        'signUpIsOpen' => $signUpIsOpen,
        'signUpAccess' => $signUpEvent && $signUpExperience,

        'isPastEvent' => $isPastEvent,
        'isEnrolled' => !empty($enrolment) && $enrolment['gibbonActivityID'] == $gibbonActivityID,
        'enrolment' => $enrolment,

        'canEditUnit' => $canEditAll || (!empty($canEditUnit) && $canEditUnit == 'Y'),
        'isStaff' => !empty($isStaff),
    ]);
}
