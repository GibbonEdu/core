<?php /*
$this->addScript("
<script type='text/javascript' language='javascript'>
	$(document).ready(function() {
		$('#finderID')
			.tokenInput(".$params->list.", {
				hintText: '".$this->__('Start typing a name...')."',
				tokenLimit: 1,
				preventDuplicates: true,
				allowCreation: false,
				theme: 'gibbon',
		});
	});
</script>
"); */
$this->addScript("
$('#finderID').tokenfield({
  autocomplete: {
    source: ".$el->list.",
    delay: 100
  },
  showAutocompleteOnFocus: true,
  minWidth: 282,
  limit: 1
});
");
