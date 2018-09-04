/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

jQuery(function($){
    /**
     * Form Class: generic check All/None checkboxes
     */
    $(document).on('click', '.checkall', function () {
        $(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', $(this).prop('checked')).trigger('change');
    });

    /**
     * Bluk Actions: show/hide the bulk action panel, highlight selected
     */
    $(document).on('click, change', '.bulkActionForm .bulkCheckbox :checkbox', function () {
        var checkboxes = $(this).parents('.bulkActionForm').find('.bulkCheckbox :checkbox');
        var checkedCount = checkboxes.filter(':checked').length;

        if (checkedCount > 0) {
            $('.bulkActionCount span').html(checkedCount);
            $('.bulkActionPanel').fadeIn(150);

            // Trigger a showhide event on any nested inputs to update their visibility & validation state
            $('.bulkActionPanel :input').trigger('showhide');
        } else {
            $('.bulkActionPanel').fadeOut(75);
        }
        
        $('.checkall').prop('checked', checkedCount > 0 );
        $('.checkall').prop('indeterminate', checkedCount > 0 && checkedCount < checkboxes.length);

        $(this).parents('tr').toggleClass('selected', $(this).prop('checked'));
        
    });

    /**
     * Column Highlighting
     */
    var columnHighlight = $(".columnHighlight td");
    columnHighlight.on("mouseover", function() {
      columnHighlight.filter(":nth-child(" + ($(this).index() + 1) + ")").addClass("hover");
    })
    .on("mouseout", function() {
      columnHighlight.removeClass("hover");
    });

    /**
     * Password Generator. Requires data-source, data-confirm and data-alert attributes.
     */
    $(".generatePassword").click(function(){
        if ($(this).data("source") == "" || $(this).data("confirm") == "") return;

        var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^~@|";
        var text = '';
        for(var i=0; i < 12; i++) {
            if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
            else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
            else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
            else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
            else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
        }
        $('input[name="' + $(this).data("source") + '"]').val(text).blur();
        $('input[name="' + $(this).data("confirm") + '"]').val(text).blur();
        prompt($(this).data("alert"), text);
    });
});

// Form API Functions

/**
 * TextField Uniqueness Check
 */
$.prototype.gibbonUniquenessCheck = function (settings) {
    var uniqueField = this;
    var validation;

    $(uniqueField).ready(function(){
        // Get the existing LiveValidation object, otherwise create one
        validation = window[$(uniqueField).attr('id') + "Validate"];
        if (validation == null || typeof validation != "object") {
            validation = new LiveValidation($(uniqueField).attr('id'));
        }

        validation.onValid = function() {
            // Pass the current value as POST['value'] (optionally by a defined fieldName)
            settings.ajaxData[settings.ajaxData.fieldName || "value"] = $(uniqueField).val();

            // Send an AJAX request to check uniqueness, and use LiveValidation messages to display response
            $.ajax({
                type: 'POST',
                data: settings.ajaxData,
                url: settings.ajaxURL,
                success: function (responseText) {
                    // The response should be the count of matching values, so 0 is unique and -1 is an error
                    if (responseText < 0) {
                        validation.message = validation.invalidMessage = settings.alertError;
                        validation.validationFailed = true;
                    } else if (responseText == 0) {
                        validation.message = validation.validMessage = settings.alertSuccess;
                        validation.validationFailed = false;
                    } else if (responseText > 0) {
                        validation.message = validation.invalidMessage = settings.alertFailure;
                        validation.validationFailed = true;
                        validation.add(Validate.Exclusion, { within: [$(uniqueField).val()], failureMessage: settings.alertFailure });
                    }
                    validation.insertMessage(validation.createMessageSpan());
                    validation.addFieldClass();
                }
            });
        };
    });
};

/**
 * Custom Blocks
 */
// Define the CustomBlocks behaviour
var CustomBlocks = window.CustomBlocks || {};

CustomBlocks = (function(element, settings) {
    var _ = this;

    _.container = $(element);
    _.blockTemplate = $('.blockTemplate', element);
    _.blockCount = 0;
    _.validation = [];
    _.defaults = {
        inputNameStrategy: "object",    // array | object | string
        addSelector: ".addBlock",       // The selector to trigger an add block action on
        addOnEvent: "click",            // The event type to trigger an add block action on
        deleteMessage: "Delete?",       // The confirmation message when deleting a block
        animationSpeed: 600,            // The speed for block animations
        currentBlocks: [],              // Blocks that should be initialized when creating is object
        predefinedBlocks: [],           // Data to add for new blocks if the identifier matches a key.
        sortable: false,                // Enable jQuery-ui drag-drop sorting
    }
    _.settings = $.extend({}, _.defaults, settings);

    _.init();
});

CustomBlocks.prototype.init = function() {
    var _ = this;

    // Setup tool actions
    $(_.settings.addSelector, _.container).each(function(){
        $(this).on(_.settings.addOnEvent, function(){
            var identifier = $(this).val();
            if (!identifier) return;

            var data = _.settings.predefinedBlocks[identifier] || {};
            _.addBlock(data);
        });
    });

    // Enable sortable drag-drop
    if (_.settings.sortable) {
        $(".blocks", _.container).sortable({
            placeholder: "sortHighlight",
            handle: ".sortHandle",
        }).bind('sortstart', function(event, ui) {
            $(_.container).trigger('hideAll');
        });
        $(_.blockTemplate).prepend('<div class="sortHandle floatLeft"></div>');
    }

    $('.showHide', _.blockTemplate).hide();

    // Initialize existing blocks from JSON data
    for (var index in _.settings.currentBlocks) {
        _.addBlock(_.settings.currentBlocks[index]);
    }

    // Built-in Button Events
    $(_.container)
        .on('delete', function(event, block) {
            if (confirm(_.settings.deleteMessage)) {
                _.removeBlock(block);
            }
        })
        .on('showHide', function(event, block, button) {
            if ($(button).hasClass('showHidden')) {
                $(button).removeClass('showHidden');
                $('img', button).prop('src', $(button).data('off'));
                block.find('.showHide').hide();
            } else {
                $(button).addClass('showHidden');
                $('img', button).prop('src', $(button).data('on'));
                block.find('.showHide').show();
            }
        })
        .on('hideAll', function(event, block, button) {
            $('.showHide').hide();
            $('a.blockButton[data-event="showHide"]').each(function(index, element){
                $(element).removeClass('showHidden');
                $('img', element).prop('src', $(element).data('off'));
            });
        });

    _.refresh();
};

CustomBlocks.prototype.addBlock = function(data) {
    var _ = this;

    _.blockCount++;

    var block = $(_.blockTemplate).clone().css("display", "block").appendTo($(".blocks", _.container));
    $(block).append('<input type="hidden" name="order[]" value="'+_.blockCount+'" />');

    _.initBlock(block, data);
    _.refresh();
};

CustomBlocks.prototype.removeBlock = function(block) {
    var _ = this;

    _.blockCount--;

    _.removeBlockValidation(block);

    $(block).fadeOut(_.settings.animationSpeed, function(){
        $(block).detach().remove();
        _.refresh();
    });
};

CustomBlocks.prototype.initBlock = function(block, data) {
    var _ = this;

    block.blockNumber = _.blockCount;

    _.loadBlockInputData(block, data);
    _.renameBlockFields(block);
    _.addBlockValidation(block);
    _.addBlockEvents(block);
};

CustomBlocks.prototype.loadBlockInputData = function(block, data) {
    var _ = this;

    for (key in data) {
        $("[name='"+key+"']", block).val(data[key]);
    }

    var readonly = data.readonly || [];
    readonly.forEach(function(element){
        $("[name='"+element+"']", block).prop('readonly', true).addClass('readonly');
        $("select[name='"+element+"'] option:not(:selected)", block).prop('disabled', true);
    });
};

CustomBlocks.prototype.renameBlockFields = function(block) {
    var _ = this;

    $("input, textarea, select", block).each(function(index, element) {
        if ($(this).prop("name") == 'order[]') return;

        var name;
        switch(_.settings.inputNameStrategy) {
            case 'object':  name = $(_.container).prop("id")+"["+block.blockNumber+"]["+$(this).prop("name")+"]"; break;
            case 'array':   name = $(this).prop("name")+"["+block.blockNumber+"]"; break;
            case 'string':  name = $(this).prop("name")+block.blockNumber; break;
        }

        $(this).prop("name", name);
        $(this).prop("id", $(this).prop("id")+block.blockNumber);
    });

    $("label", block).each(function(index, element) {
        $(this).prop("for", $(this).prop("for")+block.blockNumber);
    });
};

CustomBlocks.prototype.addBlockValidation = function(block) {
    var _ = this;
    
    $("input, textarea, select", block).each(function(index, element) {
        if ($(this).data('validation') && !$(this).prop('readonly')) {
            var id = $(this).prop("id");
            eval("block."+id+"Validate = new LiveValidation('"+id+"', {});");
            $(this).data('validation').forEach(function(item) {
                eval("block."+id+"Validate.add("+item.type+", {"+item.params+"});");
            });
        }
    });
};

CustomBlocks.prototype.removeBlockValidation = function(block) {
    var _ = this;
    
    $("input, textarea, select", block).each(function(index, element) {
        if ($(this).data('validation') && !$(this).prop('readonly')) {
            var id = $(this).prop("id");
            eval("block."+id+"Validate.destroy();");
        }
    });
};

CustomBlocks.prototype.addBlockEvents = function(block) {
    var _ = this;

    $("a.blockButton", block).each(function(index, element) {
        $(element).click(function(event){
            event.preventDefault();
            $(_.container).trigger($(this).data('event'), [ block, this ]);
        });
    });
};

CustomBlocks.prototype.refresh = function() {
    var _ = this;

    $(".blockCount", _.container).val(_.blockCount);
    $(".blockPlaceholder", _.container).css("display", (_.blockCount > 0)? "none" : "block");
    $("select.addBlock", _.container).val(''); // Deselect after action
};

// Add the prototype method to jQuery
$.prototype.gibbonCustomBlocks = function(settings) {
    this.gibbonCustomBlocks = new CustomBlocks(this, settings);
};
    
/**
 * Gibbon Data Table: a very basic implementation of jQuery + AJAX powered data tables in Gibbon
 * @param string basePath 
 * @param Object settings 
 */
var DataTable = window.DataTable || {};

DataTable = (function(element, basePath, filters) {
    var _ = this;

    _.table = $(element);
    _.path = basePath + " #" + $(element).attr('id') + " .dataTable";
    _.filters = filters;
    if (_.filters.sortBy.length == 0) _.filters.sortBy = {};
    if (_.filters.filterBy.length == 0) _.filters.filterBy = {};

    _.init();
});

DataTable.prototype.init = function() {
    var _ = this;

    // Pagination
    $(_.table).on('click', '.paginate', function() {
        var resultCount = $('.dataTable', _.table).data('results');
        _.filters.pageMax = Math.ceil(resultCount / _.filters.pageSize);
        _.filters.page = Math.min($(this).data('page'), _.filters.pageMax);
        _.refresh();
    });

    // Sortable Columns
    $(_.table).on('click', '.column.sortable', function(event) {
        var columns = $(this).data('sort').split(',');

        // Hold shift to add columns to the sort (or toggle them), otherwise clear it each time.
        var activeColumns = columns.filter(function(item){ return item in _.filters.sortBy; }); 
        if (activeColumns.length == 0 && !event.shiftKey) _.filters.sortBy = {};

        columns.forEach(function(column) {
            _.filters.sortBy[column] = (_.filters.sortBy[column] == 'ASC')? 'DESC' : 'ASC';
        });

        _.refresh();
    });

    // Remove Filter
    $(_.table).on('click', '.filter', function() {
        var filter = $(this).data('filter');
        
        if ($(this).hasClass('clear')) {
            _.filters.filterBy = {'':''};
            _.filters.searchBy.columns = [''];
        } else if (filter in _.filters.filterBy) {
            // Remove columns from search criteria if removing an in: filter
            if (filter == 'in') _.filters.searchBy.columns = [''];
            delete _.filters.filterBy[filter];
        }

        if (jQuery.isEmptyObject(_.filters.filterBy)) _.filters.filterBy = {'':''};

        _.filters.page = 1;
        _.refresh();
    });

    // Add Filter
    $(_.table).on('change', '.filters', function() {
        var filterData = $(this).val().split(':');
        var filter = filterData[0];
        var value = filterData[1];

        _.filters.filterBy[filter] = value;
        _.filters.page = 1;

        _.refresh();
    });

    // Page Size
    $(_.table).on('change', '.limit', function() {
        var resultCount = $('.dataTable', _.table).data('results');
        _.filters.pageSize = parseInt($(this).val());
        _.filters.pageMax = Math.ceil(resultCount / _.filters.pageSize);
        _.filters.page = Math.min(_.filters.page, _.filters.pageMax);
        _.refresh();
    });

    // Expandable Rows
    $(_.table).on('click', '.expander', function() {
        $(this).toggleClass('expanded');
        $(this).parents('tr').next('tr').toggle();
    });
};

DataTable.prototype.refresh = function() {
    var _ = this;

    var submitted = setTimeout(function() {
        $('.pagination', _.table).prepend('<span class="submitted"></span>');
    }, 500);

    $(_.table).load(_.path, _.filters, function(responseText, textStatus, jqXHR) { 
        $('.bulkActionPanel').hide();
        tb_init('a.thickbox'); 
        clearTimeout(submitted);
    });
};

$.prototype.gibbonDataTable = function(basePath, filters) {
    this.gibbonDataTable = new DataTable(this, basePath, filters);
};

/**
 * Disable the submit button once a form has started submitting. 
 * Add a spinning indicator for forms that take longer than 0.5s to submit.
 */
function gibbonFormSubmitted(form) {
    var submitButton = $('input[type="submit"]', $(form));
    submitButton.prop('disabled', true);
    if ($(form).hasClass('standardForm')) {
        setTimeout(function() {
            submitButton.wrap('<span class="submitted"></span>');
        }, 500);
    }
}
