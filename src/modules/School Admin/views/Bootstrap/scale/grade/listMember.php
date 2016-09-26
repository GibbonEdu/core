<tr id="<?php echo $el->getField('gibbonScaleGradeID'); ?>">
    <td>
        <?php echo $el->getField("value"); ?>
    </td>
    <td>
        <?php echo $this->__($el->getField("descriptor")) ; ?>
    </td>
    <td>
        <?php echo $el->getField("sequenceNumber") ; ?>
    </td>
    <td>
        <?php echo $this->__($el->getField("isDefault")) ; ?>
    </td>
	<?php if (! isset($el->action) || $el->action) { ?>
        <td>
        <?php  
            $this->getLink('edit', array('q'=>'/modules/School Admin/gradeScales_manage_edit_grade_edit.php', 'gibbonScaleGradeID'=>$el->getField("gibbonScaleGradeID"), 'gibbonScaleID' => $el->getField("gibbonScaleID")));
            $this->getLink('delete', array('q'=>'/modules/School Admin/gradeScales_manage_edit_grade_delete.php', 'gibbonScaleGradeID'=>$el->getField("gibbonScaleGradeID"), 'gibbonScaleID' => $el->getField("gibbonScaleID")));
        ?>
        </td>
    <?php } ?>
</tr>