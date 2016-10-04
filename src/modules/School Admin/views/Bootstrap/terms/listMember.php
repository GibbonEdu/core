<tr>
    <td>
        <?php echo $el->schoolYear->getField('name') ; ?>
    </td>
    <td>
        <?php echo $el->getField("sequenceNumber") ; ?>
    </td>
    <td>
        <?php print $el->getField("name") ; ?>
    </td>
    <td>
        <?php echo $el->getField("nameShort") ; ?>
    </td>
    <td>
        <?php if (! empty($el->getField("firstDay")) && ! empty($el->getField("lastDay"))) {
                echo $el->dateConvertBack($el->getField("firstDay")) . " - " . $el->dateConvertBack($el->getField("lastDay")) ;
            } ?>
    </td>
	<?php if (! isset($el->action) || $el->action) { ?>
    <td>
		<?php  
            $this->getLink('edit', array('q'=>'/modules/School Admin/schoolYearTerm_manage_edit.php', 'gibbonSchoolYearTermID' => $el->getField('gibbonSchoolYearTermID')));
            $this->getLink('delete', array('q'=>'/modules/School Admin/schoolYearTerm_manage_delete.php', 'gibbonSchoolYearTermID' => $el->getField('gibbonSchoolYearTermID')));
        ?>
    </td>
    <?php } ?>
</tr>
