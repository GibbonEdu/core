<?php 
use Gibbon\core\trans ;
echo '<'.$el->get('type'). ($el->isEmpty('listClass') ? '' : ' class="'.$el->get('listClass').'"').'>';
echo $el->isEmpty('listHeader') ? '' : $el->get('listHeader') ;
foreach( $el->get('messages') as $message)
{
	if ($el->get('type') !== 'dl') 
	{
	?><li<?php echo isset($message[2]) ? ' class="'.$message[2].'"' : '' ; ?>><?php echo $this->__($message[0], $message[1]); ?></li><?php
	}
	else
	{
	?><dt<?php echo isset($message[2]['dt']) ? ' class="'.$message[2]['dt'].'"' : '' ; ?>><?php echo $this->__($message[0]['dt'], $message[1]['dt']); ?></dt>
	<dd<?php echo isset($message[2]['dd']) ? ' class="'.$message[2]['dd'].'"' : '' ; ?>><?php echo $this->__($message[0]['dd'], $message[1]['dd']); ?></dd><?php
	}
}
echo '</'.$el->get('type').'>';