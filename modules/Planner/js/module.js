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

jQuery(function($){

	// Show the reason/comment when the attendance dropdown is changed
	$('select[name$="-type"]').change( function() {

		// Get the data-id from the preceding hidden input
		var id = $(this).prev('input').data('id');

		// Show/hide the div container
		$('#'+id+'-hideReasons').show();
    });

});

// Handle checkbox toggles for student homework
$(document).on('click', '.mark-complete', function () {
    var complete = $(this).is(":checked");
    $(this).parent().parent().toggleClass('success', complete);

    $.ajax({
        url: './modules/Planner/planner_deadlinesAjax.php',
        data: {
            complete: complete ? 'Y' : 'N',    
            type: $(this).data('type'),    
            gibbonPlannerEntryID: $(this).data('id')
        },
        type: 'POST',
        success: function(data) {
            if (data == 'error0') {
                window.location = location.href += "&return=error0";
            } else if (data == 'error1') {
                window.location = location.href += "&return=error1";
            }
        },
    });
});
