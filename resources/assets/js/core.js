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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Initialize an Alpine.js Tooltip (on non-mobile devices)
document.addEventListener('alpine:init', () => {
    if (window.innerWidth < 768) return;

    var currentTooltip;

    Alpine.directive('tooltip', (el, { modifiers, expression }, { cleanup }) => {
        var tooltipActive = false;
        let tooltipText = expression;
        let tooltipArrow = modifiers.includes('noarrow') ? false : true;
        let tooltipPosition = 'top';
        let tooltipId = 'tooltip-' + Date.now().toString(36) + Math.random().toString(36).substring(2, 7);
        let positions = ['top', 'bottom', 'left', 'right'];
        let elementPosition = getComputedStyle(el).position;

        for (let position of positions) {
            if (modifiers.includes(position)) {
                tooltipPosition = position;
                break;
            }
        }

        if(!['relative', 'absolute', 'fixed'].includes(elementPosition)){
            el.style.position='relative';
        }
        
        let tooltipHTML = `
            <template x-teleport="body"><div id="${tooltipId}" x-cloak x-data="{ tooltipVisible: false, tooltipArrow: ${tooltipArrow}, tooltipPosition: '${tooltipPosition}' }" x-ref="tooltip" x-init="setTimeout(function(){ tooltipVisible = true; }, 1);" x-show="tooltipVisible" :class="{ 'top-0 left-1/2 -translate-x-1/2 -mt-1.5 -translate-y-full' : tooltipPosition == 'top', 'top-1/2 -translate-y-1/2 -ml-1.5 left-0 -translate-x-full' : tooltipPosition == 'left', 'bottom-0 left-1/2 -translate-x-1/2 -mb-0.5 ' : tooltipPosition == 'bottom', 'top-1/2 -translate-y-1/2 -mr-1.5  ' : tooltipPosition == 'right' }" class="absolute pointer-events-none max-w-sm text-sm font-normal" style="z-index: 100;" >
            
                <div x-show="tooltipVisible" class="relative px-2 py-1 text-white bg-black bg-opacity-80 backdrop-blur-lg backdrop-contrast-125 backdrop-saturate-150 rounded-md"
                    x-transition:enter="transition delay-75 ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-50"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-50" >
                    <p class="flex-shrink-0 block m-0 p-1 text-xs " >${tooltipText}</p>
                    <div x-ref="tooltipArrow" x-show="tooltipArrow" :class="{ 'bottom-0 -translate-x-1/2 left-1/2 w-2.5 translate-y-full' : tooltipPosition == 'top', 'right-0 -translate-y-1/2 top-1/2 h-2.5 -mt-px translate-x-full' : tooltipPosition == 'left', 'top-0 -translate-x-1/2 left-1/2 w-2.5 -translate-y-full' : tooltipPosition == 'bottom', 'left-0 -translate-y-1/2 top-1/2 h-2.5 -mt-px -translate-x-full' : tooltipPosition == 'right' }" class="absolute inline-flex items-center justify-center overflow-hidden">
                        <div :class="{ 'origin-top-left -rotate-45' : tooltipPosition == 'top', 'origin-top-left rotate-45' : tooltipPosition == 'left', 'origin-bottom-left rotate-45' : tooltipPosition == 'bottom', 'origin-top-right -rotate-45' : tooltipPosition == 'right' }" class="w-1.5 h-1.5 transform bg-black bg-opacity-80"></div>
                    </div>
                </div>
                
            </div></template>
        `;
        
        el.dataset.tooltip = tooltipId;

        let mouseEnter = function(event){ 
            if (currentTooltip != null) {
                currentTooltip.dispatchEvent(new Event('mouseleave'));
            }
            if (!tooltipActive) {
                var elRect = el.getBoundingClientRect();

                el.insertAdjacentHTML('beforeend', tooltipHTML);
                setTimeout(function(){
                    var tooltip = document.getElementById(tooltipId);
                    if (tooltip != null) {
                        if (tooltipPosition == 'top') {
                            tooltip.style.top = (elRect.top + window.scrollY )+"px";
                            tooltip.style.left = (elRect.left + window.scrollX + (elRect.width/2.0))+"px";
                        } else if (tooltipPosition == 'bottom') {
                            tooltip.style.top = (elRect.bottom + window.scrollY + elRect.height )+"px";
                            tooltip.style.left = (elRect.left + window.scrollX + (elRect.width/2.0))+"px";
                        } else if (tooltipPosition == 'left') {
                            tooltip.style.top = (elRect.top + window.scrollY + (elRect.height/2.0))+"px";
                            tooltip.style.left = (elRect.left + window.scrollX - 12  )+"px";
                        } else if (tooltipPosition == 'right') {
                            tooltip.style.top = (elRect.top + window.scrollY + (elRect.height/2.0))+"px";
                            tooltip.style.left = (elRect.right + window.scrollX + 12  )+"px";
                        }
                    }
                }, 50);
                
                tooltipActive = true;
                currentTooltip = el;
            }
        };

        let mouseLeave = function(event){
            var tooltip = document.getElementById(tooltipId);
            if (tooltip) tooltip.remove();

            tooltipActive = false;
            currentTooltip = null;
        };
        
        el.addEventListener('mouseenter', mouseEnter);
        el.addEventListener('mouseleave', mouseLeave);
        document.addEventListener('htmx:beforeRequest', mouseLeave);

        cleanup(() => {
            el.removeEventListener('mouseenter', mouseEnter);
            el.removeEventListener('mouseleave', mouseLeave);
        })
    });
    
});

// Enable preventing page navigation from hx-boosted links
document.addEventListener('htmx:confirm', function(evt) {
    if (!evt.detail.elt.hasAttribute('hx-boost')) return;

    evt.preventDefault();

    if (window.onbeforeunload != null) {
        if (window.confirm(Gibbon.config.htmx.unload_confirm)) {
            window.onbeforeunload = null;
            evt.detail.issueRequest(true);
        }
    } else {
        evt.detail.issueRequest(true);
    }
}, false);


htmx.onLoad(function (content) {
    
    /**
     * Sidebar toggle switch
     */
    $("#sidebarToggle").click(function () {
        if ($("#sidebar").hasClass("lg:w-sidebar")) {
            $("#sidebar").removeClass("lg:w-sidebar");
            $("#sidebar").addClass("lg:hidden");
            $(this).html("«");
        } else {
            $("#sidebar").removeClass("lg:hidden");
            $("#sidebar").addClass("lg:w-sidebar");
            $(this).html("»");
        }
    });

    /**
     * Form Class: generic check All/None checkboxes
     */
    $(document).on("click", '.checkall[type="checkbox"]', function () {
        var checkall = this;
        var checked = checkall.checked;
        var parent = checkall.parentElement.parentElement.closest(
            '.bulkActionForm, .checkboxGroup'
        );

        parent
            .querySelectorAll('input[type="checkbox"]')
            .forEach(function (element, index, elements) {
                if (element === checkall) return;

                element.checked = checked;

                let formRow = element.closest("tr");
                if (formRow != undefined) {
                    formRow.classList.toggle("selected", element.checked);
                }

                if (index == elements.length - 1) {
                    $(element).trigger("change");
                }
            });
    });

    /**
     * Bulk Actions: show/hide the bulk action panel, highlight selected
     */
    $(document).on(
        "click, change",
        ".bulkActionForm .bulkCheckbox :checkbox",
        function () {
            var checkboxes = $(this)
                .parents(".bulkActionForm")
                .find(".bulkCheckbox :checkbox");
            var checkedCount = checkboxes.filter(":checked").length;

            if (checkedCount > 0) {
                $(".bulkActionCount span").html(checkedCount);

                if ($(".bulkActionPanel").hasClass("hidden")) {
                    $(".bulkActionPanel").removeClass("hidden");

                    var header = $(this)
                        .parents(".bulkActionForm")
                        .find(".dataTable header");
                    var panelHeight = $(".bulkActionPanel").innerHeight();
                    
                    $(".bulkActionPanel").css(
                        "top",
                        header.outerHeight(false) - panelHeight + 6
                    );

                    // Trigger a showhide event on any nested inputs to update their visibility & validation state
                    $(".bulkActionPanel :input").trigger("showhide");
                }
            } else {
                $(".bulkActionPanel").addClass("hidden");
            }

            $(".checkall").prop("checked", checkedCount > 0);
            $(".checkall").prop(
                "indeterminate",
                checkedCount > 0 && checkedCount < checkboxes.length
            );

            $(this)
                .closest("tr")
                .toggleClass("selected", $(this).prop("checked"));
        }
    );

    // Highlight any pre-checked rows
    document
        .querySelectorAll('.bulkCheckbox input[type="checkbox"]')
        .forEach(function (element) {
            element.closest("tr").classList.toggle("selected", element.checked);
        });

    /**
     * Column Highlighting
     */
    var columnHighlight = $(".columnHighlight td");
    columnHighlight
        .on("mouseover", function () {
            columnHighlight
                .filter(":nth-child(" + ($(this).index() + 1) + ")")
                .addClass("hover");
        })
        .on("mouseout", function () {
            columnHighlight.removeClass("hover");
        });

    /**
     * Password Generator. Requires data-source, data-confirm and data-alert attributes.
     */
    $(".generatePassword").click(function () {
        if ($(this).data("source") == "" || $(this).data("confirm") == "")
            return;

        var chars =
            "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^~@|";
        var text = "";
        for (var i = 0; i < 12; i++) {
            if (i == 0) {
                text += chars.charAt(Math.floor(Math.random() * 26));
            } else if (i == 1) {
                text += chars.charAt(Math.floor(Math.random() * 26) + 26);
            } else if (i == 2) {
                text += chars.charAt(Math.floor(Math.random() * 10) + 52);
            } else if (i == 3) {
                text += chars.charAt(Math.floor(Math.random() * 19) + 62);
            } else {
                text += chars.charAt(Math.floor(Math.random() * chars.length));
            }
        }
        $('input[name="' + $(this).data("source") + '"]')
            .val(text)
            .blur();
        $('input[name="' + $(this).data("confirm") + '"]')
            .val(text)
            .blur();
        document.getElementById($(this).data("source")).dispatchEvent(new Event('blur'));

        prompt($(this).data("alert"), text);
    });

    /**
     * Username Generator. Requires data-alert attribute.
     */
    $(".generateUsername").click(function () {
        var alertText = $(this).data("alert");
        $.ajax({
            type: "POST",
            data: {
                gibbonRoleID: $("#gibbonRoleIDPrimary").val(),
                preferredName: $("#preferredName").val(),
                firstName: $("#firstName").val(),
                surname: $("#surname").val(),
            },
            url: "./modules/User Admin/user_manage_usernameAjax.php",
            success: function (responseText) {
                if (responseText == 0) {
                    $("#gibbonRoleIDPrimary").change();
                    $("#preferredName").blur();
                    $("#firstName").blur();
                    $("#surname").blur();
                    alert(alertText);
                } else {
                    $("#username").val(responseText);
                    $("#username").trigger("input");
                    $("#username").blur();
                }
            },
        });
    });

    /**
     * Data Table: Simple Drag-Drop
     */
    var sortables = content.querySelectorAll("table[data-draggable] tbody");
    for (var i = 0; i < sortables.length; i++) {
        var sortable = sortables[i];
        var sortableInstance = new Sortable(sortable, {
            animation: 150,
            ghostClass: "bg-purple-100",
            handle: ".drag-handle",
            dataIdAttr: 'data-drag-id', 
            swapThreshold: 0.75,

            // Make the `.htmx-indicator` unsortable
            filter: ".htmx-indicator",
            onMove: function (evt) {
                return evt.related.className.indexOf("htmx-indicator") === -1;
            },

            // Disable sorting on the `end` event
            onEnd: function (evt) {
                // this.option("disabled", true);
            },
        });

        // Re-enable sorting on the `htmx:afterRequest` event
        // content.addEventListener("htmx:afterRequest", function (event) {
        //     sortableInstance.option("disabled", false);
        // });
    }
});


// Form API Functions

/**
 * Comment Editor
 */
$.prototype.gibbonCommentEditor = function (settings) {
    var editor = this;

    updateComments(editor);

    $(editor).on("input", function () {
        updateComments(this);
    });

    $(editor).on("paste", function () {
        var element = this;
        setTimeout(function () {
            updatePlaceholders(element);
            updateComments(element);
        }, 0);
    });

    $(editor).ready(function () {
        autosize(editor);
    });
};

function updateComments(element) {
    var commentText = $(element).val();

    // Update character counter for comment length
    var currentLength = commentText.length;
    $(".characterInfo .currentLength", $(element).parent()).html(currentLength);

    // Look for the student's first name somewhere in the comment
    var preferredName = $(element).data("name") ? $(element).data("name") : "";
    if (preferredName.length > 0) {
        var nameNotFound = commentText.indexOf(preferredName) === -1;
        $(".characterInfo .commentStatusName", $(element).parent()).toggleClass(
            "hidden",
            !nameNotFound
        );
    }

    // Check to ensure the pronouns match the gender of the student
    var gender = $(element).data("gender") ? $(element).data("gender") : "";
    if (gender.length > 0) {
        var heFound =
            commentText.search(/\bhe\b/i) !== -1 ||
            commentText.search(/\bhis\b/i) !== -1 ||
            commentText.search(/\bhim\b/i) !== -1 ||
            commentText.search(/\bhimself\b/i) !== -1;
        var sheFound =
            commentText.search(/\bshe\b/i) !== -1 ||
            commentText.search(/\bher\b/i) !== -1 ||
            commentText.search(/\bherself\b/i) !== -1;
        var pronounMismatch =
            (heFound && gender == "F") || (sheFound && gender == "M");
        $(
            ".characterInfo .commentStatusPronoun",
            $(element).parent()
        ).toggleClass("hidden", !pronounMismatch);
    }
}

function updatePlaceholders(element) {
    var commentText = $(element).val();

    // Replace {name} with the student's preferred name
    var preferredName = $(element).data("name") ? $(element).data("name") : "";
    if (preferredName.length > 0) {
        commentText = commentText.replace(/{name}/gi, preferredName);
    }

    // Replace pronouns to match the student's gender
    var gender = $(element).data("gender") ? $(element).data("gender") : "";
    if (gender.length > 0) {
        if (gender == "F") {
            commentText = commentText
                .replace(/\bhe\b/g, "she")
                .replace(/\bHe\b/g, "She");
            commentText = commentText
                .replace(/\bhis\b/g, "her")
                .replace(/\bHis\b/g, "Her");
            commentText = commentText
                .replace(/\bhim\b/g, "her")
                .replace(/\bHim\b/g, "Her");
            commentText = commentText
                .replace(/\bhimself\b/g, "herself")
                .replace(/\bHimself\b/g, "Herself");
        } else if (gender == "M") {
            commentText = commentText
                .replace(/\bshe\b/g, "he")
                .replace(/\bShe\b/g, "He");
            commentText = commentText
                .replace(/\bher\b/g, "his")
                .replace(/\bHer\b/g, "His");
            commentText = commentText
                .replace(/\bherself\b/g, "himself")
                .replace(/\bHerself\b/g, "Himself");
        }
    }

    $(element).val(commentText);
}

/**
 * Custom Blocks
 */
// Define the CustomBlocks behaviour
var CustomBlocks = window.CustomBlocks || {};

CustomBlocks = function (element, settings) {
    var _ = this;

    _.container = $(element);
    _.blockTemplate = $(".blockTemplate", element);
    _.blockCount = 0;
    _.identifiers = [];
    _.validation = [];
    _.defaults = {
        inputNameStrategy: "object", // array | object | string
        addSelector: ".addBlock", // The selector to trigger an add block action on
        addOnEvent: "click", // The event type to trigger an add block action on
        deleteMessage: "Delete?", // The confirmation message when deleting a block
        duplicateMessage: "Duplicate", // The message to display when a duplicate is added
        animationSpeed: 600, // The speed for block animations
        currentBlocks: [], // Blocks that should be initialized when creating is object
        predefinedBlocks: [], // Data to add for new blocks if the identifier matches a key.
        preventDuplicates: false, // Can the same block be added more than once?
        sortable: false, // Enable jQuery-ui drag-drop sorting
        orderName: "order", // Name of the variable used to hold sortable block order
    };
    _.settings = $.extend({}, _.defaults, settings);

    _.init();
};

CustomBlocks.prototype.init = function () {
    var _ = this;

    // Setup tool actions
    $(_.settings.addSelector, _.container).each(function () {
        $(this).on(_.settings.addOnEvent, function () {
            var identifier = $(this).val();
            if (!identifier) return;

            if (
                _.settings.preventDuplicates &&
                _.identifiers.includes(identifier)
            ) {
                alert(_.settings.duplicateMessage);
                return;
            }

            var data = _.settings.predefinedBlocks[identifier] || {};
            data.identifier = identifier;

            _.addBlock(data);
            _.identifiers.push(identifier);
        });
    });

    // Enable sortable drag-drop
    if (_.settings.sortable) {
        $(".blocks", _.container)
            .sortable({
                placeholder: "sortHighlight",
                handle: ".sortHandle",
            })
            .bind("sortstart", function (event, ui) {
                $(_.container).trigger("hideAll");

                // Suspend the TinyMCE editors before sorting
                $("textarea.tinymce", _.container).each(function (
                    index,
                    element
                ) {
                    tinymce.EditorManager.execCommand(
                        "mceRemoveEditor",
                        false,
                        $(this).prop("id")
                    );
                });
            });

        $(_.blockTemplate).prepend('<div class="sortHandle floatLeft"></div>');
    }

    $(".showHide", _.blockTemplate).hide();

    // Disable all block template inputs
    $(":input", _.blockTemplate).prop("disabled", true);

    // Initialize existing blocks from JSON data
    for (var index in _.settings.currentBlocks) {
        _.addBlock(_.settings.currentBlocks[index]);
        _.identifiers.push(index);
    }

    // Built-in Button Events
    $(_.container)
        .on("delete", function (event, block) {
            if (confirm(_.settings.deleteMessage)) {
                _.removeBlock(block);
            }
        })
        .on("showHide", function (event, block, button) {
            if ($(button).hasClass("showHidden")) {
                $(button).removeClass("showHidden");
                $("img", button).prop("src", $(button).data("off"));
                block.find(".showHide").hide();
            } else {
                $(button).addClass("showHidden");
                $("img", button).prop("src", $(button).data("on"));
                block.find(".showHide").show();

                // Restart any TinyMCE editors that are not active
                $("textarea.tinymce", block).each(function (index, element) {
                    tinymce.EditorManager.execCommand(
                        "mceAddEditor",
                        false,
                        $(this).prop("id")
                    );
                });
            }
        })
        .on("hideAll", function (event, block, button) {
            $(".showHide").hide();
            $('a.blockButton[data-event="showHide"]').each(function (
                index,
                element
            ) {
                $(element).removeClass("showHidden");
                $("img", element).prop("src", $(element).data("off"));
            });
        });

    _.refresh();
};

CustomBlocks.prototype.addBlock = function (data) {
    var _ = this;

    _.blockCount++;

    var block = $(_.blockTemplate)
        .clone()
        .css("display", "block")
        .appendTo($(".blocks", _.container));
    $(block).append(
        '<input type="hidden" name="' +
            _.settings.orderName +
            '[]" value="' +
            _.blockCount +
            '" />'
    );

    _.initBlock(block, data);
    _.refresh();

    $(_.container).trigger("addedBlock", [block]);
};

CustomBlocks.prototype.removeBlock = function (block) {
    var _ = this;

    _.blockCount--;

    var index = _.identifiers.indexOf(block.identifier);
    if (index !== -1) _.identifiers.splice(index, 1);

    _.removeBlockValidation(block);

    $(block).fadeOut(_.settings.animationSpeed, function () {
        $(block).detach().remove();
        _.refresh();
    });

    $(_.container).trigger("removedBlock", [block]);
};

CustomBlocks.prototype.initBlock = function (block, data) {
    var _ = this;

    block.blockNumber = _.blockCount;
    block.identifier = data.identifier;

    _.loadBlockInputData(block, data);
    _.renameBlockFields(block);
    _.addBlockValidation(block);
    _.addBlockEvents(block);
};

CustomBlocks.prototype.loadBlockInputData = function (block, data) {
    var _ = this;

    $(":input", block).prop("disabled", false);

    for (key in data) {
        $(
            "[name='" +
                key +
                "']:not([type='file']):not([type='radio']):not([type='checkbox'])",
            block
        ).val(data[key]);
        $("input:radio[name='" + key + "']", block).each(function () {
            if ($(this).val() == data[key]) {
                $(this).attr("checked", true);
            }
        });
        $(
            "input:checkbox[name='" +
                key +
                "'],input:checkbox[name='" +
                key +
                "[]']",
            block
        ).each(function () {
            var options = Array.isArray(data[key])
                ? data[key]
                : data[key].split(",");
            if (options.includes($(this).val())) {
                $(this).attr("checked", true);
            }
        });
    }

    var readonly = data.readonly || [];
    readonly.forEach(function (element) {
        $("[name='" + element + "']", block)
            .prop("readonly", true)
            .addClass("readonly");
        $("select[name='" + element + "'] option:not(:selected)", block).prop(
            "disabled",
            true
        );
    });
};

CustomBlocks.prototype.renameBlockFields = function (block) {
    var _ = this;

    $("input, textarea, select", block).each(function (index, element) {
        if ($(this).prop("name") == _.settings.orderName + "[]") return;

        var name;
        switch (_.settings.inputNameStrategy) {
            case "object":
                name =
                    $(_.container).prop("id") +
                    "[" +
                    block.blockNumber +
                    "][" +
                    $(this).prop("name") +
                    "]";
                break;
            case "array":
                name = $(this).prop("name") + "[" + block.blockNumber + "]";
                break;
            case "string":
                name = $(this).prop("name") + block.blockNumber;
                break;
        }

        name = name.replace("[]]", "][]");

        $(this).prop("name", name);
        if ($(this).prop("id") != "") {
            $(this).prop("id", $(this).prop("id") + block.blockNumber);
        }
    });

    $("label", block).each(function (index, element) {
        $(this).prop("for", $(this).prop("for") + block.blockNumber);
    });

    // Initialize any textareas tagged as tinymce using an AJAX load to grab a full editor
    $("textarea[data-tinymce]", block).each(function (index, element) {
        var isHidden = $(this).is(":hidden");
        var data = {
            id: $(this).prop("id"),
            value: $(this).val(),
            showMedia: $(this).data("media"),
            rows: $(this).attr("rows"),
        };
        $(this)
            .parent()
            .load(
                "./modules/Planner/planner_editorAjax.php",
                data,
                function (responseText, textStatus, jqXHR) {
                    if (!isHidden) {
                        tinymce.EditorManager.execCommand(
                            "mceAddEditor",
                            false,
                            data.id
                        );
                    }
                }
            );
    });
};

CustomBlocks.prototype.addBlockValidation = function (block) {
    var _ = this;

    $("input, textarea, select", block).each(function (index, element) {
        if ($(this).data("validation") && !$(this).prop("readonly")) {
            var id = $(this).prop("id");
            eval(
                "block." +
                    id +
                    "Validate = new LiveValidation('" +
                    id +
                    "', {});"
            );
            $(this)
                .data("validation")
                .forEach(function (item) {
                    eval(
                        "block." +
                            id +
                            "Validate.add(" +
                            item.type +
                            ", {" +
                            item.params +
                            "});"
                    );
                });
        }
    });
};

CustomBlocks.prototype.removeBlockValidation = function (block) {
    var _ = this;

    $("input, textarea, select", block).each(function (index, element) {
        if ($(this).data("validation") && !$(this).prop("readonly")) {
            var id = $(this).prop("id");
            eval("block." + id + "Validate.destroy();");
        }
    });

    $("textarea.tinymce", block).each(function (index, element) {
        tinymce.EditorManager.execCommand(
            "mceRemoveEditor",
            false,
            $(this).prop("id")
        );
    });
};

CustomBlocks.prototype.addBlockEvents = function (block) {
    var _ = this;

    $("a.blockButton", block).each(function (index, element) {
        $(element).click(function (event) {
            event.preventDefault();
            $(_.container).trigger($(this).data("event"), [block, this]);
        });
    });
};

CustomBlocks.prototype.refresh = function () {
    var _ = this;

    $(".blockCount", _.container).val(_.blockCount);
    $(".blockPlaceholder", _.container).css(
        "display",
        _.blockCount > 0 ? "none" : "block"
    );
    $("select.addBlock", _.container).val(""); // Deselect after action
};

// Add the prototype method to jQuery
$.prototype.gibbonCustomBlocks = function (settings) {
    this.gibbonCustomBlocks = new CustomBlocks(this, settings);
    this.data("gibbonCustomBlocks", this.gibbonCustomBlocks);
};

/**
 * Gibbon Data Table: a very basic implementation of jQuery + AJAX powered data tables in Gibbon
 * @param string basePath
 * @param Object settings
 */
var DataTable = window.DataTable || {};

DataTable = function (element, basePath, filters, identifier) {
    var _ = this;

    _.table = $(element);
    _.path = basePath + " #" + $(element).attr("id") + " > .dataTable";
    _.filters = filters;
    _.identifier = identifier;
    if (_.filters.sortBy.length == 0) _.filters.sortBy = {};
    if (_.filters.filterBy.length == 0) _.filters.filterBy = {};

    _.init();
};

DataTable.prototype.init = function () {
    var _ = this;

    // Pagination
    $(_.table).on("click", ".paginate", function () {
        var resultCount = $(".dataTable", _.table).data("results");
        _.filters.pageMax = Math.ceil(resultCount / _.filters.pageSize);
        _.filters.page = Math.min($(this).data("page"), _.filters.pageMax);
        _.refresh();
    });

    // Sortable Columns
    $(_.table).on("click", ".column.sortable", function (event) {
        var columns = $(this).data("sort").split(",");

        // Hold shift to add columns to the sort (or toggle them), otherwise clear it each time.
        var activeColumns = columns.filter(function (item) {
            return item in _.filters.sortBy;
        });
        if (activeColumns.length == 0 && !event.shiftKey) _.filters.sortBy = {};

        columns.forEach(function (column) {
            _.filters.sortBy[column] =
                _.filters.sortBy[column] == "ASC" ? "DESC" : "ASC";
        });

        _.refresh();
    });

    // Remove Filter
    $(_.table).on("click", ".filter", function () {
        var filter = $(this).data("filter");

        if ($(this).hasClass("clear")) {
            _.filters.filterBy = { "": "" };
            _.filters.searchBy.columns = [""];
        } else if (filter in _.filters.filterBy) {
            // Remove columns from search criteria if removing an in: filter
            if (filter == "in") _.filters.searchBy.columns = [""];
            delete _.filters.filterBy[filter];
        }

        if (jQuery.isEmptyObject(_.filters.filterBy))
            _.filters.filterBy = { "": "" };

        _.filters.page = 1;
        _.refresh();
    });

    // Add Filter
    $(_.table).on("change", ".filters", function () {
        var filterData = $(this).val().split(":");
        var filter = filterData[0];
        var value = filterData[1];

        _.filters.filterBy[filter] = value;
        _.filters.page = 1;

        _.refresh();
    });

    // Page Size
    $(_.table).on("change", ".limit", function () {
        var resultCount = $(".dataTable", _.table).data("results");
        _.filters.pageSize = parseInt($(this).val());
        _.filters.pageMax = Math.ceil(resultCount / _.filters.pageSize);
        _.filters.page = Math.min(_.filters.page, _.filters.pageMax);
        _.refresh();
    });
};

DataTable.prototype.refresh = function () {
    var _ = this;

    var submitted = setTimeout(function () {
        $(".pagination", _.table).prepend('<span class="submitted"></span>');
    }, 500);

    var postData = {};

    if (_.identifier != "") {
        postData[_.identifier] = _.filters;
    } else {
        postData = _.filters;
    }

    $(_.table).load(
        _.path,
        postData,
        function (responseText, textStatus, jqXHR) {
            $(".bulkActionPanel").addClass("hidden");
            clearTimeout(submitted);
            htmx.process(this);
            htmx.trigger(this, 'htmx:load');
        }
    );
};

$.prototype.gibbonDataTable = function (basePath, filters, identifier) {
    this.gibbonDataTable = new DataTable(this, basePath, filters, identifier);
};

/**
 * Multi Selects
 */
// Define the MultiSelect behaviour
var MultiSelect = window.MultiSelect || {};

MultiSelect = function (element, name) {
    var _ = this;

    _.container = $(element);
    _.selectSource = $("#" + name + "Source", element);
    _.selectDestination = $("#" + name, element);
    _.name = name;
    _.sortBy = $("#" + name + "Sort", element);

    _.init();
};

MultiSelect.prototype.init = function () {
    var _ = this;

    $("#" + _.name + "Add").click(function () {
        _.transferOption(true);
    });

    $("#" + _.name + "Remove", _.container).click(function () {
        _.transferOption(false);
    });

    var form = _.container.parents("form");

    // Select all options on submit so we can validate this select input.
    $("input[type='Submit'],button[type='Submit'],button[value~='Save']", form).click(function () {
        $("option", _.selectDestination).each(function () {
            $(this).prop("selected", true);
        });
        document.getElementById(_.name).dispatchEvent(new Event('change'));
    });

    _.sortBy.change(function () {
        _.sortSelects();
    });

    $("#" + _.name + "Search", _.container).keyup(function () {
        var search = $(this).val().toLowerCase();
        $("option", _.selectSource).each(function () {
            var option = $(this);
            if (option.text().toLowerCase().includes(search)) {
                option.show();
            } else {
                option.hide();
            }
        });
    });
};

MultiSelect.prototype.transferOption = function (add) {
    var _ = this;

    var selectFrom = add ? _.selectSource : _.selectDestination;
    var selectTo = add ? _.selectDestination : _.selectSource;

    selectFrom.find("option:selected").each(function () {
        var opt = $(this).clone();
        if ($(this).parent().is("optgroup")) {
            var optgroupnew = $(
                "optgroup[label='" + $(this).parent().attr("label") + "']",
                selectTo
            );
            if (optgroupnew.length == 0) {
                optgroupnew = $(this).parent().clone().html("");
                selectTo.append(optgroupnew);
            }
            opt.data("parent", optgroupnew);
            optgroupnew.append(opt);
        } else {
            selectTo.append(opt);
        }
        $(this).detach().remove();
    });

    _.sortSelects();

    selectTo.change().focus();
};

MultiSelect.prototype.sortSelects = function () {
    var _ = this;

    var values = null;

    var sortBy = null;
    if (_.sortBy.length !== 0) {
        sortBy = _.sortBy.val();
    }

    if (sortBy != null && sortBy != "Sort by Name") {
        values = _.container.data("sortable")[sortBy];
    }

    _.sortSelect(_.selectSource, values);
    _.sortSelect(_.selectDestination, values);
};

MultiSelect.prototype.sortSelect = function (list, sortValues) {
    var _ = this;

    $("optgroup", list).each(function () {
        _.sortSelect($(this), sortValues);
    });

    var options = $("option", list);
    if (list.is("select")) {
        options = options.not("optgroup option");
    }

    if (sortValues == null) {
        sortValues = {};
    }

    var arr = options
        .map(function (_, o) {
            return {
                tSort: sortValues[o.value] + $(o).text(),
                t: $(o).text(),
                v: o.value,
            };
        })
        .get();
    arr.sort(function (o1, o2) {
        return o1.tSort > o2.tSort ? 1 : o1.tSort < o2.tSort ? -1 : 0;
    });
    options.each(function (i, o) {
        o.value = arr[i].v;
        $(o).text(arr[i].t);
    });
};

// Add the prototype method to jQuery
$.prototype.gibbonMultiSelect = function (name) {
    this.gibbonMultiSelect = new MultiSelect(this, name);
};

function debounce(func, timeout) {
    timeout = timeout || 300;

    var timer;

    return function () {
        clearTimeout(timer);
        var args = arguments;
        timer = setTimeout(function () {
            func.apply(this, args);
        }, timeout);
    };
}

/**
 * Store a map of debounced functions for AJAX form submissions
 *
 * @type {Record<string, Function>}
 */
var __GIBBON_URL_DEBOUNCE_MAP = {};

/**
 * Gibbon Form Submit: a generic form submit function that can be used to submit forms via AJAX
 *
 * @param {HTMLFormElement} form
 */
function gibbonFormSubmitQuiet(form, url) {
    var submitData = $(form).serialize();

    if (!__GIBBON_URL_DEBOUNCE_MAP[url]) {
        __GIBBON_URL_DEBOUNCE_MAP[url] = debounce(function (submitData) {
            $.ajax({
                type: "POST",
                data: submitData,
                url: url,
            });
        });
    }

    __GIBBON_URL_DEBOUNCE_MAP[url](submitData);
}
