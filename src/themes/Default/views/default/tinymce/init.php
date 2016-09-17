<script type="text/javascript">
	tinymce.init({
		selector: "div#editorcontainer textarea",
		width: '738px',
		menubar : false,
		toolbar: 'bold, italic, underline,forecolor,backcolor,|,alignleft, aligncenter, alignright, alignjustify, |, formatselect, fontselect, fontsizeselect, |, table, |, bullist, numlist,outdent, indent, |, link, unlink, image, media, hr, charmap, subscript, superscript, |, cut, copy, paste, undo, redo, fullscreen',
		plugins: 'table, template, paste, visualchars, image, link, template, textcolor, hr, charmap, fullscreen, media',
		statusbar: false,
		valid_elements: '<?php echo $this->config->getSettingByScope("System", "allowableHTML") ?>',
		apply_source_formatting : true,
		browser_spellcheck: true,
		convert_urls: false,
		relative_urls: false
	});
</script>