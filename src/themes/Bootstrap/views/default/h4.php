<?php
use Gibbon\core\trans ;
?>
<h4><?php echo trans::__($params->title, isset($params->titleDetails) ? $params->titleDetails : array()); ?></h4><!-- default.h4 -->
