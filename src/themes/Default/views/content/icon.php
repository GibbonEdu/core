<?php echo str_replace(array('{{gibbonThemeName}}', '{{title}}', 'title='), array($this->session->get('theme.Name'), $el->oldTitle, $el->oldClass . ' title='), $el->prompt);
