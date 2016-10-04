<tr>
    <td<?php echo isset($el->col2->style) ? ' style="'.$el->col2->style . '"' : '' ; ?><?php echo isset($el->col2->class) ? ' class="'.$el->col2->class . '"' : '' ; ?> colspan='2'>
    	<span<?php echo isset($el->span->style) ? ' style="'.$el->span->style . '"' : '' ; ?><?php echo isset($el->span->class) ? ' class="'.$el->span->class . '"' : '' ; ?>><?php echo $this->__($el->description); ?></span>
    </td>
</tr>