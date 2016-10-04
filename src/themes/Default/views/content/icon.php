<?php echo str_replace(array('{{gibbonThemeName}}', 'title'), array($this->session->get('theme.Name'), $this->__($el->title)), $el->prompt);
