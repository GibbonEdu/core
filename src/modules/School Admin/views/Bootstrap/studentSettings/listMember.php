<tr>
    <td>
        <?php echo $el->getField('name') ; ?>
    </td>
    <td>
        <?php echo $this->__($el->getField('active')); ?>
    </td>
    <td>
        <?php echo $el->getField('template') ; ?>
    </td>
<?php if (! isset($el->action) || $el->action) { ?>
    <td>
        <?php  
            $this->getLink('edit', array('q' => '/modules/School Admin/studentsSettings_noteCategory_edit.php', 'gibbonStudentNoteCategoryID' => $el->getField('gibbonStudentNoteCategoryID')));
            $this->getLink('delete', array('q' => '/modules/School Admin/studentsSettings_noteCategory_delete.php', 'gibbonStudentNoteCategoryID' => $el->getField('gibbonStudentNoteCategoryID')));
        ?>
    </td>
<?php } ?>
</tr>
