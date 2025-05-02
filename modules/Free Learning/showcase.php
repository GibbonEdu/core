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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$publicUnits = $settingGateway->getSettingByScope('Free Learning', 'publicUnits');

$canEdit = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_approval.php');

if (!(isActionAccessible($guid, $connection2, '/modules/Free Learning/showcase.php') == true or ($publicUnits == 'Y' and !$session->exists('username')))) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Beadcrumbs
    $page->breadcrumbs
         ->add(__m('Free Learning Showcase'));

    $disableExemplarWork = $settingGateway->getSettingByScope('Free Learning', 'disableExemplarWork');
    if ($disableExemplarWork == 'Y') {
        $page->addWarning(__('Exemplar work is disabled.'));
    }
    else {

        $unitStudentGateway = $container->get(UnitStudentGateway::class);

        // QUERY
        $criteria = $unitStudentGateway->newQueryCriteria(true)
            ->fromPOST();

        $units = $unitStudentGateway->selectShowcase();

        $table = DataTable::createPaginated('units', $criteria);

        $table->addColumn('unit', __('Unit'))
            ->format(function ($values) use ($session) {
                $return = "<span class='text-lg font-bold'>".$values['name']."</span><br/>";

                if ($values['exemplarWorkThumb'] != '') {
                    $return .= "<img style='width: 150px; height: 150px; margin: 5px 0' class='user' src='".$values['exemplarWorkThumb']."'/><br/>";
                    if ($values['exemplarWorkLicense'] != '') {
                        $return .= "<span style='font-size: 85%; font-style: italic'>".$values['exemplarWorkLicense'].'</span>';
                    }
                } else {
                    if ($values['logo'] != '') {
                        $return .= "<img style='height: 150px; width: 150px; opacity: 1.0; margin: 5px 0' class='user' src='".$values['logo']."'/><br/>";
                    }
                    else {
                        $return .= "<img style='height: 150px; width: 150px; opacity: 1.0; margin: 5px 0' class='user' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_240_square.jpg'/><br/>";
                    }
                }

                return $return;
            });

        $table->addColumn('students', __('Students'))
            ->format(function ($values) {
                $return = preg_replace("/,([^,]+)$/", " & $1", $values['students'])."<br/>";

                $return .= Format::small(__m('Shared on')." ".Format::date($values['timestampCompleteApproved']));

                return $return;
            });

        $table->addColumn('work', __('Work'))
            ->format(function ($values) use ($session) {
                $return = '';

                $return .= '<p class="mt-4">';
                if ($values['exemplarWorkEmbed'] =='') { //It's not an embed
                    $extension = strrchr($values['evidenceLocation'], '.');
                    if (strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) { //Its an image
                        if ($values['evidenceType'] == 'File') { //It's a file
                            $return .= "<a target='_blank' href='".$session->get('absoluteURL').'/'.$values['evidenceLocation']."'><img class='user' style='max-width: 550px' src='".$session->get('absoluteURL').'/'.$values['evidenceLocation']."'/></a>";
                        } else { //It's a link
                            $return .= "<a target='_blank' href='".$session->get('absoluteURL').'/'.$values['evidenceLocation']."'><img class='user' style='max-width: 550px' src='".$values['evidenceLocation']."'/></a>";
                        }
                    } else { //Not an image
                        if ($values['evidenceType'] == 'File') { //It's a file
                            $return .= "<a class='button' target='_blank' href='".$session->get('absoluteURL').'/'.$values['evidenceLocation']."'>".__m('Click to View Work').'</a>';
                        } else { //It's a link
                            $return .= "<a class='button' target='_blank' href='".$values['evidenceLocation']."'>".__m('Click to View Work').'</a>';
                        }
                    }
                } else {
                    if (filter_var($values['exemplarWorkEmbed'], FILTER_VALIDATE_URL)) {
                        $return .= "<a class='button' target='_blank' href='".$values['exemplarWorkEmbed']."'>".__m('Click to View Work').'</a>';
                    } else {
                        $return .= $values['exemplarWorkEmbed'];
                    }
                }
                $return .= '<p>';

                $return .= "<br/>";

                $return .= Format::bold(__m('Student Comment'))."<br/><i>".$values['commentStudent']."</i><br/><br/>";
                $return .= Format::bold(__m('Teacher Comment'))."<br/><i>".$values['commentApproval']."</i>";

                return $return;
            });

            if ($canEdit) {
                $actions = $table->addActionColumn()
                    ->addParam('freeLearningUnitID')
                    ->addParam('freeLearningUnitStudentID')
                    ->addParam('sidebar', 'true')
                    ->format(function ($resource, $actions) {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Free Learning/units_browse_details_approval.php');
                    });
            }

        echo $table->render($units);
    }
}
