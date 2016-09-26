<tr <?php echo $el->getField('active') == 'N' ? ' class="error"' : '' ; ?>>
    <td>
        <strong><?php echo $el->getField("name"); ?></strong><br/>
        <?php echo $this->__($el->getField("nameShort")) ; ?>
    </td>
    <td>
        <?php echo $this->__($el->getField("usage")) ; ?>
    </td>
    <td>
        <?php echo $this->__($el->getField("active")) ; ?>
    </td>
    <td>
        <?php echo $this->__($el->getField("numeric")) ; ?>
    </td>
    <?php if (! isset($el->action) || $el->action) { ?>
        <td>
        <?php  
            $this->getLink('edit', array('q'=>'/modules/School Admin/gradeScales_manage_edit.php', 'gibbonScaleID'=>$el->getField("gibbonScaleID")));
            $this->getLink('delete', array('q'=>'/modules/School Admin/gradeScales_manage_delete.php', 'gibbonScaleID'=>$el->getField("gibbonScaleID")));
        ?>
        </td>
    <?php } ?>
</tr>
