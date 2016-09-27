<?php if ($el->required) { 
$this->session->set('form.validation.extra', '{
		excluded: [":disabled"],
		fields: {
			'.$el->id.': {
				validators: {
					callback: {
						message: "'.$this->__('Entry Required!').'",
						callback: function(value, validator, $field) {
							// Get the plain text without HTML
							var text = tinyMCE.activeEditor.getContent({
								format: "text"
							});
							var text = $.trim(text);
							return text.length >= 1;
						}
					}
				}
			}
		}
	}');
}
