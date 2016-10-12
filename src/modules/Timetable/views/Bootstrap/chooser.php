<?php
$form = $this->getForm(null, array('q'=> ! empty($q) ? $q : "", 'gibbonTTID'=>$el->row->getField('gibbonTTID').$el->params), false);
$form->setStyle('nothing');

$w = $form->addElement('hidden', 'ttDate', date($this->session->get('i18n.dateFormatPHP'), $el->startDayStamp));
$w->setID($el->row->getField('name').'ttDate');
$form->addElement('hidden', 'schoolCalendar', $this->session->get('viewCalendar.School'));
$form->addElement('hidden', 'personalCalendar', $this->session->get('viewCalendar.Personal'));
$form->addElement('hidden', 'spaceBookingCalendar', $this->session->get('viewCalendar.SpaceBooking'));
$form->addElement('hidden', 'fromTT', 'Y');

$w = $form->addELement('submit', null, $el->row->getField('name'));
$w->element->style = 'min-width: 30px; margin-top: 0px; float: left;';

$form->render();
