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

namespace Gibbon\Module\Planner\Forms;

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;

/**
 * PlannerFormFactory
 *
 * @version v16
 * @since   v16
 */
class PlannerFormFactory extends DatabaseFormFactory
{
    /**
     * Create and return an instance of DatabaseFormFactory.
     * @return  object DatabaseFormFactory
     */
    public static function create(Connection $pdo = null)
    {
        return new PlannerFormFactory($pdo);
    }

    /**
     * Creates a fully-configured CustomBlocks input for Smart Blocks in the lesson planner.
     *
     * @param string $name
     * @param Session $session
     * @param string $guid
     * @return OutputableInterface
     */
    public function createPlannerSmartBlocks($name, $session, $canAdd) : OutputableInterface
    {
        $blockTemplate = $this->createSmartBlockTemplate();

        // Create and initialize the Custom Blocks
        $customBlocks = $this->createCustomBlocks($name, $session, true, $canAdd, $canAdd)
            ->fromTemplate($blockTemplate)
            ->settings([
                'inputNameStrategy' => 'string',
                'addOnEvent'        => 'click',
                'sortable'          => true,
                'orderName'         => 'order',
                'uniqueID'          => 'gibbonUnitBlockID',
            ])
            ->placeholder(__('Smart Blocks listed here...'))
            ->addBlockButton('showHide', __('Show/Hide'), 'plus.png');

        return $customBlocks;
    }

    /**
     * Creates a template for displaying Outcomes in a CustomBlocks input.
     *
     * @param string $guid
     * @return OutputableInterface
     */
    public function createSmartBlockTemplate() : OutputableInterface
    {
        global $container;

        $blockTemplate = $this->createTable()->setClass('blank w-full');
            $row = $blockTemplate->addRow();
            $row->addTextField('title')
                ->setClass('w-3/4 title focus:bg-white')
                ->placeholder(__('Title'));

            $row = $blockTemplate->addRow()->addClass('w-3/4 flex justify-between mt-1');
                $row->addTextField('type')->placeholder(__('type (e.g. discussion, outcome)'))
                    ->setClass('flex-1 focus:bg-white mr-1');
                $row->addTextField('length')->placeholder(__('length (min)'))
                    ->setClass('w-48 focus:bg-white')->prepend('');

            $smartBlockTemplate = $container->get(SettingGateway::class)->getSettingByScope('Planner', 'smartBlockTemplate');
            $col = $blockTemplate->addRow()->addClass('showHide w-full')->addColumn();
                $col->addLabel('contentsLabel', __('Block Contents'));
                $col->addTextArea('contents')->addData('tinymce')->addData('media', '1')->setRows(20)->setValue($smartBlockTemplate);

            $col = $blockTemplate->addRow()->addClass('showHide w-full')->addColumn();
                $col->addLabel('teachersNotesLabel', __('Teacher\'s Notes'));
                $col->addTextArea('teachersNotes')->addData('tinymce')->addData('media', '1')->setRows(5);

        return $blockTemplate;
    }

    /**
     * Creates a fully-configured CustomBlocks input for Outcomes in the lesson planner.
     *
     * @param string $name
     * @param Session $session
     * @param string $gibbonYearGroupIDList
     * @param string $gibbonDepartmentID
     * @param bool $allowOutcomeEditing
     * @return OutputableInterface
     */
    public function createPlannerOutcomeBlocks($name, $session, $gibbonYearGroupIDList = '', $gibbonDepartmentID = '', $allowOutcomeEditing = false) : OutputableInterface
    {
        $outcomeSelector = $this->createSelectOutcome('addOutcome', $gibbonYearGroupIDList, $gibbonDepartmentID);
        $blockTemplate = $this->createOutcomeBlockTemplate($allowOutcomeEditing);

        // Create and initialize the Custom Blocks
        $customBlocks = $this->createCustomBlocks($name, $session, true, false, false)
            ->fromTemplate($blockTemplate)
            ->settings([
                'inputNameStrategy' => 'string',
                'addOnEvent'        => 'change',
                'preventDuplicates' => true,
                'sortable'          => true,
                'orderName'         => 'outcomeorder',
                'hiddenInputs'      => 'outcomegibbonOutcomeID',
                'uniqueID'          => 'outcomegibbonOutcomeID',
            ])
            ->placeholder(__('Key outcomes listed here...'))
            ->addToolInput($outcomeSelector)
            ->addBlockButton('showHide', __('Show/Hide'), 'plus.png');

        // Add predefined block data (for creating new blocks, triggered with the outcome selector)
        $data = ['gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'gibbonDepartmentID' => $gibbonDepartmentID];
        $sql = "SELECT gibbonOutcomeID as outcomegibbonOutcomeID, gibbonOutcome.name as outcometitle, category as outcomecategory, description as outcomecontents, scope
                FROM gibbonOutcome JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonOutcome.gibbonYearGroupIDList))
                WHERE FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)
                AND (scope='School' OR (scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID))
                AND gibbonOutcome.active='Y'";
        $outcomeData = $this->pdo->select($sql, $data)->fetchAll();

        foreach ($outcomeData as $outcome) {
            $customBlocks->addPredefinedBlock($outcome['outcomegibbonOutcomeID'], $outcome);
        }

        return $customBlocks;
    }

    /**
     * Creates a drop-down list of available outcomes by year group. Groups outcomes by school-wide and by department.
     *
     * @param string $name
     * @param string $gibbonYearGroupIDList
     * @param string $gibbonDepartmentID
     * @return OutputableInterface
     */
    public function createSelectOutcome($name, $gibbonYearGroupIDList, $gibbonDepartmentID) : OutputableInterface
    {
        // Get School Outcomes
        $data = ['gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'noCategory' => '['.__('No Category').']'];
        $sql = "SELECT (CASE WHEN category='' THEN :noCategory ELSE category END) AS groupBy, CONCAT('all ', category) as chainedTo, gibbonOutcomeID AS value, gibbonOutcome.name AS name
                FROM gibbonOutcome
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonOutcome.gibbonYearGroupIDList))
                WHERE active='Y' AND scope='School'
                AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)
                GROUP BY gibbonOutcome.gibbonOutcomeID
                ORDER BY groupBy, name";

        // Get Departmental Outcomes
        $data2 = ['gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'gibbonDepartmentID' => $gibbonDepartmentID];
        $sql2 = "SELECT CONCAT(gibbonDepartment.name, ': ', category) AS groupBy, CONCAT('all ', category) as chainedTo, gibbonOutcomeID AS value, gibbonOutcome.name AS name
                FROM gibbonOutcome
                JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonOutcome.gibbonYearGroupIDList))
                WHERE gibbonOutcome.active='Y' AND scope='Learning Area'
                AND gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID
                AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)
                GROUP BY gibbonOutcome.gibbonOutcomeID
                ORDER BY groupBy, gibbonOutcome.name";

        $col = $this->createColumn($name.'Col')->setClass('');

        $select = $col->addSelect($name)
            ->setClass('addBlock floatNone standardWidth')
            ->setAttribute('@change', 'handleToolChange($el)')
            ->fromArray(['' => __('Choose an outcome to add it to this lesson')])
            ->fromArray([__('SCHOOL OUTCOMES') => []])
            ->fromQueryChained($this->pdo, $sql, $data, $name.'Filter', 'groupBy')
            ->fromArray([__('LEARNING AREAS') => []])
            ->fromQueryChained($this->pdo, $sql2, $data2, $name.'Filter', 'groupBy');

        // Get Categories by Year Group
        $data3 = ['gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'noCategory' => '['.__('No Category').']', 'gibbonDepartmentID' => $gibbonDepartmentID];
        $sql3 = "SELECT category as value, (CASE WHEN category='' THEN :noCategory ELSE category END) as name
                FROM gibbonOutcome
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonOutcome.gibbonYearGroupIDList))
                WHERE active='Y' AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)
                AND (scope='School' OR (scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID))
                GROUP BY gibbonOutcome.category
                HAVING COUNT(*) > 0";

        $col->addSelect($name.'Filter')
            ->setClass('floatNone standardWidth')
            ->fromArray(['all' => __('View All')])
            ->fromQuery($this->pdo, $sql3, $data3);

        return $col;
    }

    /**
     * Creates a template for displaying Outcomes in a CustomBlocks input.
     *
     * @param string $allowOutcomeEditing
     * @return OutputableInterface
     */
    public function createOutcomeBlockTemplate($allowOutcomeEditing) : OutputableInterface
    {
        $blockTemplate = $this->createRow();
            $row = $blockTemplate->addRow()->setClass('w-full p-4 flex items-center gap-2');
            $row->addTextField('outcometitle')
                ->setOuterClass('w-3/4 title readonly')
                ->readonly()
                ->placeholder(__('Outcome Name'));

            $row->addTextField('outcomecategory')
                ->setOuterClass('w-1/4 readonly')
                ->readonly();

            $col = $blockTemplate->addRow()->addClass('w-full px-4 showHide w-full')->addColumn();
            if ($allowOutcomeEditing == 'Y') {
                $col->addTextArea('outcomecontents')->setRows(3);
            }

        return $blockTemplate;
    }
}
