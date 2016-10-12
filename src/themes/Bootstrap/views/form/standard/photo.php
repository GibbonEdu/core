<?php 
$el->element->name = 'form.photo';
$this->render('form.standard.rowStart', $el);
echo isset($el->deletePhoto) ? $this->__($el->deletePhoto) : '' ;
$this->render('form.file', $el);
$this->render('form.standard.rowEnd', $el); 
?><!-- form.standard.photo -->