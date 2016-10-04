<tr>
    <td>
        <?php echo $el->getField('sequenceNumber') ; ?>
    </td>
    <td>
        <?php echo $el->getField('name'); ?><br/>
        <span style='font-size: 85%; font-style: italic'><?php echo $el->getField('nameShort'); ?></span>
    </td>
    <td>
        <?php echo ! empty( $el->getField('description')) ? $el->getField('description') : '' ; ?>
    </td>
    <?php if (! isset($el->action) || $el->action) { ?>
    <td>
        <?php  
            $this->getLink('edit', array('q' => '/modules/School Admin/inDescriptors_manage_edit.php', 'gibbonINDescriptorID'=>$el->getField('gibbonINDescriptorID')));
            $this->getLink('delete', array('q' => '/modules/School Admin/inDescriptors_manage_delete.php', 'gibbonINDescriptorID'=>$el->getField('gibbonINDescriptorID')));
        ?>
    </td>
	<?php } ?>
</tr>
