<?php
use Gibbon\core\trans ;
?>
<a href="<?php echo $params->link; ?>"<?php 
echo ! empty($params->title) ? ' title="'.$this->__($params->title).'"' : ''; 
echo ! empty($el->onclick) ? ' onclick="'.str_replace('"', "'", $el->onclick).'"' : ''; 
echo ! empty($params->linkStyle) ? ' style="'.$params->linkStyle.'"' : ''; 
echo ! empty($params->linkClass) ? ' style="'.$params->linkClass.'"' : ''; ?>><?php
echo ! empty($params->leftName) ? $this->__($params->leftName) : ''; 
echo ! empty($params->name) ? $this->__($params->name) : ''; 
if ( ! empty($params->imageName)) {
?><img<?php
	echo isset($params->imageStyle) ? ' style="'.$params->imageStyle.'"' : ' style="margin-left: 5px"' ; 
	echo isset($params->imageClass) ? ' style="'.$params->imageClass.'"' : NULL ;
	echo isset($params->title) ? ' title="'.$this->__($params->title).'"': '' ; ?> src="<?php echo $this->session->get("theme.url"); ?>/img/<?php echo $params->imageName; ?>" /><?php  
}
echo ! empty($params->rightName) ? $this->__($params->rightName) : ''; ?>
</a>