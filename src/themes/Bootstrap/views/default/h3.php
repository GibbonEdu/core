<?php
use Gibbon\core\trans ;
?>
<a name="<?php echo $this->sanitiseAnchor($this->__($el->title, isset($el->titleDetails) ? $el->titleDetails : array())); ?>"></a> 
<h3 id="<?php echo $this->sanitiseAnchor($this->__($el->title, isset($el->titleDetails) ? $el->titleDetails : array())); ?>"><?php echo $this->__($el->title, isset($el->titleDetails) ? $el->titleDetails : array()); ?></h3><!-- default.h3 -->
<?php $this->session->set('pageAnchors.'.$this->sanitiseAnchor($this->__($el->title, isset($el->titleDetails) ? $el->titleDetails : array())), $this->__($el->title, isset($el->titleDetails) ? $el->titleDetails : array()));
