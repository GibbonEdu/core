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
                        new Gibbon\Menu\main($this);
                        $x = $this->session->get("display.studentFastFinder");
						if (isset($x->output) && $x->output) echo "<p>".Gibbon\core\trans::__('Total Student Enrolment: %d', array($x->studentCount))."</p>" ; ?>
                        </div><!-- home.header.hedaer-finder --> 
                    </div>
                </div>
                <div class="row">
                    <div id="header-menu" >
                        <?php 
                            //Get main menu
                            echo  $this->session->notEmpty("display.menu.main.content") ? $this->session->get("display.menu.main.content") : '' ;
                        ?>
                    </div><!-- home.header.hedaer-menu --> 
                </div>
            </div>
