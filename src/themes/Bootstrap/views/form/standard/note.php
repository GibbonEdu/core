<div<?php echo isset($el->row->class) ? ' class="'.$el->row->class . '"' : '' ; ?><?php echo isset($el->row->style) ? ' style="'.$el->row->style . '"' : '' ; ?><?php echo isset($el->row->id) ? ' id="'.$el->row->id . '"' : '' ; ?>>
	<div class="row form-element form-group">
        <div<?php echo isset($el->col2->class) ? ' class="'.$el->col2->class . '"' : '' ; ?><?php echo isset($el->col2->style) ? ' style="'.$el->col2->style . '"' : '' ; ?>>
            <?php echo isset($el->title) ? '<strong>'.$this->__($el->title, $el->titleParameters).'</strong><br />' : '' ;?>
            <div<?php echo isset($el->span->style) ? ' style="'.$el->span->style . '"' : '' ; ?><?php echo isset($el->span->class) ? ' class="'.$el->span->class . '"' : '' ; ?>><?php echo isset($el->value) ? $this->__($el->value, $el->valueParameters) : '' ;?></div>
        </div>
    </div>
</div><!-- bootstrap.form.standard.note -->