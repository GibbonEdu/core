<?php
use Gibbon\Form\radio ;
?>
<tr <?php echo $el['active'] == 'N' ? 'class="warning"': '';?>>
    <td>
    <strong><?php echo $this->__($el['name']); ?></strong>
    </td>
    <td>
    <?php echo $el['code']; ?>
    </td>
    <td class="center">
    <?php echo $this->__($el['active']); ?>
    </td>
    <td>
    <?php if (! empty($el['maintainer']['website'])) { ?>
        <a href='<?php echo $el['maintainer']['website'];?>'><?php echo $el['maintainer']['name'];?></a>
    <?php } else { 
        echo $el['maintainer']['name'];
    } ?>
    </td>
    <td class="center">
    <?php 
    if ($el['active'] =='Y') {
        $rad = new radio('gibboni18nCode', $this->config->getSettingByScope('System', 'defaultLanguage', 'en_GB'), $this); 
        $rad->addOption($el['code']);
        if ($this->config->getSettingByScope('System', 'defaultLanguage') == $el['code'])
            $rad->setChecked();
        $rad->display = reset($rad->options);
        $this->render('form.radio', $rad);
    } ?>
    </td>
</tr>
