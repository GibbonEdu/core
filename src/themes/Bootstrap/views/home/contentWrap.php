            <div id="content-wrap" class="container-fluid">
                <?php
                //Allow for wide pages (no sidebar)
                if ( $this->session->get('sidebar') == "false" ) {
					$this->render('home.contentWide');
                }
				$this->render('home.content'); ?>
                </div><!-- end of ContentWide -->
                <?php $this->render('home.sideBar'); ?>
            </div><!-- close content-wrap -->
