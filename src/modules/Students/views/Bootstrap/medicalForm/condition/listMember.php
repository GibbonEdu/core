<?php
use Gibbon\trans ;
use Gibbon\helper ;

?>
<tr>
    <td>
    	<?php echo $el->getField('name'); ?>
    </td>
    <td>
    	<?php echo $el->getField('risk'); ?>
    </td>
    <td><?php
						if (! empty($el->getField('triggers'))) {
							echo '<strong>'.trans::__('Triggers').':</strong> '.$el->getField('triggers').'<br/>';
						}
						if (!empty($el->getField('reaction'))) {
							echo '<strong>'.trans::__('Reaction').':</strong> '.$el->getField('reaction').'<br/>';
						}
						if (!empty($el->getField('response'))) {
							echo '<strong>'.trans::__('Response').':</strong> '.$el->getField('response').'<br/>';
						}
						if (!empty($el->getField('lastEpisode'))) {
							echo '<strong>'.trans::__('Last Episode').':</strong> '.helper::dateConvertBack($el->getField('lastEpisode')).'<br/>';
						}
						if (!empty($el->getField('lastEpisodeTreatment'))) {
							echo '<strong>'.trans::__('Last Episode Treatment').':</strong> '.$el->getField('lastEpisodeTreatment').'<br/>';
						} ?>
    </td>
    <td>
   		<?php echo $el->getField('medication'); ?>
    </td>
    <td>
   		<?php echo $el->getField('comment'); ?>
    </td>
    <?php if (! isset($el->action) || $el->action) { ?>
    <td><?php
		$this->getLink('edit', array('q' => '/modules/Students/medicalForm_manage_condition_edit.php', 'gibbonPersonMedicalConditionID' => $el->getField('gibbonPersonMedicalConditionID'), 'gibbonPersonMedicalID' => $el->getField('gibbonPersonMedicalID'), 'gibbonPersonID' => $el->personID));
		$this->getLink('delete', array('q' => '/modules/Students/medicalForm_manage_condition_delete.php', 'gibbonPersonMedicalConditionID' => $el->getField('gibbonPersonMedicalConditionID'), 'gibbonPersonMedicalID' => $el->getField('gibbonPersonMedicalID'), 'gibbonPersonID' => $el->personID)); ?>
    </td>
    <?php } ?>
</tr>
