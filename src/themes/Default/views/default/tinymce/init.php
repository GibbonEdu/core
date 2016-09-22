<?php
$this->addScript("
<script>
	tinymce.init({
		selector: 'div#editorcontainer textarea',
		width: '738px',
		menubar : false,
		toolbar: 'bold, italic, underline,forecolor,backcolor,|,alignleft, aligncenter, alignright, alignjustify, |, formatselect, fontselect, fontsizeselect, |, table, |, bullist, numlist,outdent, indent, |, link, unlink, image, media, hr, charmap, subscript, superscript, |, cut, copy, paste, undo, redo, fullscreen',
		plugins: 'table, template, paste, visualchars, image, link, template, textcolor, hr, charmap, fullscreen, media',
		statusbar: false,
<<<<<<< HEAD
		valid_elements: '<?php echo $this->config->getSettingByScope('System', 'allowableHTML') ?>',
=======
		valid_elements: '".$this->config->getSettingByScope('System', 'allowableHTML')."',
>>>>>>> 9f852d0fb1c6b3799f833bd1593409785cc98f71
		apply_source_formatting : true,
		browser_spellcheck: true,
		convert_urls: false,
		relative_urls: false
	});
</script>
");
