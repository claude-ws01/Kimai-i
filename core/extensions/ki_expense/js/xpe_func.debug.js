/*! ki_expense */
/**
 * This file is part of
 * Kimai - Open Source Time Tracking // http://www.kimai.org
 * (c) 2006-2009 Kimai-Development-Team
 *
 * Kimai is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Javascript functions for the timesheet extension are defined here.
 */

/**
 * Called when the extension loaded. Do some initial stuff.
 */
function xpe_ext_onload() {

    set_lists_visibility(true, $('#gui').find('div.ext.ki_expense').attr('id'));

    xpe_ext_applyHoverIntent();
    xpe_ext_resize();
    $("#loader").hide();
    lists_visible(true);
}

/**
 * Update the dimension variables to reflect new height and width.
 */
function xpe_ext_get_dimensions() {

    (customerShrinkMode) ? subtableCount = 2 : subtableCount = 3;
    subtableWidth = (pageWidth() - 10) / subtableCount - 7;

    xpe_width = pageWidth() - 24;
    xpe_height = pageHeight() - 224 - headerHeight() - 28;
}

/**
 * Hover a row if the mouse is over it for more than half a second.
 */
function xpe_ext_applyHoverIntent() {

    var xpe_m_tbl_tr = $('#xpe_m_tbl').find('tr');

    xpe_m_tbl_tr.hoverIntent({
        sensitivity: 1,
        interval: 500,
        over: function () {
            xpe_m_tbl_tr.removeClass('hover');
            $(this).addClass('hover');
        },
        out: function () {
            $(this).removeClass('hover');
        }
    });
}

/**
 * The window has been resized, we have to adjust to the new space.
 */
function xpe_ext_resize() {

    xpe_ext_set_tableWrapperWidths();
    xpe_ext_set_heightTop();
}

/**
 * Set width of table and faked table head.
 */
function xpe_ext_set_tableWrapperWidths() {

    xpe_ext_get_dimensions();
    $("#xpe_head,#xpe_main").css("width", xpe_width);
    xpe_ext_set_TableWidths();
}

/**
 * If the extension is being shrinked so the sublists are shown larger
 * adjust to that.
 */
function xpe_ext_set_heightTop() {

    xpe_ext_get_dimensions();
    if (!extensionShrinkMode) {
        $("#xpe_main").css("height", xpe_height);
    } else {
        $("#xpe_main").css("height", "70px");
    }

    xpe_ext_set_TableWidths();
}

/**
 * Set the width of the table.
 */
function xpe_ext_set_TableWidths() {

    var scr,
        xpe_h_tbl = $("#xpe_h_tbl"),
        xpe_m_tbl = $("#xpe_m_tbl"),
        xpe_h_tr = xpe_h_tbl.find("tbody>tr"),
        xpe_m_tr = xpe_m_tbl.find("tbody>tr"),
        xpe_main = $("#xpe_main");

    xpe_ext_get_dimensions();

    // width of expenses table depending on scrollbar or not
    (xpe_main.innerHeight() - xpe_m_tbl.outerHeight() > 0) ? scr = 0 : scr = scroller_width;

    // set table widths
    xpe_h_tbl.css("width", xpe_width - scr);
    xpe_m_tbl.css("width", xpe_width - scr);

    // manage width of HEAD column

    if (xpe_m_tr.find("td.option").width() != null) {
        xpe_h_tr.find("td.option").css("width", xpe_m_tr.find("td.option").width());
    }
    xpe_h_tr.find("td.date").css("width", xpe_m_tr.find("td.date").width());
    xpe_h_tr.find("td.time").css("width", xpe_m_tr.find("td.time").width());
    xpe_h_tr.find("td.value").css("width", xpe_m_tr.find("td.value").width());
    xpe_h_tr.find("td.refundable").css("width", xpe_m_tr.find("td.refundable").width());
    xpe_h_tr.find("td.customer").css("width", xpe_m_tr.find("td.customer").width());
    xpe_h_tr.find("td.project").css("width", xpe_m_tr.find("td.project").width());
    xpe_h_tr.find("td.description").css("width", xpe_m_tr.find("td.description").width());
    xpe_h_tr.find("td.username").css("width", xpe_m_tr.find("td.username").width());
}

function xpe_ext_triggerchange() {

    $('#display_total').html(expenses_total);
    if (xpe_timeframe_changed_hook_flag) {
        xpe_ext_reload();
        xpe_customers_changed_hook_flag = 0;
        xpe_projects_changed_hook_flag = 0;
        xpe_activities_changed_hook_flag = 0;
    }
    if (xpe_customers_changed_hook_flag) {
        xpe_ext_triggerCHK();
        xpe_projects_changed_hook_flag = 0;
        xpe_activities_changed_hook_flag = 0;
    }
    if (xpe_projects_changed_hook_flag) {
        xpe_ext_triggerCHP();
    }
    if (xpe_activities_changed_hook_flag) {
        xpe_ext_triggerCHE();
    }

    xpe_timeframe_changed_hook_flag = 0;
    xpe_customers_changed_hook_flag = 0;
    xpe_projects_changed_hook_flag = 0;
    xpe_activities_changed_hook_flag = 0;
}

function xpe_ext_timeframe_changed() {

    if ($('.ki_expense').css('display') == "block") {
        xpe_ext_reload();
    } else {
        xpe_timeframe_changed_hook_flag++;
    }
}

function xpe_ext_triggerCHK() {

    if ($('.ki_expense').css('display') == "block") {
        xpe_ext_reload();
    } else {
        xpe_customers_changed_hook_flag++;
    }
}

function xpe_ext_triggerCHP() {

    if ($('.ki_expense').css('display') == "block") {
        xpe_ext_reload();
    } else {
        xpe_projects_changed_hook_flag++;
    }
}

function xpe_ext_triggerCHE() {

    if ($('.ki_expense').css('display') == "block") {
        xpe_ext_reload();
    } else {
        xpe_activities_changed_hook_flag++;
    }
}

// ----------------------------------------------------------------------------------------
// reloads timesheet, customer, project and activity tables
//
function xpe_ext_reload() {

    $('#ajax_wait').show();
    $.post(xpe_ext_path + "processor.php", {
            axAction: "reload_exp",
            axValue: filterUsers.join(":") + '|' + filterCustomers.join(":") + '|' + filterProjects.join(":"),
            id: 0,
            first_day: new Date($('#pick_in').val()).getTime() / 1000,
            last_day: new Date($('#pick_out').val()).getTime() / 1000
        },

        function (data) {
            $('#ajax_wait').hide();
            var scr,
                xpe_h_tbl = $("#xpe_h_tbl"),
                xpe_m_tbl = $("#xpe_m_tbl"),
                xpe_main  = $("#xpe_main");

            xpe_main.html(data);

            // set expenses table width
            (xpe_main.innerHeight() - xpe_m_tbl.outerHeight() > 0 ) ? scr = 0 : scr = scroller_width; // width of exp table depending on scrollbar or not
            xpe_m_tbl.css("width", xpe_width - scr);

            // stretch refundable column in faked expenses table head
            xpe_m_tbl.find("td.refundable").css("width", xpe_m_tbl.find("td.refundable").width());

            // stretch customer column in faked expenses table head
            xpe_m_tbl.find("td.customer").css("width", xpe_m_tbl.find("td.customer").width());

            // stretch project column in faked expenses table head
            xpe_m_tbl.find("td.project").css("width", xpe_m_tbl.find("td.project").width());
            xpe_ext_applyHoverIntent();
        }
    );
}

// ----------------------------------------------------------------------------------------
// delete a timesheet record immediately
//
function xpe_ext_quickdelete(id) {

    var exp_entry = $('#expensesEntry' + id + '>td>a');

    exp_entry.blur();

    if (confirmText != undefined) {
        var check = confirm(confirmText);
        if (check == false) return;
    }

    exp_entry.removeAttr('onClick');
    exp_entry.find('.quickdelete>img').attr("src", "../skins/standard/grfx/loading13.gif");

    $('#ajax_wait').show();
    $.post(xpe_ext_path + "processor.php", {
            axAction: "quickdelete",
            axValue: 0,
            id: id},

        function (result) {
            $('#ajax_wait').hide();
            if (result.errors.length == 0) {
                xpe_ext_reload();
            }
            else {
                var messages = [];
                for (var index in result.errors)
                    messages.push(result.errors[index]);
                alert(messages.join("\n"));
            }
        }
    );
}

// ----------------------------------------------------------------------------------------
// edit a timesheet record
//
function xpe_ext_editRecord(id) {

    floaterShow(xpe_ext_path + "floaters.php", "add_edit_record", 0, id, 600);
}

// ----------------------------------------------------------------------------------------
// shows comment line for expense entry
//
function xpe_ext_comment(id) {

    $('#xpe_c' + id).toggle();
    return false;
}
// ----------------------------------------------------------------------------------------
// pastes the current date and time in the outPoint field of the
// change dialog for timesheet entries 
//
//         $view->pasteValue = date("d.m.Y - H:i:s",$kga['now']);
//
function xpe_ext_pasteNow() {

    var time, H, i;

    now = new Date();

    H = now.getHours();
    i = now.getMinutes();

    if (H < 10) H = "0" + H;
    if (i < 10) i = "0" + i;

    time = H + ":" + i ;

    $("#edit_time").val(time);
}
