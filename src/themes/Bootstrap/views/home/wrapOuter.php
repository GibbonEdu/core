    <div id="wrapOuter" class="container">
        <?php
		//Show warning if not in the current school year
		if ($this->session->notEmpty("username") && $this->session->get("gibbonSchoolYearID") != $this->session->get("gibbonSchoolYearIDCurrent")) 
			$this->displayMessage(array('%1$sWarning: you are logged into the system in school year %2$s, which is not the current year.%3$sYour data may not look quite right (for example, students who have left the school will not appear in previous years), but you should be able to edit information from other years which is not available in the current year.', array("<strong><u>", $this->session->get("gibbonSchoolYearName"), '</u></strong>')), 'warning');

        if ($this->session->notEmpty("gibbonHouseIDLogo")) {
            print "<div class='minorLinks minorLinksTopGap'>" ;
        } else {
            print "<div class='minorLinks'>" ;
        }
		$x = new Gibbon\Menu\minorLinks($this);
		$menu = $x->setMenu();
		echo empty($menu['content']) ? '' : $menu['content'] ;

        print "</div>" ;
		$this->render('home.wrap');
        ?>
    </div>
