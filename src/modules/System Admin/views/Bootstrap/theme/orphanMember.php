<tr class="<?php echo $el->rowNum; ?>">
    <td>
        <?php echo Gibbon\core\trans::__($el->themeName) ; ?>
    </td>
    <td>
        <?php $this->getLink('uninstall', GIBBON_URL. '/index.php?q=/modules/System Admin/theme_manage_uninstall.php&gibbonThemeID='.$el->themeID,'&orphaned=true'); ?>
    </td>
</tr>
