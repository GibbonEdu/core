<a href="<?php echo $el->link; ?>"<?php 
echo ! empty($el->title) ? ' alt="'.$this->__($el->title).'"' : ''; 
echo ! empty($el->onclick) ? ' onclick="'.str_replace('"', "'", $el->onclick).'"' : ''; 
echo ! empty($el->linkStyle) ? ' style="'.$el->linkStyle.'"' : ''; 
echo ! empty($el->linkClass) ? ' style="'.$el->linkClass.'"' : ''; ?>><?php
echo ! empty($el->leftName) ? $this->__($el->leftName) : ''; 
echo ! empty($el->name) ? $this->__($el->name) : ''; 
if ( ! empty($el->imageName)) {
?><img<?php
	echo isset($el->imageStyle) ? ' style="'.$el->imageStyle.'"' : ' style="margin-left: 5px"' ; 
	echo isset($el->imageClass) ? ' class="'.$el->imageClass.'"' : NULL ;
	echo (isset($el->title) ? $this->__($el->title): ''); ?> src="<?php echo $this->session->get("theme.url"); ?>/img/<?php echo $el->imageName; ?>" /><?php  
}
echo ! empty($el->rightName) ? $this->__($el->rightName) : ''; ?>
</a>