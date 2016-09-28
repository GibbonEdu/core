<tr>
    <td>
        <?php echo $el->getField('name') ; ?><br />
        <span style='font-size: 85%; font-style: italic'><?php echo $el->getField('nameShort'); ?></span>
    </td>
    <td>
        <?php for($x=1; $x<4; $x++) {
			$w = 'getTutor'.$x;
            $tutor = $el->$w();
            echo $tutor->formatName(false, true).'<br/>';
        } ?>
    </td>
    <td>
        <?php echo $el->getSpace()->getField('name') ; ?>
    </td>
    <td style="max-width: 200px">
        <?php echo ! empty($el->getField('website')) ? $el->getField('website') : '' ; ?>
    </td>
	<?php if (! isset($el->action) || $el->action) { ?>
    <td>
	<?php  
        $this->getLink('edit', array('q'=>'/modules/School Admin/rollGroup_manage_edit.php', 'gibbonSchoolYearID' => $el->getField('gibbonSchoolYearID'), 'gibbonRollGroupID' => $el->getField('gibbonRollGroupID')));
        $this->getLink('delete', array('q'=>'/modules/School Admin/rollGroup_manage_delete.php', 'gibbonSchoolYearID' => $el->getField('gibbonSchoolYearID'), 'gibbonRollGroupID' => $el->getField('gibbonRollGroupID')));
    ?>
    </td>
	<?php } ?>
</tr>