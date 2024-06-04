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

use Gibbon\Domain\IndividualNeeds\INGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;

//$mode can be blank or "disabled". $archive is a serialized array of values previously archived
function printINStatusTable($connection2, $guid, $gibbonPersonID, $mode = '', $archive = '')
{
    $output = "";
    global $container;
    $gateway = $container->get(INGateway::class);
    if ($archive == '') { //Use live data
        $criteria = $gateway
          ->newQueryCriteria()
          ->filterBy('gibbonPersonID', $gibbonPersonID)
          ->fromPOST();
        $personalDescriptors = $gateway->queryIndividualNeedsPersonDescriptors($criteria)->toArray();
    } else //Use archived data
    {
        $archive = unserialize($archive);
        $personalDescriptors = [];
        foreach ($archive as $archiveEntry) {
            array_push($personalDescriptors, [
            'gibbonINDescriptorID' => $archiveEntry['gibbonINDescriptorID'],
            'gibbonAlertLevelID' => $archiveEntry['gibbonAlertLevelID']
            ]);
        }
    }
    $allDescriptors = $gateway->queryIndividualNeedsDescriptors($gateway->newQueryCriteria())->toArray();
    $alertLevels = $gateway->queryAlertLevels($gateway->newQueryCriteria())->toArray();

    if ($archive != '') {
      /*
       * Given this is archived data, there's a possibility some of the data
       * may no longer exist. Display an error message if this data no longer
       * exists.
       */
        $missingDescriptor = false;
        $missingAlert = false;
        foreach ($personalDescriptors as $pDescriptor) {
            $foundDescriptor = false;
            $foundAlert = false;
            foreach ($allDescriptors as $descriptor) {
                if ($descriptor['gibbonINDescriptorID'] == $pDescriptor['gibbonINDescriptorID']) {
                    $foundDescriptor = true;
                }
            }
            foreach ($alertLevels as $level) {
                if ($level['gibbonAlertLevelID'] == $pDescriptor['gibbonAlertLevelID']) {
                    $foundAlert = true;
                }
            }
            if ($foundDescriptor == false) {
                $missingDescriptor = true;
            }
            if ($foundAlert == false) {
                $missingAlert = true;
            }
        }
        if ($missingAlert) {
            $output .= Format::alert(__('Some of the alert levels present in your provided archive can no longer be found. These alert levels will no longer show in the table below.'));
        }
        if ($missingDescriptor) {
            $output .= Format::alert(__('Some of the descriptors present in your provided archive can no longer be found. These descriptors will no longer show in the table below.'));
        }
    }

    $results = array_map(function ($descriptor) use ($alertLevels, $personalDescriptors) {
        $result = [
        'gibbonINDescriptorID' => $descriptor['gibbonINDescriptorID'],
        'gibbonINDescriptorName' => $descriptor['name'],
        'gibbonINDescriptorNameShort' => $descriptor['nameShort'],
        'gibbonINDescriptorDescription' => $descriptor['description'],
        'gibbonINDescriptorSequenceNumber' => $descriptor['sequenceNumber']
        ];
        foreach ($alertLevels as $alertLevel) {
            $pDescriptorAssigned = false;
            foreach ($personalDescriptors as $pDescriptor) {
                if ($pDescriptor['gibbonINDescriptorID'] == $descriptor['gibbonINDescriptorID']) {
                    if ($pDescriptor['gibbonAlertLevelID'] == $alertLevel['gibbonAlertLevelID']) {
                        $pDescriptorAssigned = true;
                    }
                }
            }
            $result["alert_{$alertLevel['name']}"] = $pDescriptorAssigned;
        }
        return $result;
    }, $allDescriptors);

  //Results array needs to be a dataset so it can be rendered by the table;
    $dataset = new DataSet($results);

    $table = DataTable::create('descriptors');
    $table->addColumn('gibbonINDescriptorName', __('Descriptor'))->context('primary');
    foreach ($alertLevels as $alertLevel) {
        $table
        ->addColumn("alert_{$alertLevel['name']}", $alertLevel['name'])
        ->context('primary')
        ->format(function ($level) use ($alertLevel, $mode) {
            $checked = $level["alert_{$alertLevel['name']}"] == true ? 'checked' : '';
            $disabled = $mode == 'disabled' ? 'disabled' : '';
            $value = "{$level['gibbonINDescriptorID']}-{$alertLevel['gibbonAlertLevelID']}";
            return "<input type='checkbox' name='status[]' value={$value} {$checked} $disabled></input>";
        });
    }
    $output .= $table->render($dataset);
    return $output;
}

function getInvestigationCriteriaArray($strand)
{
    $options = array();

    if ($strand == 'Cognition') {
        $options = array(
            'There is limited understanding of concepts generally' => __('There is limited understanding of concepts generally'),
            'Learning new concepts is a challenge' => __('Learning new concepts is a challenge'),
            'Simple concepts and processes with one or two steps can be understood and applied to classroom learning' => __('Simple concepts and processes with one or two steps can be understood and applied to classroom learning'),
            'New concepts can be understood with ease and used consistently in a structured learning environment' => __('New concepts can be understood with ease and used consistently in a structured learning environment')
        );
    } elseif ($strand == 'Memory') {
        $options = array(
            'The ability to hold and use important information in work­ing mem­ory over a short period of time' => __('The ability to hold and use important information in work­ing mem­ory over a short period of time'),
            'The ability to hold several ideas in mind at once' => __('The ability to hold several ideas in mind at once'),
            'The ability to remember what has been learnt in recent lessons' => __('The ability to remember what has been learnt in recent lessons'),
            'The ability to retrieve information from long-term memory' => __('The ability to retrieve information from long-term memory'),
        );
    } elseif ($strand == 'Self-Management') {
        $options = array(
            'Setting goals' => __('Setting goals'),
            'Managing time' => __('Managing time'),
            'Being organised' => __('Being organised'),
            'Monitoring, controlling and self directing aspects of learning for themselves' => __('Monitoring, controlling and self directing aspects of learning for themselves'),
            'Emotional self-regulation' => __('Emotional self-regulation'),
        );
    } elseif ($strand == 'Attention') {
        $options = array(
            'Sus­taining con­cen­tra­tion and attention in lessons' => __('Sus­taining con­cen­tra­tion and attention in lessons'),
            'Paying attention to relevant information' => __('Paying attention to relevant information'),
            'Shifting attention when needed' => __('Shifting attention when needed'),
            'Monitoring, controlling and self directing aspects of learning for themselves' => __('Monitoring, controlling and self directing aspects of learning for themselves'),
            'Resisting distraction and internal urges to do other things than the task at hand' => __('Resisting distraction and internal urges to do other things than the task at hand'),
        );
    } elseif ($strand == 'Social Interaction') {
        $options = array(
            'Approaching others and making friends' => __('Approaching others and making friends'),
            'Ability to hold a conversation' => __('Ability to hold a conversation'),
            'Ability to use appropriate non-verbal communication (eye contact, facial expressions, gestures, body language)' => __('Ability to use appropriate non-verbal communication (eye contact, facial expressions, gestures, body language)'),
            'Adjusting behaviour to suit contexts' => __('Adjusting behaviour to suit contexts'),
        );
    } elseif ($strand == 'Communication') {
        $options = array(
            'Word reading accuracy (ability to sound out words)' => __('Word reading accuracy (ability to sound out words)'),
            'Reading rate or fluency (speed)' => __('Reading rate or fluency (speed)'),
            'Reading comprehension (understanding of texts)' => __('Reading comprehension (understanding of texts)'),
            'Spelling accuracy' => __('Spelling accuracy'),
            'Grammar and punctuation accuracy' => __('Grammar and punctuation accuracy'),
            'Clarity or organization of written expression' => __('Clarity or organization of written expression'),
            'Verbal articulation of meaning with an awareness of audience' => __('Verbal articulation of meaning with an awareness of audience')
        );
    }

    return $options;
}

function getInvestigationCriteriaStrands($includeCognition = false)
{
    $options = array(
        0 => array('name' => 'memory', 'nameHuman' => 'Memory'),
        1 => array('name' => 'selfManagement', 'nameHuman' => 'Self-Management'),
        2 => array('name' => 'attention', 'nameHuman' => 'Attention'),
        3 => array('name' => 'socialInteraction', 'nameHuman' => 'Social Interaction'),
        4 => array('name' => 'communication', 'nameHuman' => 'Communication'),
    );

    if ($includeCognition) {
        array_unshift($options, array('name' => 'cognition', 'nameHuman' => 'Cognition'));
    }

    return $options;
}
