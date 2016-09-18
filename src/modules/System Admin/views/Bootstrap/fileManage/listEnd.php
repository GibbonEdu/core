	<?php if ($el->updateRequired) { ?>
		<div class="row">
			<div class="col-lg-offset-10 col-md-offset-10 col-lg-2 col-md-2">
				<?php $x = new \Gibbon\Form\submit('submitBtn', 'Update', $this);
				$x->element->class = 'form-control';
				$this->render('form.submit', $x); ?>
			</div>      
		</div>
	<?php } ?>
</div><!-- close gibbon-container -->
