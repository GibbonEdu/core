<div class="container-fluid form-group">
    <div<?php echo isset($el->row->class) ? ' class="' . $el->row->class . '"' : '' ; ?>> 
        <div class="row form-element form-group">
            <div<?php echo isset($el->col2->style) ? ' style="'.$el->col2->style . '"' : '' ; ?><?php echo isset($el->col2->class) ? ' class="'.$el->col2->class . '"' : ' class="col-lg-12 col-md-12"' ; ?>>
            	<span<?php echo isset($el->span->style) ? ' style="'.$el->span->style . '"' : '' ; ?><?php echo isset($el->span->class) ? ' class="'.$el->span->class . '"' : '' ; ?>><?php echo Gibbon\core\trans::__($el->description); ?></span>
            </div>
        </div>
    </div>
</div><!-- bootstrap.form.login.note -->