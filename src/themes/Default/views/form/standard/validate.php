<?php $val = $el->insertValidation($el, true);
if (! empty($val)) { ?>
<script type="text/javascript">
    var <?php print $params->id ?> = new LiveValidation('<?php print $params->id ?>');
	<?php echo $val ; ?>
    <?php /* if ($params->validate->Presence) { print $params->id ?>.add(Validate.Presence); <?php echo "\n"; } ?>
    <?php if (isset($params->validate->Email) && $params->validate->Email) { print $params->id ?>.add(Validate.Email); <?php echo "\n"; } ?>
    <?php if (isset($params->validate->Format) && $params->validate->Format) {
        for($x=0; $x<count($params->validate->pattern); $x++) {
			print $params->id ?>.add(Validate.Format, { 
            	pattern: <?php echo '"' . $params->validate->pattern[$x] . '"'; ?>, 
            	failureMessage: <?php echo '"'.  $params->validate->formatMessage[$x] .'"'; ?> 
       	 	} );
    <?php }
	} ?>
    <?php if (isset($params->validate->Numericality) && $params->validate->Numericality) { ?>
        <?php print $params->id ?>.add(Validate.Numericality);
        <?php echo $params->validate->numberMinimum !== NULL ? $params->id . '.add(Validate.Numericality, { minimum: ' .$params->validate->numberMinimum . " } );\n" : '' ; ?>
    <?php } ?>
	<?php if (isset($params->validate->Exclusion) && $params->validate->Exclusion) { ?>
		<?php echo $params->id ?>.add(Validate.Exclusion, { <?php echo isset($params->validate->within) ? "within: ['" . Gibbon\trans::__($params->validate->within) . "']," : '' ; ?> <?php echo isset($params->validate->exclusionMessage) ? 'failureMessage: "' .  Gibbon\trans::__($params->validate->exclusionMessage) . '"' : '' ; ?><?php echo isset($params->validate->exclusionExtras) ? ' '.$params->validate->exclusionExtras : '' ; ?> });
    <?php } ?>
	<?php echo isset($params->validate->disable) && $params->validate->disable ? $params->id.'.disable() ;' : NULL ; ?>
    <?php echo isset($params->validate->Length) ? $params->id.".add( Validate.Length, { minimum: '".$params->validate->minLength."' maximum: '".$params->validate->maxLength."' } );\n" : NULL ; ?>
    <?php echo isset($params->validate->Confirmation) ? $params->id.".add( Validate.Confirmation, { match: '_".$params->validate->Confirmation."' } );\n" : NULL ; */ ?>
</script><!-- form.standard.validate -->
<?php }
