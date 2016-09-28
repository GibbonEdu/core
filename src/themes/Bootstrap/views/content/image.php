<?php echo str_replace(array('{{title}}', '{{gibbonThemeName}}'), array($el->title, $this->session->get('theme.Name')), $el->prompt);
