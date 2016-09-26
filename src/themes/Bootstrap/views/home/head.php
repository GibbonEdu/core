<?php
$this->render('default.header');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>
            <?php 
            echo $this->session->get("organisationNameShort") . " - " . $this->session->get("systemName") ;
			$address = $this->session->get("address");
            if (! empty($address)) {
                if (strstr($address,"..") == false) {
                    if ($this->getModuleName($address)!="" ) {
                        echo " - " . $this->__($this->getModuleName($address)) ;
                    }
                }
            }
            ?>
        </title>
        <meta charset="utf-8"/>
        <meta name="author" content="Ross Parker, International College Hong Kong"/>
        <meta name="robots" content="none"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
		<?php 
		$this->render('home.scripts'); 
        $this->render('home.style'); 
		?>
        
        <link rel="shortcut icon" type="image/x-icon" href="./favicon.ico" />

        <?php
        
        //Analytics setting
        echo $this->session->notEmpty("analytics") ? $this->session->get("analytics") : NULL ;
        ?>
    </head>
