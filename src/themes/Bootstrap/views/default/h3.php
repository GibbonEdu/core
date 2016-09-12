<?php
use Gibbon\core\trans ;
use Gibbon\core\helper ;
?>
<a name="<?php echo helper::sanitiseAnchor(trans::__($params->title, isset($params->titleDetails) ? $params->titleDetails : array())); ?>"></a>
<h3><?php echo trans::__($params->title, isset($params->titleDetails) ? $params->titleDetails : array()); ?></h3><!-- default.h3 -->
<?php $this->session->set('pageAnchors.'.helper::sanitiseAnchor(trans::__($params->title, isset($params->titleDetails) ? $params->titleDetails : array())), trans::__($params->title, isset($params->titleDetails) ? $params->titleDetails : array()));
