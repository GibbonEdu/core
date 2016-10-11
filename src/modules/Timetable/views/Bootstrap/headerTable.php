<?php if ($el->title !== false) $this->h3($el->record->name);
if ($params->narrow == 'trim') 
	$x = '700px';
elseif ($params->narrow == 'narrow') 
	$x = '525px';
else 
	$x = '750px';
?>
<table cellspacing='0' class='noIntBorder' style='width: <?php echo $x; ?> ; margin: 10px 0 10px 0'>
	<tbody>
        <tr class="info">
            <td style='vertical-align: top'>
            	<?php
				$form = $this->getForm(null, array('q'=>! empty($q) ? $q : '', 'gibbonTTID'=>$el->record->gibbonTTID.$el->params), false);
				$form->setStyle('nothing');
				$form->setName('lastWeek');
				
				$w = $form->addElement('hidden', 'ttDate', date($this->session->get('i18n.dateFormatPHP'), ($el->startDayStamp - (7 * 24 * 60 * 60))));
				$w->setID('lastWeekTTDate');
				$form->addElement('hidden', 'schoolCalendar', $this->session->get('viewCalendarSchool'));
				$form->addElement('hidden', 'personalCalendar', $this->session->get('viewCalendarPersonal'));
				$form->addElement('hidden', 'spaceBookingCalendar', $this->session->get('viewCalendarSpaceBooking'));
				$form->addElement('hidden', 'fromTT', 'Y');
				
				$w = $form->addElement('submitBtn', null, 'Last Week');
				$w->element->class = 'buttonLink';
				$w->element->style = 'min-width: 30px; margin-top: 0px; float: left;';
				
				$form->render('nothing');
				?>
            	<?php
				$form = $this->getForm(null, array('q'=>! empty($q) ? $q : '', 'gibbonTTID'=>$el->record->gibbonTTID.$el->params), false);
				$form->setStyle('nothing');
				$form->setName('nextWeek');
				
				$w = $form->addElement('hidden', 'ttDate', date($this->session->get('i18n.dateFormatPHP'), ($el->startDayStamp + (7 * 24 * 60 * 60))));
				$w->setID('nextWeekTTDate');
				$form->addElement('hidden', 'schoolCalendar', $this->session->get('viewCalendarSchool'));
				$form->addElement('hidden', 'personalCalendar', $this->session->get('viewCalendarPersonal'));
				$form->addElement('hidden', 'spaceBookingCalendar', $this->session->get('viewCalendarSpaceBooking'));
				$form->addElement('hidden', 'fromTT', 'Y');
				
				$w = $form->addElement('submitBtn', null, 'Next Week');
				$w->element->class = 'buttonLink';
				$w->element->style = 'min-width: 30px; margin-top: 0px; float: left;';
				
				$form->render('nothing');
				?>
            
            </td>
            <td style='vertical-align: top; text-align: right'>
            	<?php
				$form = $this->getForm(null, array('q'=>! empty($q) ? $q : '', 'gibbonTTID'=>$el->record->gibbonTTID.$el->params), false);
				$form->setStyle('nothing');
				$form->setName('ttDateForm');
				
				$w = $form->addElement('date', 'ttDate', $el->startDayStamp);
				$w->setID('dateFormttDate');
				$w->element->style = 'height: 22px; width:100px; margin-right: 0px; float: none;';
				$w->setFormID('ttDateForm');
				
				$form->addElement('hidden', 'schoolCalendar', $this->session->get('viewCalendarSchool'));
				$form->addElement('hidden', 'personalCalendar', $this->session->get('viewCalendarPersonal'));
				$form->addElement('hidden', 'spaceBookingCalendar', $this->session->get('viewCalendarSpaceBooking'));
				$form->addElement('hidden', 'fromTT', 'Y');
				
				$w = $form->addElement('submitBtn', null, 'Go');
				$w->element->style = 'margin-top: 0px; margin-right: -2px;';
				
				$form->render('nothing');
				?>
            </td>
        </tr>
    </tbody>
</table>
