<?php $el->element->name = 'form.optGroup'; 
$this->render('form.standard.rowStart', $el); 
?><fieldset><?php
if (count($el->options) == 0)
	echo '<em>' . $this->__($el->emptyMessage) . '</em>';
foreach($el->options as $option) 
{ ?>
    <div class="row optGroup" style="border: none; ">
    	<div class="col-md-9 col-lg-9 right input-sm"> <?php
	echo ! empty($option->nameDisplay) ? '<label for="_'.$option->name.'">'.$option->nameDisplay.'</label>&nbsp;' : '' ; ?>
    	</div>
        <div class="col-md-2 col-lg-2">
    		<input type="<?php echo $el->optionType; ?>"<?php echo ! empty($option->value) ? ' value="'.$option->value.'"': '' ; ?><?php echo ! empty($option->name) ? ' name="'.$option->name.'" id="_'.$el->setID($option->name).'"': '' ; ?><?php echo $option->checked ? ' checked': '' ; ?><?php echo ! empty($option->class) ? ' class="'.$option->class.'"': '' ; ?><?php echo ! empty($option->style) ? ' style="'.$option->style.'"': '' ; ?> />
        </div>
	</div>
<?php }
echo $el->script;
?></fieldset><?php
$this->render('form.standard.rowEnd', $el); 
if ($el->checkAll) 
{ ?>
	<script type='text/javascript'>
		$(function () {
			$('.checkAll').click(function () {
				$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
			});
		});
    </script>
	
<?php } ?>
<!-- bootstrap.form.standard.optGroup -->
