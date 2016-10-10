<?php
$el->id = isset($el->id) ? $el->id : 'id';
$this->addScript('
<script >
	$(document).ready(function() {
		$("#'.$el->id.'")
			.tokenInput('.$el->list.', {
				theme: "facebook",
				hintText: "Start typing a name...",
				allowCreation: false,
				preventDuplicates: true,
				tokenLimit: 1
		});
	});
</script>
');
