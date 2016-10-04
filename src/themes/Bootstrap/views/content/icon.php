<?php 
echo str_replace(array('{{title}}', 'glyphicons '), array($el->title, $el->class.' glyphicons '), $el->prompt);
