<?php if (isset($el->nameDisplay)) { ?>
	 <strong><?php echo Gibbon\core\trans::__( $el->nameDisplay);
	 echo ($el->required || $el->pleaseSelect) ? ' *' : '';
	 ?></strong>
     
<?php
}
if (isset($params->description)) { ?>
	<br/><span<?php echo isset($params->span->style) ? ' style="'.$params->span->style.'"' : '' ; ?><?php echo isset($params->span->id) ? ' id="'.$params->span->id.'"' : '' ; ?><?php echo isset($params->span->class) ? ' class="'.$params->span->class.'"' : '' ; ?>>
	<?php echo Gibbon\core\trans::__( $params->description); ?></span>
<?php } ?><!-- form.standard.header -->
