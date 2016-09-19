            <div id="header">
                <div id="header-logo">
                    <a href='<?php print $this->session->get("absoluteURL") ?>'><img height='100px' width='400px' class="logo" alt="Logo" src="<?php print $this->session->get("absoluteURL") . "/" . $this->session->get("organisationLogo") ; ?>"/></a>
                </div>
                <?php 
				$finder =  new Gibbon\core\finder($this);
				$this->render('default.finder.render', $finder->getFastFinder()); ?>
                <div id="header-menu">
                    <?php 
						new Gibbon\Menu\main($this);
                        //Get main menu
                        if (! $this->session->isEmpty("mainMenu")) echo $this->session->get("mainMenu") ;
                    ?>
                </div>
            </div>
