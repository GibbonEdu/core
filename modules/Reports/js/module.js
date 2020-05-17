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

// Character counter for comment fields
$(function() {
    $('.characterCount').each(function () {
        updateComments(this);
    });

    $(document).on('input', '.characterCount', function () {
        updateComments(this);
    });

    $(document).on('paste', '.characterCount', function () {
        var element = this;
        setTimeout(function() { 
            updatePlaceholders(element);
            updateComments(element);
        }, 0);
    });

    $(document).on('change', '.auto-submit', function () {
        $(this).parents('form').submit();
    });
});


function updateComments(element)
{
    // if ($('#complete:checked').length <= 0) return;
    // console.log('checking');

    var commentText = $(element).val();

    // Update character counter for comment length
    var currentLength = commentText.length;
    $('.characterInfo .currentLength', $(element).parent()).html(currentLength);

    // Look for the student's first name somewhere in the comment
    var preferredName = $(element).data('name') ? $(element).data('name') : '';
    if (preferredName.length > 0) {
        var nameNotFound = commentText.indexOf(preferredName) === -1;
        $('.characterInfo .commentStatusName', $(element).parent()).toggleClass('hidden', !nameNotFound);
    }

    // Check to ensure the pronouns match the gender of the student
    var gender = $(element).data('gender') ? $(element).data('gender') : '';
    if (gender.length > 0) {
        var heFound = commentText.search(/\bhe\b/i) !== -1 || commentText.search(/\bhis\b/i) !== -1 || commentText.search(/\bhim\b/i) !== -1 || commentText.search(/\bhimself\b/i) !== -1;
        var sheFound = commentText.search(/\bshe\b/i) !== -1 || commentText.search(/\bher\b/i) !== -1 || commentText.search(/\bherself\b/i) !== -1;
        var pronounMismatch = (heFound && gender == 'F') || (sheFound && gender == 'M');
        $('.characterInfo .commentStatusPronoun', $(element).parent()).toggleClass('hidden', !pronounMismatch);
    }
}

function updatePlaceholders(element)
{
    var commentText = $(element).val();

    // Replace {name} with the student's preferred name
    var preferredName = $(element).data('name') ? $(element).data('name') : '';
    if (preferredName.length > 0) {
        commentText = commentText.replace(/{name}/ig, preferredName);
    }

    // Replace pronouns to match the student's gender
    var gender = $(element).data('gender') ? $(element).data('gender') : '';
    if (gender.length > 0) {
        if (gender == 'F') {
            commentText = commentText.replace(/\bhe\b/g, 'she').replace(/\bHe\b/g, 'She');
            commentText = commentText.replace(/\bhis\b/g, 'her').replace(/\bHis\b/g, 'Her');
            commentText = commentText.replace(/\bhim\b/g, 'her').replace(/\bHim\b/g, 'Her');
            commentText = commentText.replace(/\bhimself\b/g, 'herself').replace(/\bHimself\b/g, 'Herself');
        } else if (gender == 'M') {
            commentText = commentText.replace(/\bshe\b/g, 'he').replace(/\bShe\b/g, 'He');
            commentText = commentText.replace(/\bher\b/g, 'his').replace(/\bHer\b/g, 'His');
            commentText = commentText.replace(/\bherself\b/g, 'himself').replace(/\bHerself\b/g, 'Himself');
        }
    }

    $(element).val(commentText);
}
