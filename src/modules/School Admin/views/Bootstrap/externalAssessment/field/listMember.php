<tr id="<?php echo $el->getField('gibbonExternalAssessmentFieldID'); ?>">
    <td>
		<?php echo $this->__($el->getField("name")); ?>
    </td>
    <td>
        <?php echo $el->getField("category") ; ?>
    </td>
    <td style="text-align: center">
        <?php echo $el->getField("order") ; ?>
    </td>
    <?php if (! isset($el->action) || $el->action) { ?>
        <td>
        <?php  
            $this->getLink('edit', array('q' => '/modules/School Admin/externalAssessments_manage_edit_field_edit.php', 'gibbonExternalAssessmentFieldID' => $el->getField('gibbonExternalAssessmentFieldID'), 'gibbonExternalAssessmentID' => $el->getField('gibbonExternalAssessmentID')));
            $this->getLink('delete', array('q' => '/modules/School Admin/externalAssessments_manage_edit_field_delete.php', 'gibbonExternalAssessmentFieldID' => $el->getField('gibbonExternalAssessmentFieldID'), 'gibbonExternalAssessmentID' => $el->getField('gibbonExternalAssessmentID')));
        ?>
        </td>
    <?php } ?>
</tr>
