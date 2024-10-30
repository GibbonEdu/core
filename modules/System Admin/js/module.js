/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

/**
 * Compares two version number strings.
 * @param    string  a
 * @param    string  b
 * @return   Return values:
            - a number < 0 if a < b
            - a number > 0 if a > b
            - 0 if a = b
 */
function versionCompare(a, b) {
    var i, diff;
    var regExStrip0 = /(\.0+)+$/;
    var segmentsA = a.replace(regExStrip0, '').split('.');
    var segmentsB = b.replace(regExStrip0, '').split('.');
    var l = Math.min(segmentsA.length, segmentsB.length);

    for (i = 0; i < l; i++) {
        diff = parseInt(segmentsA[i], 10) - parseInt(segmentsB[i], 10);
        if (diff) {
            return diff;
        }
    }
    return segmentsA.length - segmentsB.length;
}


$(function(){

    $("select.columnOrder").on('change', function(){

        var currentSelection = $(this).val();
        var textBox = $(this).parent().parent().parent().find('input.columnText');

        textBox.prop("readonly", currentSelection != columnDataCustom );
        textBox.prop("disabled", currentSelection != columnDataCustom );

        if ( currentSelection == columnDataFunction ) {
            textBox.val("*generated*");
        } else if ( currentSelection == columnDataCustom ) {
            textBox.val("");
        } else if ( currentSelection == columnDataSkip ) {
            textBox.val("*skipped*");
        } else if ( currentSelection >= 0 ) {
            if ( currentSelection in csvFirstLine ) {
                textBox.val(csvFirstLine[ currentSelection ] );
            } else {
                textBox.val("");
            }
        }
    });
    $("select.columnOrder").change();

	$("#ignoreErrors").click(function() {
		if ($(this).is(':checked')) {
			$(this).val( 1 );
			$("#submitStep3").prop("disabled", false).prop("type", "submit").prop("value", "Submit");
		} else {
			$(this).val( 0 );
			$("#submitStep3").prop("disabled", true).prop("value", "Cannot Continue");
		}
	});
}); 
