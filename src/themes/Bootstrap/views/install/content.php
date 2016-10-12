<div id='content' class='col-lg-9 col-md-9'>
    <?php
        //Get and set database variables (not set until step 1)
        $el->dbHost = isset($_POST["databaseServer"]) ? $_POST["databaseServer"] : '';


        $el->dbName = isset($_POST["databaseName"]) ? $_POST["databaseName"] : '';

        $el->dbUser = isset($_POST["databaseUsername"]) ? $_POST["databaseUsername"] : '';

        $el->dbPWord = isset($_POST["databasePassword"]) ? $_POST["databasePassword"] : '';

        $el->demoData = isset($_POST["demoData"]) ? $_POST["demoData"] : '';

        $el->dbPrefix = isset($_POST["dbPrefix"]) ? $_POST["dbPrefix"] : '';
      
        $this->h2(array('Installation - Step %1$s', array($el->step + 1)));
        
        //Set language
        $el->code = isset($_POST["code"]) ? $_POST["code"] : "en_GB" ;
  
        if ($el->step==0) { //Choose language
            $this->render('install.step0', $el);
        }
        elseif ($el->step==1) { //Set database options
            $this->render('install.step1', $el);
        }
        elseif ($el->step==2) {
            $this->render('install.step2', $el);
        }
        elseif ($el->step==3) {
            $this->render('install.step3', $el);
        }
    
    ?>
</div><!-- install.content -->	
