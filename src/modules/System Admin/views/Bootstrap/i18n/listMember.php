<?php
use Gibbon\core\trans ;
?>
        <tr <?php echo $el['active'] == 'N' ? 'class="warning"': '';?>>
            <td>
            <strong><?php echo $this->__($el['name']); ?></strong>
            </td>
            <td>
            <?php echo $el['code']; ?>
            </td>
            <td>
            <?php echo $this->__($el['active']); ?>
            </td>
            <td>
            <?php echo $el['version']; ?>
            </td>
            <td class="centre">
            <?php if (! empty($el['update'])) 
            {
                $x = new Gibbon\Form\checkbox('update[]', $el['code']);
                $x->id = 'update_'.$el['update'];
				$x->onClickSubmit();
                $x->label = $el['update'] ;
                $this->render('form.checkbox', $x);
            } ?>
            </td>
            <td>
            <?php if (! empty($el['maintainer']['website'])) { ?>
                <a href='<?php echo $el['maintainer']['website'];?>'><?php echo $el['maintainer']['name'];?></a>
            <?php } else { 
                echo $el['maintainer']['name'];
            } ?>
            </td>
            <td>
            <?php 
            if ($el['active'] =='Y') {
                $rad = new Gibbon\Form\radio('gibboni18nCode', $this->config->getSettingByScope('System', 'defaultLanguage', 'en_GB'), $this); 
				$rad->addOption($el['code']);
				if ($this->config->getSettingByScope('System', 'defaultLanguage') == $el['code'])
					$rad->setChecked();
                $rad->hideDisplay = true;
                $this->render('form.radio', $rad);
            } 			?>
            </td>
        </tr>
