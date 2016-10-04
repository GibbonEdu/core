<?php
$this->render('default.header');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>
            <?php
            print $this->session->get("organisationNameShort") . " - " . $this->session->get("systemName") ;
            if ($this->session->notEmpty("address")) {
                if (! strstr($this->session->get("address"),"..")) {
                    if ( $this->getModuleName($this->session->get("address"))!="" ) {
                        echo " - " . $this->__( $this->getModuleName($this->session->get("address")) ) ;
                    }
                }
            }
            ?>
        </title>
        <meta charset="utf-8"/>
        <meta name="author" content="Ross Parker, International College Hong Kong"/>
        <meta name="robots" content="none"/>
<?php $this->render('home.scripts'); ?>
<?php $this->render('home.style'); ?>

        <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" />


        <?php
        //Analytics setting
        echo $this->session->notEmpty("analytics") ? $this->session->get("analytics") : '' ;
        ?>
    </head>
