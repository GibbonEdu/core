<tr>
    <td>
        <?php echo $el->original ; ?>
    </td>
    <td>
        <?php echo $el->replacement ; ?>
    </td>
    <td>
        <?php echo $el->mode ; ?>
    </td>
    <td>
        <?php echo $this->__($el->caseSensitive) ; ?>
    </td>
    <td>
        <?php echo $el->priority ; ?>
    </td>
    <?php if (! empty($el->action) || $el->action) { ?>
        <td>
            <?php
            $this->getLink('edit', array('q'=>'/modules/System Admin/stringReplacement_manage_edit.php', 'gibbonStringID' => $el->gibbonStringID, 'search'=>$el->search));
            $this->getLink('delete', array('q'=>'/modules/System Admin/stringReplacement_manage_delete.php', 'gibbonStringID' => $el->gibbonStringID, 'search'=>$el->search));
            ?>
        </td>
    <?php } ?>
</tr>
