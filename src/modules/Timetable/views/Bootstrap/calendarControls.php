<tr class='head' style='height: 37px;'>
    <th class='ttCalendarBar' colspan='<?php echo $el->daysInWeek + 1?>'>
    	<?php
		$form = $this->getForm(null, array('q' => (! empty($el->q) ? '?q='.$el->q : '')), false, 'calendarView');
		$form->setStyle('nothing');
		$form->setName('calendarView');
		
		if ($this->session->notEmpty('calendarFeed') && $this->session->notEmpty('googleAPIAccessToken')) {
			$w = $form->addElement('checkbox', 'schoolCalendar', 'Y');
			if ($this->session->get('viewCalendar.School') == 'Y')
				$w->setChecked();
			$w->span->class = 'ttSchoolCalendar';
			$w->span->style = 'opacity: ' . $el->schoolCalendarAlpha;
			$w->nameDisplay = 'School Calendar';
			$w->element->style = 'margin-left: 3px';
			$w->onClickSubmit();
		} else 
			$form->addElement('hidden', 'schoolCalendar', $this->session->get('viewCalendar.School'));
		
		if ($this->session->notEmpty('calendarFeedPersonal') && $this->session->notEmpty('googleAPIAccessToken')) {
			$w = $form->addElement('checkbox', 'personalCalendar', 'Y');
			if ($this->session->get('viewCalendar.Personal') == 'Y')
				$w->setChecked();
			$w->span->class = 'ttPersonalCalendar';
			$w->span->style = 'opacity: ' . $el->schoolCalendarAlpha;
			$w->nameDisplay = 'Personal Calendar';
			$w->element->style = 'margin-left: 3px';
			$w->onClickSubmit();
		} else 
			$form->addElement('hidden', 'personalCalendar', $this->session->get('viewCalendar.Personal'));

		if ($el->spaceBookingAvailable) {
			$w = $form->addElement('checkbox', 'spaceBookingCalendar', 'Y');
			if ($this->session->get('viewCalendar.SpaceBooking') == 'Y')
				$w->setChecked();
			$w->span->class = 'ttSpaceBookingCalendar';
			$w->span->style = 'opacity: ' . $el->schoolCalendarAlpha;
			$w->nameDisplay = array('%1$sBookings%2$s', array("<a style='color: #FFF' href='".GIBBON_URL."index.php?q=/modules/Timetable/spaceBooking_manage.php'>", "</a>"));
			$w->element->style = 'margin-left: 3px';
			$w->onClickSubmit();
		} else 
			$form->addElement('hidden', 'spaceBookingCalendar', $this->session->get('viewCalendar.SpaceBooking'));

		$form->addElement('hidden', 'ttDate', date($this->session->get('i18n.dateFormatPHP'), $el->startDayStamp));

		$form->addElement('hidden', 'fromTT', 'Y');

		$form->render('nothing');
		?>
    </th>
</tr><!-- 'calendarControls' -->