<?php
use Gibbon\core\trans ;
?>
<a href="<?php echo $params->link; ?>"<?php 
echo ! empty($params->title) ? ' title="'.trans::__($params->title).'"' : ''; 
echo ! empty($el->onclick) ? ' onclick="'.str_replace('"', "'", $el->onclick).'"' : ''; 
echo ! empty($params->linkStyle) ? ' style="'.$params->linkStyle.'"' : ''; 
echo ! empty($params->linkClass) ? ' style="'.$params->linkClass.'"' : ''; ?>><?php
echo ! empty($params->leftName) ? trans::__($params->leftName) : ''; 
echo ! empty($params->name) ? trans::__($params->name) : ''; 
if ( ! empty($params->imageName)) {
?><img<?php
	echo isset($params->imageStyle) ? ' style="'.$params->imageStyle.'"' : ' style="margin-left: 5px"' ; 
	echo isset($params->imageClass) ? ' style="'.$params->imageClass.'"' : NULL ;
	echo isset($params->title) ? ' title="'.trans::__($params->title).'"': '' ; ?> src="<?php echo $this->session->get("theme.url"); ?>/img/<?php echo $params->imageName; ?>" /><?php  
}
echo ! empty($params->rightName) ? trans::__($params->rightName) : ''; ?>
</a>