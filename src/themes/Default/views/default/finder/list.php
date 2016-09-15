<script type='text/javascript'>
	$(document).ready(function() {
		$("#<?php echo isset($params->id) ? $params->id : 'id'; ?>")
			.tokenInput(<?php echo $params->list; ?>, {
				theme: "facebook",
				hintText: "Start typing a name...",
				allowCreation: false,
				preventDuplicates: true,
				tokenLimit: 1
		});
	});
</script>
