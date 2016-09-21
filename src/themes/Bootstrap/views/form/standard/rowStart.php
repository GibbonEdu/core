<div<?php echo isset($el->row->class) ? ' class="' . $el->row->class . '"' : '' ; ?><?php echo isset($el->row->id) ? ' id="' . $el->row->id . '"' : '' ; ?><?php echo isset($params->row->style) ? ' style="'.$params->row->style . '"' : '' ; ?>>
	<div class="row form-element form-group">
    	<?php if (isset($params->oneColumn) && $params->oneColumn) { ?>
        <div<?php echo isset($el->col2->class) ? ' class="' . $el->col2->class . '"' : ''; ?>>
			<?php echo ! empty($el->nameDisplay) ? '<label for="'.$el->id.'">'.$this->__($el->nameDisplay) . (($el->required || $el->pleaseSelect) ? ' *': '') . '</label>' : '' ; ?>
            <?php echo ! empty($el->description) ? '<p class="help-block"'.(isset($el->span->style) ? ' style="'.$el->span->style.'"' : '').'>'.$this->__($el->description).'</p>' : NULL ; 
        } else { ?>
    	<div<?php echo isset($el->col1->class) ? ' class="' . $el->col1->class . '"' : ''; ?>>
			<?php echo ! empty($el->nameDisplay) ? '<label for="'.$el->id.'">'.$this->__($el->nameDisplay). (($el->required || $el->pleaseSelect) ? ' *': '') . '</label>' : '' ; ?>
            <?php echo ! empty($el->description) ? '<p class="help-block"'.(isset($el->span->style) ? ' style="'.$el->span->style.'"' : '').(isset($el->span->id) ? ' id="'.$el->span->id.'"' : '').'>'.$this->__($el->description).'</p>' : NULL ; ?>
    	</div>
        <div<?php echo isset($el->col2->class) ? ' class="' . $el->col2->class . '"' : ''; ?>> <?php
		} ?><!-- bootstrap.form.standard.rowStart -->