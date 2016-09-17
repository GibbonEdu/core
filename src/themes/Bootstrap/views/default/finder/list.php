	<script type='text/javascript' language="javascript">
        $(document).ready(function() {
			$('#finderID')
				.tokenInput(<?php echo $params->list; ?>, {
					hintText: "<?php echo Gibbon\core\trans::__('Start typing a name...');?>",
					tokenLimit: 1,
					preventDuplicates: true,
					allowCreation: false,
					theme: "gibbon",
			});
		});
    </script>
