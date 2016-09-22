            <div id="header" class="container-fluid">
            	<div class="row header-row-1">
                	<div class="col-lg-7">
                        <div id="header-logo">
                            <a href='<?php print $this->session->get("absoluteURL") ?>'><img height='100px' width='400px' class="logo" alt="Logo" src="<?php print $this->session->get("absoluteURL") . "/" . $this->session->get("organisationLogo") ; ?>"/></a>
                        </div>
                    </div>
                	<div class="col-lg-5">
                    	<div id="header-finder">
                    	<?php 
						$this->render('default.finder.render'); ?>
                        </div><!-- home.header.header-finder --> 
                    </div>
                </div>
                <div class="row header-row-2">
                    <div id="header-menu" >
                        <?php 
                            //Get main menu
							new Gibbon\Menu\main($this);
                            echo $this->session->notEmpty("display.menu.main.content") ? $this->session->get("display.menu.main.content") : '' ;
                        ?>
                    </div><!-- home.header.header-menu --> 
                </div>
            </div>
