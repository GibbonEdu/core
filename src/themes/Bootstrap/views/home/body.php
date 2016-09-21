<body>
    <?php
	$this->session->loadLogo();
    
    $this->render('home.wrapOuter');
	//and now inserted Scripts.
	foreach($this->session->get('scripts') as $script)
	{?><script type="text/javascript">
	<?php
		echo $script;
	?></script>
	<?php
	}
$this->session->clear('scripts');
    ?>
</body>
