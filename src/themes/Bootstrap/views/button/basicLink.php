<a href="<?php echo $el->link; ?>"<?php 
echo ! empty($el->title) ? ' title="'.Gibbon\core\trans::__($el->title).'"' : ''; 
echo ! empty($el->onclick) ? ' onclick="'.str_replace('"', "'", $el->onclick).'"' : ''; 
echo ! empty($el->linkStyle) ? ' style="'.$el->linkStyle.'"' : ''; 
echo ! empty($el->linkClass) ? ' class="'.$el->linkClass.'"' : ''; ?>><?php 
echo ! empty($el->leftName) ? Gibbon\core\trans::__($el->leftName) : ''; 
echo ! empty($el->name) ? Gibbon\core\trans::__($el->name) : ''; 
if (! empty($el->spanClass)) {
	?><span class="<?php echo $el->spanClass; ?>"<?php 
	echo isset($el->imageStyle) ? ' style="'.$el->imageStyle.'"' : NULL ; 
	echo isset($el->imageClass) ? ' class="'.$el->imageClass.'"' : NULL ; ?>></span><?php 
}
echo ! empty($el->rightName) ? Gibbon\core\trans::__($el->rightName) : ''; ?>
</a><!-- bootstrap.button.basicLink -->