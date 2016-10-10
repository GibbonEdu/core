<?php
$el->showMedia = isset($el->showMedia) ? $$el->showMedia : false ;
$el->allowUpload = isset($el->allowUpload) ? $el->allowUpload : true ;
$el->required = isset($el->required) ? $el->required : false ;
$el->initiallyHidden = isset($el->initiallyHidden) ? $el->initiallyHidden : false;
$el->initialFilter = isset($el->initialFilter) ? $el->initialFilter : '';
$el->resourceAlphaSort = isset($el->resourceAlphaSort) ? $el->resourceAlphaSort : false;
$el->rows = isset($el->rows) ? $el->rows : 10;
$el->tinymceInit = isset($el->tinymceInit) ? $el->tinymceInit : true ;

$this->render('form.editor.toolbar', $el);
$this->render('form.editor.required', $el);
$this->render('default.tinymce.init', $el);

$this->addScript('
<script type="text/javascript">
	// Prevent Bootstrap dialog from blocking focusin
	$(document).on("focusin", function(e) {
		if ($(e.target).closest(".mce-window").length) {
			e.stopImmediatePropagation();
		}
	});
</script>
'); ?><!-- bootstrap.form.editor -->