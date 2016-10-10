<tr>
    <td style="width: 175px">
        <?php echo $el->getField('sequenceNumber') ; ?>
    </td>
    <td>
        <?php echo $el->getField('name') ; ?>
    </td>
    <td>
        <?php echo $el->getField('nameShort'); ?>
    </td>
<?php if (! isset($el->action) || $el->action) { ?>
    <td>
        <?php  
            $this->getLink('edit', array('q'=>'/modules/School Admin/yearGroup_manage_edit.php', 'gibbonYearGroupID' => $el->getField('gibbonYearGroupID')));
            $this->getLink('delete', array('q'=>'/modules/School Admin/yearGroup_manage_delete.php', 'gibbonYearGroupID' => $el->getField('gibbonYearGroupID')));
        ?>
    </td>
<?php } ?>
</tr>
