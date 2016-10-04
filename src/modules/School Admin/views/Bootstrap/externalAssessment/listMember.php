<tr <?php echo $el->getField('active') == 'N' ? ' class="error"' : '' ; ?>>
    <td>
        <?php
		if ( ! empty($el->getField("website")))
			echo $this->__('%1$s'.$el->getField("name"). '%2$s', array('<a href="'.$el->getField("website").'" target="_blank" title="'.$el->getField("name").'">', '</a>'));
		else
        	echo $this->__($el->getField("name")); ?>
    </td>
    <td>
        <?php echo $this->__($el->getField("description")) ; ?>
    </td>
    <td style="text-align: center">
        <?php echo $this->__($el->getField("active")) ; ?>
    </td>
    <?php if (! isset($el->action) || $el->action) { ?>
        <td style="width: 100px;">
        <?php  
            $this->getLink('edit', array('q'=>'/modules/School Admin/externalAssessments_manage_edit.php', 'gibbonExternalAssessmentID' => $el->getField("gibbonExternalAssessmentID")));
            $this->getLink('delete', array('q'=>'/modules/School Admin/externalAssessments_manage_delete.php', 'gibbonExternalAssessmentID' => $el->getField("gibbonExternalAssessmentID")));
        ?>
        </td>
    <?php } ?>
</tr>
