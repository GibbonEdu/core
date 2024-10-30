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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Tables\Action;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\DataUpdater\DataUpdaterGateway;
use Gibbon\Forms\Layout\Element;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_updates.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('My Data Updates'));

    $gibbonPersonID = $session->get('gibbonPersonID');
    $dataUpdaterGateway = $container->get(DataUpdaterGateway::class);

    // Get the data updater settings for required updates
    $settingGateway = $container->get(SettingGateway::class);
    $requiredUpdates = $settingGateway->getSettingByScope('Data Updater', 'requiredUpdates');
    if ($requiredUpdates == 'Y') {
        $requiredUpdatesByType = $settingGateway->getSettingByScope('Data Updater', 'requiredUpdatesByType');
        $requiredUpdatesByType = explode(',', $requiredUpdatesByType);
        $cutoffDate = $settingGateway->getSettingByScope('Data Updater', 'cutoffDate');
    } else {
        $requiredUpdatesByType = [];
        $cutoffDate = null;
    }

    // Get the active data types based on this user's permissions
    $updatableDataTypes = [];
    if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family.php')) $updatableDataTypes[] = 'Family';
    if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_personal.php')) $updatableDataTypes[] = 'Personal';
    if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical.php')) $updatableDataTypes[] = 'Medical';
    if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance.php')) $updatableDataTypes[] = 'Finance';
    if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_staff.php')) $updatableDataTypes[] = 'Staff';

    echo '<p>';
    echo __('This page shows all the data updates that are available to you. If an update is required it will be highlighted in red.');
    echo '</p>';

    if ($requiredUpdates == 'Y') {
        $updatesRequiredCount = $dataUpdaterGateway->countAllRequiredUpdatesByPerson($gibbonPersonID);

        if ($updatesRequiredCount > 0) {
            echo '<div class="warning">';
            if (isset($_GET['redirect'])) {
                echo '<b>'.__("You have been redirected upon login because there are pending data updates.").'</b> ';
            }
            echo __("Please take a moment to view and submit the required updates. Even if you don't change your data, submitting the form will indicate you've reviewed the data and have confirmed it is correct.");
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo __('Your data is up to date. Please note any recent changes will not appear in the system until they have been approved.');
            echo '</div>';
        }
    }

    // Get the data updates per person and indicate required updates
    $updatablePeople = $dataUpdaterGateway->selectUpdatableUsersByPerson($gibbonPersonID)->toDataSet();
    $updatablePeople->transform(function (&$person) use ($dataUpdaterGateway, $gibbonPersonID, &$requiredUpdatesByType, &$cutoffDate) {
        $person['updates'] = $dataUpdaterGateway->selectDataUpdatesByPerson($person['gibbonPersonID'], $gibbonPersonID)->fetchGrouped();

        foreach ($person['updates'] as $type => $dataUpdates) {
            foreach ($dataUpdates as $index => $dataUpdate) {
                if (!in_array($type, $requiredUpdatesByType) || empty($cutoffDate)) {
                    $person['updates'][$type][$index]['required'] = 'N/A';
                } elseif (empty($dataUpdate['lastUpdated']) || $dataUpdate['lastUpdated'] < $cutoffDate) {
                    $person['updates'][$type][$index]['required'] = 'Y';
                    $person['updatesRequired'][$type] = true;
                } else {
                    $person['updates'][$type][$index]['required'] = 'N';
                }
            }
        }
    });

    // DATA TABLE
    $table = DataTable::create('dataUpdates');
    $table->setTitle(__('Data Updates'));

    $table->addColumn('image_240', __('Photo'))
        ->context('secondary')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'sm']));

    $table->addColumn('fullName', __('Name'))
        ->context('primary')
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Student', true]));

    foreach ($updatableDataTypes as $type) {
        $table->addColumn($type, __($type.' Data'))
            ->context('primary')
            ->format(function ($person) use ($type) {
                $output = '';
                if (empty($person['updates'][$type])) {
                    return Format::small(__('N/A'));
                }

                foreach ($person['updates'][$type] as $dataUpdate) {
                    $lastUpdate = !empty($dataUpdate['lastUpdated'])
                        ? __('Last Updated').': '.date('F j, Y', strtotime($dataUpdate['lastUpdated']))
                        : '';

                    // Create an action icon for this type of update
                    $action = (new Action('edit', __('Edit')))
                        ->setURL('/modules/Data Updater/data_'.strtolower($type).'.php')
                        ->addParam($dataUpdate['idType'], $dataUpdate['id'])
                        ->setClass('block underline');

                    // Add the family name if there's more than one family
                    if (!empty($dataUpdate['name']) && count($person['updates'][$type]) > 1) {
                        $action->addEmbeddedElement(new Element('<br/>'.$dataUpdate['name']));
                    }

                    // Change the label/icon based on required updates
                    if ($dataUpdate['required'] == 'N') {
                        $action->setLabel(__('Up to date').'<br/>'.$lastUpdate)
                               ->setIcon('iconTick');
                    } elseif ($dataUpdate['required'] == 'Y') {
                        $action->setLabel(__('Update Required').'<br/>'.$lastUpdate)
                               ->setIcon('copyforward')
                               ->addEmbeddedElement(new Element('<br/>'.__('Update Required')));
                    }

                    $output .= $action->getOutput();
                }

                return $output;
            })
            ->modifyCells(function ($person, $cell) use ($type) {
                if (!empty($person['updatesRequired'][$type])) {
                    $cell->addClass('error');
                }
                return $cell;
            });
    }

    echo $table->render($updatablePeople);
}
