<?php
use Gibbon\core\module ;
use Gibbon\core\trans ;
$this->render('default.header');

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>
            <?php 
            print $this->session->get("organisationNameShort") . " - " . $this->session->get("systemName") ;
            if ($this->session->notEmpty("address")) {
                if (strstr($this->session->get("address"),"..")==FALSE) {
                    if ( module::getModuleName($this->session->get("address"))!="" ) {
                        print " - " . trans::__( module::getModuleName($this->session->get("address")) ) ;
                    }
                }
            }
            ?>
        </title>
        <meta charset="utf-8"/>
        <meta name="author" content="Ross Parker, International College Hong Kong"/>
        <meta name="robots" content="none"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
		<?php $this->render('home.scripts'); ?>
        <?php $this->render('home.style'); ?>
        
        <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" />

        <?php
        
        //Analytics setting
        echo $this->session->notEmpty("analytics") ? $this->session->get("analytics") : NULL ;
        ?>
    </head>
