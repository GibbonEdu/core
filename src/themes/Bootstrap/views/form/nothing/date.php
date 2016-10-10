<?php $this->render('form.text', $el);
if (! empty($el->validate)) {
	if (! empty($el->validate->dateBefore))
		$el->validate->dateBefore = "max: '".date('Y-m-d', strtotime(str_replace('/','-',$el->validate->dateBefore)))."',"; 
	else
		$el->validate->dateBefore = '';
	if (! empty($el->validate->dateAfter))
		$el->validate->dateAfter = "min: '".date('Y-m-d', strtotime(str_replace('/','-',$el->validate->dateAfter)))."',"; 
	else
		$el->validate->dateAfter = ''; 
	$this->addScript('
	<script>
	$(document).ready(function() {
		$("#'.$el->formID.'")
			.formValidation({
				fields: {
					"'.$el->name.'": {
						validators: {
							date: {
								format: "'.strtoupper($el->validate->dateFormat).'",
								//  Before and After
								'.$el->validate->dateAfter . $el->validate->dateBefore.'
								message: "'.$el->validate->dateMessage.'"
							}
						}
					}
				}	
			})
			.find(\'[name="'.$el->name.'"]\')
				.datepicker({
					format: "'.$el->validate->dateFormat.'",
					onSelect: function(date, inst) {
						// Revalidate the field when choosing it from the datepicker
						$("#'.$el->formID.'").formValidation("revalidateField", "'.$el->name.'");
					}
			 });
	});
	</script>
	'); 
} 
$this->addScript('
<script type="text/javascript">
	$( "#'.(isset($el->id) ? $el->id : $el->name).'" ).datepicker();
</script>
');
