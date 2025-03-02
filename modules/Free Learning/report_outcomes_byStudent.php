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
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\FreeLearning\Domain\UnitOutcomeGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_outcomes_byStudent.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__m('Outcomes by Student'));

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? null;

    // FORM
    $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__m('Choose Student'));

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder w-full');
    $form->addHiddenValue('q', '/modules/Free Learning/report_outcomes_byStudent.php');

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'), array("allStudents" => false, "byName" => true, "byForm" => true))->required()->placeholder()->selected($gibbonPersonID);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    if ($gibbonPersonID != '') {

        $unitOutcomeGateway = $container->get(UnitOutcomeGateway::class);

        // OUTCOMES TABLE
        // QUERY
        $criteria = $unitOutcomeGateway->newQueryCriteria(true)
            ->sortBy(['scope', 'category', 'name'])
            ->pageSize(50)
            ->fromPOST();

        $outcomes = $unitOutcomeGateway->queryOutcomesByStudent($criteria, $gibbonPersonID);

        // TABLE
        $table = DataTable::createPaginated('outcomes', $criteria);
        $table->setTitle(__m('Outcome Completion'));

        $table->addColumn('scope', __('Scope'))
            ->format(function ($row) {
                return ($row['scope'] == "School") ? $row['scope'] : $row['scope']."<br/>".Format::small(__($row['department']));
            });

        $table->addColumn('category', __('Category'));

        $table->addColumn('name', __('Name'));

        $table->addColumn('status', __('Status'))
            ->format(function ($values) use ($session) {
                if (empty($values['status'])) {
                    return "<img title='".__m('Outcome not met')."' src='./themes/".$session->get('gibbonThemeName')."/img/iconCross.png'/> ";
                } else {
                    return "<img title='".__m('Outcome met in units:').' '.htmlPrep($values['units'])."' src='./themes/".$session->get('gibbonThemeName')."/img/iconTick.png'/> x".$values['status'];
                }
            });


        $output = $table->render($outcomes);

        // TODO: refactor this stuff!
        $learningAreaArray = getLearningAreaArray($connection2);
        $authors = getAuthorsArray($connection2);
        $blocks = getBlocksArray($connection2);

        // RECOMMENDED UNITS TABLE
        $outcomesNotMet = [];
        foreach ($outcomes AS $outcome) {
            if (empty($outcome['status'])) {
                $outcomesNotMet[] = $outcome['gibbonOutcomeID'];
            }
        }

        // QUERY
        $criteria = $unitOutcomeGateway->newQueryCriteria(true)
            ->pageSize(3)
            ->fromPOST();

        $units = $unitOutcomeGateway->queryRecommendedUnitsByStudent($criteria, $gibbonPersonID, $session->get('gibbonSchoolYearID'), $outcomesNotMet);

        // TABLE
        $table = DataTable::create('units');
        $table->setTitle(__m('Recommended Units'));

        $table->addColumn('name', __('Unit'))
            ->format(function ($values) use ($session, $learningAreaArray) {
                $return = "<div class='text-center'>";
                if ($values['logo'] == null) {
                   $return .= "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_125.jpg'/><br/>";
                } else {
                   $return .= "<img style='margin-bottom: 10px; height: 125px; width: 125px' class='user' src='".$values['logo']."'/><br/>";
                }
                $return .= Format::bold($values['name']).'<br/>';

                if ($values['gibbonDepartmentIDList'] != '') {
                    $departmentList = '';
                    $departments = explode(',', $values['gibbonDepartmentIDList']);
                    foreach ($departments as $department) {
                        $departmentList .= $learningAreaArray[$department].'<br/>';
                    }
                    $return .= Format::small($departmentList);
                }

                return $return;
            })->notSortable();

        $table->addColumn('authors', __('Authors'))
            ->format(function ($values) use ($authors) {
                foreach ($authors as $author) {
                    if ($author[0] == $values['freeLearningUnitID']) {
                        if ($author[3] == '') {
                            return $author[1].'<br/>';
                        } else {
                            return "<a target='_blank' href='".$author[3]."'>".$author[1].'</a><br/>';
                        }
                    }
                }
            })->notSortable();

        $table->addColumn('difficulty', __m('Difficulty'))->notSortable();

        $table->addColumn('timing', __m('Timing'))
            ->format(function ($values) use ($blocks) {
                $timing = null;
                if ($blocks != false) {
                    foreach ($blocks as $block) {
                        if ($block[0] == $values['freeLearningUnitID']) {
                            if (is_numeric($block[2])) {
                                $timing += $block[2];
                            }
                        }
                    }
                }
                if (is_null($timing)) {
                    return '<i>'.__('N/A').'</i>';
                } else {
                    return $timing;
                }
            })->notSortable();

        $table->addColumn('groupings', __m('Groupings'))
            ->format(function ($values) use ($session) {
                if ($values['grouping'] != '') {
                   $groupings = explode(',', $values['grouping']);
                   foreach ($groupings as $grouping) {
                       return ucwords($grouping).'<br/>';
                   }
                }
            })->notSortable();

        $table->addColumn('prerequisites', __m('Prerequisites'))
            ->format(function ($values) use ($session, $connection2, $gibbonPersonID) {
                $prerequisitesActive = prerequisitesRemoveInactive($connection2, $values['freeLearningUnitIDPrerequisiteList']);
                if ($prerequisitesActive != false) {
                    $prerequisites = explode(',', $prerequisitesActive);
                    $units = getUnitsArray($connection2);
                    foreach ($prerequisites as $prerequisite) {
                        echo $units[$prerequisite][0].'<br/>';
                    }
                } else {
                    echo '<i>'.__m('None').'<br/></i>';
                }
                if ($prerequisitesActive != false) {
                    $prerequisitesMet = prerequisitesMet($connection2, $gibbonPersonID, $prerequisitesActive);
                    if ($prerequisitesMet) {
                        echo "<span style='font-weight: bold; color: #00cc00'>".__m('OK!').'</span>';
                    } else {
                        echo "<span style='font-weight: bold; color: #cc0000'>".__m('Not Met').'</span>';
                    }
                }
            })->notSortable();

        $actions = $table->addActionColumn()
            ->addParam('freeLearningUnitID')
            ->addParam('sidebar', 'true')
            ->addParam('gibbonDepartmentID', '')
            ->addParam('difficulty', '')
            ->addParam('name', '')
            ->format(function ($resource, $actions) {
                $actions->addAction('view', __('View'))
                    ->setURL('/modules/Free Learning/units_browse_details.php');
            });

        echo $table->render($units).$output;
    }
}
?>
