<?php
use Gibbon\Menu\minorLinks;
?>
    <div id="wrapOuter">
        <?php

		//Show warning if not in the current school year
		if ($this->session->notEmpty("username")) {
			if ($this->session->get("gibbonSchoolYearID")!=$this->session->get("gibbonSchoolYearIDCurrent")) {
				print "<div class='warning'>" ;
					echo Gibbon\trans::__('%1$sWarning: you are logged into the system in school year %2$s, which is not the current year.%3$sYour data may not look quite right (for example, students who have left the school will not appear in previous years), but you should be able to edit information from other years which is not available in the current year.', array("<strong><u>", $this->session->get("gibbonSchoolYearName"), "</strong></u><br/>")) ;
				print "</div>" ;
			}
		}

        if ($this->session->isEmpty("gibbonHouseIDLogo")) {
            print "<div class='minorLinks minorLinksTopGap'>" ;
        } else {
            print "<div class='minorLinks'>" ;
        }
		$x = new minorLinks($this);
		echo $x->setMenu();
        print "</div>" ;
		$this->render('home.wrap');
        ?>
    </div>
