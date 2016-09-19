<?php 
if (!empty($el->slider)){
$this->render('default.slider.start');
echo $el->slider;
$this->render('default.slider.end');
}
