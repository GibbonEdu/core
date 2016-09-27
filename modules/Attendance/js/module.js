/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

function getDate() {
	// GET CURRENT DATE
	var date = new Date();
 
	// GET YYYY, MM AND DD FROM THE DATE OBJECT
	var yyyy = date.getFullYear().toString();
	var mm = (date.getMonth()+1).toString();
	var dd  = date.getDate().toString();
 
	// CONVERT mm AND dd INTO chars
	var mmChars = mm.split('');
	var ddChars = dd.split('');
 
	// CONCAT THE STRINGS IN YYYY-MM-DD FORMAT
	var datestring = yyyy + '-' + (mmChars[1]?mm:"0"+mmChars[0]) + '-' + (ddChars[1]?dd:"0"+ddChars[0]);
	
	return datestring ;
}

jQuery(function($){

	// Update reasons on Attendance type selected
	$('select[name$="-type"]').change( function() {
		var reason = $(this).next('select[name$="-reason"]');

		// Auto-select Unexcused for Absent attendance
		if ( $(this).val() == 'Absent' && reason.val() == ''  ) {
			reason.val("Unexcused");
		}

		// Auto-clear reasons for Present attendance
		else if ( $(this).val() == 'Present' ) {
			reason.val("");
		}
		
	});

	// Disallow blank reasons for Absent attendance
	$('select[name$="-reason"]').change( function() {
		if ( $(this).val() == ''  ) {
			if ( $(this).prev('select[name$="-type"]').val() == 'Absent' ) {
				alert('If the reason for an absence is unknown please select Unexcused.\n\nThe attendence may be updated later once the absence has been excused.');
				$(this).val("Unexcused");
			}
		}
	});

	$('#set-all').click( function() {
		$('select[name$="-type"]').val(  $('select[name="set-all-type"]').val() );
		$('select[name$="-reason"]').val(  $('select[name="set-all-reason"]').val() );
		$('input[name$="-comment"]').val(  $('input[name="set-all-comment"]').val() );
		$('#set-all-note').show();
	});
});