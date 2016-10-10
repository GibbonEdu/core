<?php $rowEntry = (array)$el-returnRecord(); ?>

<a name='<?php echo $rowEntry['gibbonMarkbookEntryID']; ?>'></a>

<tr>
    <td>
        <span title='<?php echo htmlPrep($rowEntry['description']); ?>'><?php echo $rowEntry['name']; ?></span><br/>
        <span style='font-size: 90%; font-style: italic; font-weight: normal'>
        	<?php echo $this->__('Marked on').' '. $this->dateConvertBack($rowEntry['completeDate']); ?><br/>
        </span>
    </td>

    <?php if ($rowEntry['attainment'] == 'N' || ($rowEntry['gibbonScaleIDAttainment'] == '' && $rowEntry['gibbonRubricIDAttainment'] == '')) { ?>
        <td class='dull' style='color: #bbb; text-align: center'>
        	<?php echo $this->__('N/A'); ?>
        </td>
    <?php } else { ?>
        <td style='text-align: center'><?php
        $attainmentExtra = '';
        $resultAttainment = $this->getRecord('scale')->find($rowEntry['gibbonScaleIDAttainment']);
        if ($this->getRecord('scale')->getSuccess()) {
            $rowAttainment = (array)$resultAttainment->returnRcord();
            $attainmentExtra = '<br/>'.$this->__($rowAttainment['usage']);
        }
        $styleAttainment = "style='font-weight: bold'";
        if ($rowEntry['attainmentConcern'] == 'Y' and $showParentAttainmentWarning == 'Y') {
            $styleAttainment = "style='color: #".$el->alert['colour']."; font-weight: bold; border: 2px solid #".$el->alert['colour']."; padding: 2px 4px; background-color: #".$el->alert['BGcolour']."; '";
        } elseif ($rowEntry['attainmentConcern'] == 'P' and $showParentAttainmentWarning == 'Y') {
            $styleAttainment = "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'";
        } ?>
        <div <?php echo $styleAttainment; ?>><?php echo $rowEntry['attainmentValue'];
        if ($rowEntry['gibbonRubricIDAttainment'] != '') { 
			$this->getLink('rubric', array('q'=>'/modules/Markbook/markbook_view_rubric.php', 'fullscreen' => true, 'gibbonRubricID'=>$rowEntry['gibbonRubricIDAttainment'], 'gibbonCourseClassID' => $rowEntry['gibbonCourseClassID'], 'gibbonMarkbookColumnID' => $rowEntry['gibbonMarkbookColumnID'], 'class' => 'thickbox', 'title' => 'View Rubric', 'gibbonPersonID' => $el->personID, 'mark' => false, 'type' => 'attainment', 'width' => 1100, 'height' => 550)); 
        } ?>
        </div><?php 
        if ($rowEntry['attainmentValue'] != '') { ?>
            <div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><strong><?php echo $this->htmlPrep($this->__($rowEntry['attainmentDescriptor'])); ?></strong>'.$this->__($attainmentExtra); ?></strong></div><?php
        } ?>
        </td><?php
    }

	if ($rowEntry['effort'] == 'N' || ($rowEntry['gibbonScaleIDEffort'] == '' && $rowEntry['gibbonRubricIDEffort'] == '')) { ?>
		<td class='dull' style='color: #bbb; text-align: center'>
		<?php echo $this->__('N/A'); ?>
		</td><?php
	} else { ?>
		<td style='text-align: center'><?php
			$effortExtra = '';
			$dataEffort = array('gibbonScaleID' => $rowEntry['gibbonScaleIDEffort']);
			$sqlEffort = $this->getRecord('scale')->find($rowEntry['gibbonScaleIDEffort']);
			if ($this->getRecord('scale')->getSuccess()) {
				$rowEffort = (array)$resultEffort->returnRecord();
				$effortExtra = '<br/>'.$this->__($rowEffort['usage']);
			}
			$styleEffort = "style='font-weight: bold;'";
			if ($rowEntry['effortConcern'] == 'Y' && $showParentEffortWarning == 'Y') { 
				$styleEffort = "style='color: #".$el->alert['colour']."; font-weight: bold; border: 2px solid #".$el->alert['colour']."; padding: 2px 4px; background-color: #".$el->alert['BGcolour']."; '";
			} ?>
			<div <?php echo $styleEffort; ?>><?php echo $rowEntry['effortValue']; 
			if ($rowEntry['gibbonRubricIDEffort'] != '') { 
				$this->getLink('rubric', array('q'=>'/modules/Markbook/markbook_view_rubric.php', 'fullscreen' => true, 'gibbonRubricID'=>$rowEntry['gibbonRubricIDEffort'], 'gibbonCourseClassID' => $rowEntry['gibbonCourseClassID'], 'gibbonMarkbookColumnID' => $rowEntry['gibbonMarkbookColumnID'], 'class' => 'thickbox', 'title' => 'View Rubric', 'gibbonPersonID' => $el->personID, 'mark' => false, 'type' => 'effort', 'width' => 1100, 'height' => 550)); 
			} ?>
			</div><?php
			if ($rowEntry['effortValue'] != '') { ?>
				<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><strong><?php echo htmlPrep($this->__($rowEntry['effortDescriptor'])); ?></strong><?php echo $this->__($effortExtra); ?></div><?php
			} ?>
		</td> <?php
	}

	if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') { ?>
		<td class='dull' style='color: #bbb; text-align: left'>
		<?php echo $this->__('N/A'); ?>
		</td>
	<?php } else { ?>
		<td>
		<?php if ($rowEntry['comment'] != '') {
			if (strlen($rowEntry['comment']) > 50) {
				$this->addScript('
<script type="text/javascript">
	$(document).ready(function() {
		$("comment-'.$rowEntry['gibbonMarkbookEntryID'].'").hide();
		$("show_hide-'.$rowEntry['gibbonMarkbookEntryID'].'").fadeIn(1000);
		$("show_hide-'.$rowEntry['gibbonMarkbookEntryID'].'").click(function() {
			$("comment-'.$rowEntry['gibbonMarkbookEntryID'].'").fadeToggle(1000);
		});
	});
</script>
'); ?>
				<span><?php echo mb_substr($rowEntry['comment'], 0, 50); ?>...<br/>
					<a title='<?php echo $this->__('View Description'); ?>' class='show_hide-<?php echo $rowEntry['gibbonMarkbookEntryID']; ?>' onclick='return false;' href='#'><?php echo $this->__('Read more'); ?></a>
                </span><br/><?php
			} else {
				$gradesOutput .= nl2br($rowEntry['comment']);
			} ?>
			<br/><?php
		}
		if ($rowEntry['response'] != '') { ?>
			<a title='<?php echo $this->__('Uploaded Response'); ?>' href='<?php echo GIBBON_URL; ?>'.$rowEntry['response']; ?>'><?php echo $this->__('Uploaded Response'); ?></a><br/><?php
		} ?>
		</td> <?php
	}

	if ($rowEntry['gibbonPlannerEntryID'] == 0) { ?>
		<td class='dull' style='color: #bbb; text-align: left; '>
			<?php echo $this->__('N/A'); ?>
		</td>
	<?php } else { 
		$resultSub = $this->getRecord('plannerEntry')->find($rowEntry['gibbonPlannerEntryID']);
		if ($this->getRecord('plannerEntry')->getSuccess() && $resultSub->getField('homeworkSubmission') != 'Y') { ?>
			<td class='dull' style='color: #bbb; text-align: left'>
			<?php echo $this->__('N/A'); ?>
			</td>
		<?php } else { ?>
			<td>
			<?php $rowSub = (array) $resultSub->returnRecord();

$this->dump($rowSub, true); 
			$dataWork = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID'], 'gibbonPersonID' => $personID);
			$resultWork = $this->getRecord('plannerEntryHomework')->findAllBy($dataWork, array('count' => 'DESC'));
			if (count($resultWork) > 0) {
				$rowWork = (array)reset($resultWork);
	
				if ($rowWork['status'] == 'Exemption') {
					$linkText = $this->__('Exemption');
				} elseif ($rowWork['version'] == 'Final') {
					$linkText = $this->__('Final');
				} else {
					$linkText = $this->__('Draft') . $rowWork['count'];
				}
	
				$style = '';
				$status = 'On Time';
				if ($rowWork['status'] == 'Exemption') {
					$status = $this->__('Exemption');
				} elseif ($rowWork['status'] == 'Late') {
					$style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px;'";
					$status = $this->__('Late');
				}
	
				if ($rowWork['type'] == 'File') { ?>
					<span title='<?php echo $rowWork['version']. $status . $this->__('Submitted at %1$s on %2$s', array(mb_substr($rowWork['timestamp'], 11, 5), $this->dateConvertBack(substr($rowWork['timestamp'], 0, 10)))); ?>' <?php echo $style; ?>>
                    	<a href='<?php echo GIBBON_URL . $rowWork['location']; ?>'><?php echo $linkText; ?></a>
                    </span><?php
				} elseif ($rowWork['type'] == 'Link') { ?>
					<span title='<?php echo $rowWork['version'] . $status . $this->__('Submitted at %1$s on %2$s', array(mb_substr($rowWork['timestamp'], 11, 5), $this->view->dateConvertBack(substr($rowWork['timestamp'], 0, 10)))); ?>' <?php echo $style; ?>>
                    	<a target='_blank' href='<?php echo $rowWork['location']; ?>'>$linkText</a>
                    </span><?php
				} else { ?>
					<span title='<?php echo $style . $this->__('Recorded at %1$s on %2$s', array(mb_substr($rowWork['timestamp'], 11, 5), $this->view->dateConvertBack(substr($rowWork['timestamp'], 0, 10)))); ?>' <?php echo $style; ?>><?php echo $linkText; ?>
                    </span><?php
				}
			} else {
				if (date('Y-m-d H:i:s') < $rowSub['homeworkDueDateTime']) { ?>
					<span title='Pending'><?php echo $this->__('Pending'); ?></span><?php
				} else {
					if ($row['dateStart'] > $rowSub['date']) { ?>
						<span title='<?php echo $this->__('Student joined school after assessment was given'); ?>' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>
							<?php echo $this->__('NA'); ?>
                        </span><?php
					} else {
						if ($rowSub['homeworkSubmissionRequired'] == 'Compulsory') { ?>
							<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>
								<?php echo $this->__('Incomplete'); ?>
                            </div><?php
						} else {
							echo $this->__('Not submitted online');
						}
					}
				}
			} ?>
			</td><?php
		}
	} ?>
</tr><?php
if (strlen($rowEntry['comment']) > 50) { ?>
    <tr class='comment-<?php echo $rowEntry['gibbonMarkbookEntryID']; ?>' id='comment-<?php echo $rowEntry['gibbonMarkbookEntryID']; ?>'>
        <td colspan='6'>
 	       <?php echo nl2br($rowEntry['comment']); ?>
        </td>
    </tr><?php
}
