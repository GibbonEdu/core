<tr>
    <td>
    <?php echo $el->getPerson()->formatName(true); ?>
    </td>
    <td>
    <?php echo $el->getYearGroup()->getField('nameShort'); ?>
    </td>
    <td>
    <?php echo $el->getRollGroup()->getField('nameShort'); ?>
    </td>
    <?php if (! isset($el->action) || $el->action) { ?>
    <td>
		<?php
        $this->getLink('view details', array('q' => '/modules/Students/student_view_details.php', 'gibbonPersonID' => $el->getField('gibbonPersonID')));
        ?>
    </td>
    <?php } ?>
</tr>
