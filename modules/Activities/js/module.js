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

	// Matches the width of the top placeholder to the final table width
	$(window).on('load', function (e) {
	    $('.doublescroll-top-tablewidth').width($('.doublescroll-container table').width());
	});
	
	// Pairs the position of the top scrollbar with the bottom scrollbar
    $(".doublescroll-top").scroll(function(){
        $(".doublescroll-container")
            .scrollLeft($(".doublescroll-top").scrollLeft());
    });
    $(".doublescroll-container").scroll(function(){
        $(".doublescroll-top")
            .scrollLeft($(".doublescroll-container").scrollLeft());
    });

	
	// Fills the column with checkboxes to create the attenance formdata (naming pattern --> $_POST data)
    $("a.editColumn").click( function(){
    	var editing = $(this).parent().data('editing');

    	if (!editing || editing == false) {
    		$(this).parent().data('editing', true);

    		var date = $(this).data('date');
	    	var column = $(this).data('column');
	    	var checkedDefault = $(this).data('checked');

	    	var rows = $(this).parents('table').find("td.col" + column).each(function(){
	    		
	    		var checked = ( $(this).html() != "")? "checked" : checkedDefault;
		    	$(this).html("<input type='checkbox' name='attendance["+ column +"]["+ $(this).parent().data('student') +"]' "+ checked +">");
		    	$(this).addClass('highlight');
		    });

			$(this).parent().parent().addClass('highlight');
			$(this).parent().parent().append("<input type='hidden' name='sessions["+ column +"]' value='" + date + "'>");

		    $(this).addClass('hidden');
			$(this).parent().parent().find('.clearColumn').removeClass('hidden');
	    }

    } );

    // Clears the column checkboxes
    $("a.clearColumn").click(function(){
    	
    	if (confirm("Are you sure you want to clear the attendance recorded for this date?")) {

    		$(this).parent().data('editing', false);

	    	var column = $(this).data('column');
			var rows = $(this).parent().parents('table').find("td.col" + column).each(function(){
	    		$(this).html("<input name='attendance["+ column +"]["+ $(this).parent().data('student') +"]' type='checkbox'>");
	    	});

	    	$(this).addClass('hidden');
			$(this).parent().parent().find('.editColumn').removeClass('hidden');
			$(this).parent().parent().find('.addColumn').removeClass('hidden');
	    }
    });


}); 