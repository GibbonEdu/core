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

namespace Gibbon\Tables\Prefab;

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Behaviour\BehaviourGateway;

/**
 * BehaviourTable
 *
 * @version v28
 * @since   v28
 */
class BehaviourTable
{
    protected $db;
    protected $settingGateway;
    protected $behaviourGateway;

    protected $enableDescriptors;
    protected $enableLevels;
    protected $policyLink;

    public function __construct(Connection $db, SettingGateway $settingGateway, BehaviourGateway $behaviourGateway)
    {
        $this->db = $db;
        $this->behaviourGateway = $behaviourGateway;

        $this->enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
        $this->enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');
        $this->policyLink = $settingGateway->getSettingByScope('Behaviour', 'policyLink');
    }

    public function create($gibbonSchoolYearID, $gibbonFormGroupID)
    {
        $criteria = $this->behaviourGateway->newQueryCriteria()
            ->sortBy('date', 'DESC')
            ->sortBy('timestamp', 'DESC')
            ->fromPOST('formGroupBehaviour');

        $behaviour = $this->behaviourGateway->queryBehaviourByFormGroup($criteria, $gibbonSchoolYearID, $gibbonFormGroupID);

        $table = DataTable::create('formGroupBehaviour')->withData($behaviour);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Behaviour/behaviour_manage_add.php')
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->displayLabel();

        if (!empty($this->policyLink)) {
            $table->addHeaderAction('document', __('View Behaviour Policy'))
                ->setExternalURL($this->policyLink)
                ->directLink()
                ->displayLabel();
        }

        $table->addExpandableColumn('comment')
            ->format(function($behaviour) {
                $output = '';
                if (!empty($behaviour['comment'])) {
                    $output .= Format::bold(__('Incident')).'<br/>';
                    $output .= nl2br($behaviour['comment']).'<br/>';
                }

                if (!empty($behaviour['followUps'])) {
                    foreach ($behaviour['followUps'] as $followUp) { 
                        $output .= '<br/>'.Format::bold(__('Follow Up By ').$followUp['firstName']._(' ').$followUp['surname']).'<br/>';
                        $output .= nl2br($followUp['followUp']).'<br/>';
                    }
                }
                return $output;
            });

        $table->addColumn('student', __('Student & Date'))
            ->sortable(['student.surname', 'student.preferredName'])
            ->context('primary')
            ->format(function($person)  {
                return Format::bold(Format::nameLinked($person['gibbonPersonID'],'', $person['preferredName'], $person['surname'], 'Student', true, true, ['subpage' => 'Behaviour']));
            })
            ->formatDetails(function($behaviour) {
                if (substr($behaviour['timestamp'], 0, 10) > $behaviour['date']) {
                    return __('Updated:').' '.Format::date($behaviour['timestamp']).'<br/>'
                         . __('Incident:').' '.Format::date($behaviour['date']).'<br/>';
                } else {
                    return Format::date($behaviour['timestamp']);
                }
            });

        $table->addColumn('type', __('Type'))
            ->context('secondary')
            ->width('8%')
            ->format(function($behaviour) {
                if ($behaviour['type'] == 'Negative') {
                    return icon('solid', 'cross', 'size-6 fill-current text-red-700');
                } elseif ($behaviour['type'] == 'Positive') {
                    return icon('solid', 'check', 'size-6 fill-current text-green-600');
                }
            });

        if ($this->enableDescriptors == 'Y') {
            $table->addColumn('descriptor', __('Descriptor'))->context('primary');
        }

        if ($this->enableLevels == 'Y') {
            $table->addColumn('level', __('Level'))->width('15%');
        }

        $table->addColumn('teacher', __('Teacher'))
            ->context('secondary')
            ->sortable(['preferredNameCreator', 'surnameCreator'])
            ->width('25%')
            ->format(function($person) {
                return Format::name($person['titleCreator'], $person['preferredNameCreator'], $person['surnameCreator'], 'Staff');
            });

        $table->addActionColumn()
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->addParam('gibbonBehaviourID')
            ->addParam('gibbonFormGroupID')
            ->addParam('gibbonYearGroupID')
            ->addParam('type')
            ->format(function ($values, $actions) {
                $actions->addAction('view', __('View'))
                    ->setURL('/modules/Behaviour/behaviour_manage_edit.php');
            });

        return $table;
    }
}
