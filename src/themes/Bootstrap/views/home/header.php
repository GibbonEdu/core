            <div id="header" class="container-fluid">
            	<div class="row">
                	<div class="col-lg-6">
                        <div id="header-logo">
                            <a href='<?php print $this->session->get("absoluteURL") ?>'><img height='100px' width='400px' class="logo" alt="Logo" src="<?php print $this->session->get("absoluteURL") . "/" . $this->session->get("organisationLogo") ; ?>"/></a>
                        </div>
                    </div>
                	<div class="col-lg-6">
                    	<div id="header-finder">
                    	<?php 
                        $finder =  new Gibbon\core\finder($this);
                        $x = $finder->getFastFinder();
						if ($x->output) echo "<p>".Gibbon\core\trans::__('Total Student Enrolment: %d', array($x->studentCount))."</p>" ; ?>
                        </div>  
                    </div>
                </div>
                <div class="row">
                    <div id="header-menu" >
                        <?php 
                            new Gibbon\Menu\main($this);
                            //Get main menu
                            echo  $this->session->notEmpty("mainMenu") ? $this->session->get("mainMenu") : null ;
                        ?>
                    </div>
                </div>
            </div>
