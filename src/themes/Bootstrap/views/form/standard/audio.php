<?php 
$el->element->name = 'form.audio';
$this->render('form.standard.rowStart', $el);
echo isset($el->currentAttachment) ? Gibbon\core\trans::__($el->currentAttachment) : '' ;
$this->render('form.file', $el);
$this->render('form.standard.rowEnd', $el); 
?><!-- form.standard.audio -->