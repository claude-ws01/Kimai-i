/*! ki_timesheets */
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/claude-ws01/Kimai-i
 * (c) 2015 Claude Nadon  https://github.com/claude-ws01
 * (c) 2006-2009 Kimai-Development-Team // http://www.kimai.org
 *
 * Kimai-i is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai-i is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Javascript functions used in the timesheet extension.
 */

/**
 * Called when the extension loaded. Do some initial stuff.
 */

function ts_ext_onload() {

    set_lists_visibility(true, $('#gui').find('div.ext.ki_timesheet').attr('id'));

    ts_ext_applyHoverIntent();
    ts_ext_resize();
    $("#loader").hide();
}

/**
 * Update the dimension variables to reflect new height and width.
 */
function ts_ext_get_dimensions() {

    (customerShrinkMode) ? subtableCount = 2 : subtableCount = 3;
    subtableWidth = (pageWidth() - 10) / subtableCount - 7;

    timeSheet_width = pageWidth() - 24;
    timeSheet_height = pageHeight() - 224 - headerHeight() - 28;
}

/**
 * Hover a row if the mouse is over it for more than half a second.
 */
function ts_ext_applyHoverIntent() {

    var ts_tr = $('#ts_m_tbl').find('tr');

    ts_tr.hoverIntent({
        sensitivity: 1,
        interval: 500,
        over: function () {
            ts_tr.removeClass('hover');
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
function ts_ext_resize() {

    ts_ext_set_tableWrapperWidths();
    ts_ext_set_heightTop();
}

/**
 * Set width of table and faked table head.
 */
function ts_ext_set_tableWrapperWidths() {

    ts_ext_get_dimensions();
    $("#ts_head,#ts_main").css("width", timeSheet_width);
    ts_ext_set_TableWidths();
}

/**
 * If the extension is being shrinked so the sublists are shown larger
 * adjust to that.
 */
function ts_ext_set_heightTop() {

    ts_ext_get_dimensions();

    if (!extensionShrinkMode) {
        $("#ts_main").css("height", timeSheet_height);
    } else {
        $("#ts_main").css("height", "70px");
    }

    ts_ext_set_TableWidths();
}

/**
 * Set the width of the table.
 */
function ts_ext_set_TableWidths() {

    var scr,
        ts_main = $("#ts_main"),

        ts_h_tbl = $("#ts_h_tbl"),
        ts_m_tbl = $("#ts_m_tbl"),

        ts_h_tr = ts_h_tbl.find("tbody>tr"),
        ts_m_tr = ts_m_tbl.find("tbody>tr"),

        ref_code_head = ts_h_tbl.find("col.ref_code");

    ts_ext_get_dimensions();

    // set table widths   
    (ts_main.innerHeight() - ts_m_tbl.outerHeight() > 0) ? scr = 0 : scr = scroller_width; // width of timesheet table depending on scrollbar or not

    ts_h_tbl.css("width", timeSheet_width - scr);
    ts_m_tbl.css("width", timeSheet_width - scr);

    // manage width of DATA column
    if (ts_m_tr.find("td.option").width() != null) {
        ts_h_tr.find("td.option").css("width", ts_m_tr.find("td.option").width());
    }
    ts_h_tr.find("td.date").css("width", ts_m_tr.find("td.date").width());
    ts_h_tr.find("td.from").css("width", ts_m_tr.find("td.from").width());
    ts_h_tr.find("td.to").css("width", ts_m_tr.find("td.to").width());
    ts_h_tr.find("td.time").css("width", ts_m_tr.find("td.time").width());
    ts_h_tr.find("td.wage").css("width", ts_m_tr.find("td.wage").width());
    ts_h_tr.find("td.customer").css("width", ts_m_tr.find("td.customer").width());
    ts_h_tr.find("td.project").css("width", ts_m_tr.find("td.project").width());
    ts_h_tr.find("td.activity").css("width", ts_m_tr.find("td.activity").width());

    if (ref_code_head.width() != null) {
        ref_code_head.css("width", ts_m_tr.find("td.ref_code").width());
    }

    ts_h_tr.find("td.username").css("width", ts_m_tr.find("td.username").width());

}

function ts_ext_tab_changed() {

    $('#display_total').html(ts_total);

    if (timesheet_timeframe_changed_hook_flag) {
        ts_ext_reload();
        timesheet_customers_changed_hook_flag = 0;
        timesheet_projects_changed_hook_flag = 0;
        timesheet_activities_changed_hook_flag = 0;
    }
    if (timesheet_customers_changed_hook_flag) {
        ts_ext_customers_changed();
        timesheet_projects_changed_hook_flag = 0;
        timesheet_activities_changed_hook_flag = 0;
    }
    if (timesheet_projects_changed_hook_flag) {
        ts_ext_projects_changed();
    }
    if (timesheet_activities_changed_hook_flag) {
        ts_ext_activities_changed();
    }

    timesheet_timeframe_changed_hook_flag = 0;
    timesheet_customers_changed_hook_flag = 0;
    timesheet_projects_changed_hook_flag = 0;
    timesheet_activities_changed_hook_flag = 0;
}

function ts_ext_timeframe_changed() {

    if ($('div.ext.ki_timesheet').css('display') == "block") {
        ts_ext_reload();
    } else {
        timesheet_timeframe_changed_hook_flag++;
    }
}

function ts_ext_customers_changed() {
    if ($('div.ext.ki_timesheet').css('display') == "block") {
        ts_ext_reload();
    } else {
        timesheet_customers_changed_hook_flag++;
    }
}

function ts_ext_projects_changed() {
    if ($('div.ext.ki_timesheet').css('display') == "block") {
        ts_ext_reload();
    } else {
        timesheet_projects_changed_hook_flag++;
    }
}

function ts_ext_activities_changed() {
    if ($('div.ext.ki_timesheet').css('display') == "block") {
        ts_ext_reload();
    } else {
        timesheet_activities_changed_hook_flag++;
    }
}

// ----------------------------------------------------------------------------------------
// reloads timesheet, customer, project and activity tables
function ts_ext_reload() {

    $('#ajax_wait').show();
    $.post(ts_ext_path + "processor.php", {
            axAction: "reload_timeSheet",
            axValue: filterUsers.join(":") + '|' + filterCustomers.join(":") + '|' + filterProjects.join(":") + '|' + filterActivities.join(":"),
            id: 0,
            first_day: new Date($('#pick_in').val()).getTime() / 1000,
            last_day: new Date($('#pick_out').val()).getTime() / 1000
        },
        function (data) {
            $('#ajax_wait').hide();
            $("#ts_main").html(data);

            ts_ext_set_TableWidths();
            ts_ext_applyHoverIntent();
        }
    );
}

// ----------------------------------------------------------------------------------------
// reloads timesheet, customer, project and activity tables
function ts_ext_reload_activities(project, noUpdateRate, activity, timeSheetEntry) {

    var selector = $('#add_edit_timeSheetEntry_activityID'),
        previousValue = selector.val();

    $('#ajax_wait').show();
    $.post(ts_ext_path + "processor.php", {
            axAction: "reload_activities_options",
            axValue: 0,
            id: 0,
            project: project},

        function (data) {
            $('#ajax_wait').hide();
            delete window['__cacheselect_add_edit_timeSheetEntry_activityID'];
            selector.html(data);
            selector.val(previousValue);
            if (noUpdateRate == undefined)
                ts_ext_getBestRates();
            if (activity > 0) {
                $.getJSON("../extensions/ki_timesheet/processor.php", {
                        axAction: "budgets",
                        project_id: project,
                        activity_id: activity,
                        timeSheetEntryID: timeSheetEntry
                    },
                    function (data) {
                        ts_ext_updateBudget(data);
                    }
                );
            }
        }
    );
}

//----------------------------------------------------------------------------------------
//reloads budget
//
// everything in data['timeSheetEntry'] has to be subtracted in case the time sheet entry is in the db already
// part of this activity. In other cases, we already took case on server side that the values are 0
function ts_ext_updateBudget(data) {

    var budget, approved, budgetUsed, durationArray, rate, secs,
        budget_val = $('#budget_val'),
        approved_val = $('#approved');


    budget = data['activityBudgets']['budget'];

    // that is the case if we changed the project and no activity is selected
    if (isNaN(budget)) {
        budget = 0;
    }
    if (budget_val.val() != '') {
        budget += parseFloat(budget_val.val());
    }

    budget -= data['timeSheetEntry']['budget'];
    $('#budget_activity').text(budget);
    approved = data['activityBudgets']['approved'];


    // that is the case if we changed the project and no activity is selected
    if (isNaN(approved)) {
        approved = 0;
    }
    if (approved_val.val() != '') {
        approved += parseFloat(approved_val.val());
    }
    approved -= data['timeSheetEntry']['approved'];
    $('#budget_activity_approved').text(approved);

    budgetUsed = data['activityUsed'];
    if (isNaN(budgetUsed)) {
        budgetUsed = 0;
    }

    durationArray = $("#duration").val().split(/:|\./);
    if (end != null && durationArray.length > 0 && durationArray.length < 4) {
        secs = durationArray[0] * 3600;
        if (durationArray.length > 1)
            secs += (durationArray[1] * 60);
        if (durationArray.length > 2)
            secs += parseInt(durationArray[2]);
        rate = $('#rate').val();
        if (rate != '') {
            budgetUsed += secs / 3600 * rate;
            budgetUsed -= data['timeSheetEntry']['duration'] / 3600 * data['timeSheetEntry']['rate'];
        }
    }
    $('#budget_activity_used').text(Math.round(budgetUsed, 2));
}

// ----------------------------------------------------------------------------------------
// this function is attached to the little green arrows in front of each timesheet record
// and starts recording that activity anew
function ts_ext_recordAgain(project, activity, id) {

    var tda = $('#ts_' + id + '>td>a');

    tda.blur();

    if (currentRecording > -1) {
        stopRecord();
    }

    $('#ts_' + id + '>td>a.recordAgain>img').attr("src", "../grfx/loading13.gif");
    hour = 0;
    min = 0;
    sec = 0;
    now = Math.floor(((new Date()).getTime()) / 1000);
    offset = now;
    startsec = 0;
    show_stopwatch();
    tda.removeAttr('onClick');

    $('#ajax_wait').show();
    $.post(ts_ext_path + "processor.php", {
            axAction: "record",
            axValue: 0,
            id: id},

        function (data) {
            if (data.errors.length > 0) {
                $('#ajax_wait').hide();
                return;
            }


            var customer = data.customer;
            var customerName = data.customer_name;
            var projectName = data.project_name;
            var activityName = data.activity_name;
            currentRecording = data.current_recording;

            ts_ext_reload();
            buzzer_preselect_project(project, projectName, customer, customerName, false);
            buzzer_preselect_activity(activity, activityName, 0, 0, false);
            $("#ticker_customer").html(customerName);
            $("#ticker_project").html(projectName);
            $("#ticker_activity").html(activityName);
        }
    );
}

// ----------------------------------------------------------------------------------------
// this function is attached to the little green arrows in front of each timesheet record
// and starts recording that activity anew
function ts_ext_stopRecord(id) {

    var td = $('#ts_' + id + '>td'),
        tda = $('#ts_' + id + '>td>a');

    ticktack_off();
    show_selectors();

    if (id) {
        //td.css("background-color", "#F00");
        $('#ts_' + id + '>td>a.stop>img').attr("src", "../grfx/loading13_red.gif");
        tda.blur();
        tda.removeAttr('onClick');
        td.css({"color": "#FFF", "background-color": "#F00"});
    }

    $('#ajax_wait').show();
    $.post(ts_ext_path + "processor.php", {
            axAction: "stop",
            axValue: 0,
            id: id},

        function (data) {
            $('#ajax_wait').hide();
            ts_ext_reload();
        }
    );
}

// ----------------------------------------------------------------------------------------
// delete a timesheet record immediately
function ts_ext_quickdelete(id) {

    var tda = $('#ts_' + id + '>td>a');

    tda.blur();

    if (confirmText != undefined) {
        var check = confirm(confirmText);
        if (check == false) return;
    }

    tda.removeAttr('onClick');
    $('#ts_' + id + '>td>a.quickdelete>img').attr("src", "../grfx/loading13.gif");

    $('#ajax_wait').show();
    $.post(ts_ext_path + "processor.php", {
            axAction: "quickdelete",
            axValue: 0,
            id: id},

        function (result) {
            $('#ajax_wait').hide();

            if (result.errors.length == 0) {
                ts_ext_reload();
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
function ts_ext_editRecord(id) {

    floaterShow(ts_ext_path + "floaters.php", "add_edit_timeSheetEntry", 0, id, 650);
}

// ----------------------------------------------------------------------------------------
// refresh the rate with a new value, if this is a new entry
function ts_ext_getBestRates() {

    $.getJSON(ts_ext_path + "processor.php", {
            axAction: "bestFittingRates",
            axValue: 0,
            project_id: $("#add_edit_timeSheetEntry_projectID").val(),
            activity_id: $("#add_edit_timeSheetEntry_activityID").val()
        },
        function (data) {
            if (data.errors.length > 0) return;

            var select = $("#ts_ext_form_add_edit_timeSheetEntry"),
                fixedRate = select.find("#fixedRate"),
                rate = select.find("#rate");

            if (data.hourly_rate != false) {
                rate.val(data.hourly_rate);
            }

            if (data.fixed_rate != false) {
                fixedRate.val(data.fixed_rate);
            }
        }
    );
}

// ----------------------------------------------------------------------------------------
// Returns a Date object, based on 2 strings
function ts_ext_getDateFromStrings(dateStr, timeStr) {

    var result = new Date();
    var dateArray = dateStr.split(/\./);
    var timeArray = timeStr.split(/:|\./);

    if (dateArray.length != 3 || timeArray.length < 1 || timeArray.length > 3) {
        return null;
    }
    result.setFullYear(dateArray[2], dateArray[1] - 1, dateArray[0]);
    if (timeArray[0].length > 2) {
        result.setHours(timeArray[0].substring(0, 2));
        result.setMinutes(timeArray[0].substring(2, 4));
    }
    else
        result.setHours(timeArray[0]);
    if (timeArray.length > 1)
        result.setMinutes(timeArray[1]);
    else
        result.setMinutes(0);
    if (timeArray.length > 2)
        result.setSeconds(timeArray[2]);
    else
        result.setSeconds(0);
    return result;
}

// ----------------------------------------------------------------------------------------
// pastes the current date and time in the outPoint field of the
// change dialog for timesheet entries 
//         $view->pasteValue = date("d.m.Y - H:i:s",$kga['now']);
function ts_ext_pasteNowToStart() {

    whichTimeWasSet = 'start';
    ts_ext_pasteNowTotime($("#start_time"), $("#start_day"))
}

function ts_ext_pasteNowToEnd() {

    whichTimeWasSet = 'end';
    ts_ext_pasteNowTotime($("#end_time"), $("#end_day"))
}

function ts_ext_pasteNowTotime(timeSelector, dateSelector) {
    var H, i, time;

    now = new Date();

    H = now.getHours();
    i = now.getMinutes();

    if (H < 10) H = "0" + H;
    if (i < 10) i = "0" + i;

    time = H + ":" + i;

    timeSelector.val(time);
    timeSelector.trigger('change');

    dateSelector.datepicker("setDate", now);
}

// ----------------------------------------------------------------------------------------
// Gets the begin Date, while editing a timesheet record
function ts_ext_getStartDate() {

    return ts_ext_getDateFromStrings($("#start_day").val(), $("#start_time").val());
}

// ----------------------------------------------------------------------------------------
// Gets the end Date, while editing a timesheet record
function ts_ext_getEndDate() {

    return ts_ext_getDateFromStrings($("#end_day").val(), $("#end_time").val());
}

// ----------------------------------------------------------------------------------------
function ts_ext_durationToTime() {

    if (whichTimeWasSet == 'end') {
        ts_ext_durationToTimeCalc($("#start_time"), $("#start_day"), -1)
    }
    else {
        ts_ext_durationToTimeCalc($("#end_time"), $("#end_day"), 1)
    }
}

// ----------------------------------------------------------------------------------------
// Change the end time field, based on the duration, while editing a timesheet record
function ts_ext_durationToTimeCalc(timeSelector, dateSelector, multiplier) {

    var toDate, secs, H, i, d, m, y,
        fromDate = ts_ext_getEndDate(),
        durationArray = $("#duration").val().split(/:|\./);

    if (multiplier < 0) {
        fromDate = ts_ext_getEndDate();
    }
    else {
        fromDate = ts_ext_getStartDate();
    }

    if (fromDate != null
        && durationArray.length > 0
        && durationArray.length < 4
    ) {

        whichTimeWasSet = 'duration';

        secs = durationArray[0] * 3600;
        if (durationArray.length > 1)
            secs += (durationArray[1] * 60);

        if (durationArray.length > 2)
            secs += parseInt(durationArray[2]);

        toDate = new Date();
        toDate.setTime(fromDate.getTime() + (secs * 1000 * multiplier));


        H = toDate.getHours();
        i = toDate.getMinutes();

        if (H < 10) H = "0" + H;
        if (i < 10) i = "0" + i;

        timeSelector.val(H + ":" + i);

        d = toDate.getDate();
        m = toDate.getMonth() + 1;
        y = toDate.getFullYear();
        if (d < 10) d = "0" + d;
        if (m < 10) m = "0" + m;

        dateSelector.val(d + "." + m + "." + y);
    }
}

// ----------------------------------------------------------------------------------------
// Change the duration field, based on the time, while editing a timesheet record
function ts_ext_startTimeToDuration() {

    if (whichTimeWasSet == 'duration') {
        // mod end
        ts_ext_durationToTimeCalc($("#end_time"), $("#end_day"), 1)
    }
    else {
        // mod duration
        ts_ext_timeToDuration();
    }
    whichTimeWasSet = 'start';
}

function ts_ext_endTimeToDuration() {
    if (whichTimeWasSet == 'duration') {
        // mod start
        ts_ext_durationToTimeCalc($("#start_time"), $("#start_day"), -1)
    }
    else {
        // mod duration
        ts_ext_timeToDuration();
    }
    whichTimeWasSet = 'end';
}

function ts_ext_timeToDuration() {
    var beginSecs, endSecs, durationSecs, mins, hours,
        duration = $("#duration"),
        begin = ts_ext_getStartDate(),
        end = ts_ext_getEndDate();

    if (begin == null || end == null) {
        duration.val("");
    }

    else {
        beginSecs = Math.floor(begin.getTime() / 1000);
        endSecs = Math.floor(end.getTime() / 1000);
        durationSecs = endSecs - beginSecs;

        if (durationSecs < 0) {
            duration.val("");
        }

        else {

            durationSecs = Math.floor(durationSecs / 60);
            mins = durationSecs % 60;
            if (mins < 10)
                mins = "0" + mins;

            hours = Math.floor(durationSecs / 60);
            if (hours < 10)
                hours = "0" + hours;

            duration.val(hours + ":" + mins);
            duration.trigger('change');
        }
    }
}

// ----------------------------------------------------------------------------------------
// shows comment line for timesheet entry
function ts_ext_comment(id) {

    $('#c' + id).toggle();
    return false;
}
