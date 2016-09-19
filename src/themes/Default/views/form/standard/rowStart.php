<tr<?php echo isset($params->row->style) ? ' style="' . $params->row->style . '"' : '' ; ?><?php echo isset($params->row->class) ? ' class="' . $params->row->class . '"' : '' ; ?><?php echo isset($params->row->id) ? ' id="' . $params->row->id . '"' : '' ; ?>>
    <?php if (isset($params->oneColumn) && $params->oneColumn) { ?>
    <td<?php echo isset($params->col2->style) ? ' style="'.$params->col2->style . '"' : '' ; ?><?php echo isset($params->col2->class) ? ' class="'.$params->col2->class . '"' : '' ; ?> colspan='2'>
        <?php $this->render('form.standard.header', $params); ?>
    <?php } else { ?>
    <td<?php echo ! empty($params->col1->style) ? ' style="'.$params->col1->style . '"' : '' ; ?><?php echo ! empty($params->col1->class) ? ' class="'.$params->col1->class . '"' : '' ; ?>> 
        <?php $this->render('form.standard.header', $params); ?>
    </td>
    <td<?php echo ! empty($params->col2->style) ? ' style="'.$params->col2->style . '"' : '' ; ?><?php echo ! empty($params->col2->class) ? ' class="'.$params->col2->class . '"' : '' ; ?>>
    <?php } ?><!-- form.standard.rowStart -->