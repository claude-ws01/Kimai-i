/* -*- Mode: jQuery; tab-width: 4; indent-tabs-mode: nil -*- */
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/cloudeasy/Kimai-i
 * (c) 2015 Claude Nadon
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

function logfile(entry) {
    $('#ajax_wait').show();
    $.post("processor.php", {
            axAction: "logfile",
            axValue: entry,
            id: 0
        },

        $('#ajax_wait').hide()
    );
}

// ----------------------------------------------------------------------------------------
// returns the dimensions of the document/page
// (unfortunately those are needed even though the jQ dimensions plugin is loaded by default)
function pageWidth() {
    var
        pw1 = document.body != null ? document.body.clientWidth : null,
        pw2 = document.documentElement && document.documentElement.clientWidth ? document.documentElement.clientWidth : pw1,
        pw = window.innerWidth != null ? window.innerWidth : pw2,
        minwidth;

    // the dimensions plugin seems not to return very accurate results when the window is resized SMALLER ... 
    // often the right margin becomes to thick then
    // return $(window).width();

    minwidth = $('html').css("min-width");
    minwidth = minwidth.replace(/px/, "");

    if (minwidth > 0) {
        if (pw < minwidth) {
            return minwidth;
        } else {
            return pw;
        }
    } else {
        return pw;
    }
}

// ----------------------------------------------------------------------------------------
// shows floating dialog windows based on processor data
function floaterShow(phpFile, axAction, axValue, id, width, callback) {
    var floater = $('#floater');

    if (floater.css("display") == "block") {
        floater.fadeOut(fading_enabled ? 100 : 0, function () {
            floaterLoadContent(phpFile, axAction, axValue, id, width, callback);
        });
    } else {
        floaterLoadContent(phpFile, axAction, axValue, id, width, callback);
    }
}

function floaterLoadContent(phpFile, axAction, axValue, id, width, callback) {
    $("#ajax_wait").show();

    $("#floater").load(phpFile,
        {
            axAction: axAction,
            axValue: axValue,
            id: id
        },
        function () {
            var floater = $('#floater'),
                el, x;

            if (floater[0].innerHTML != '') {
                floater.css({width: width + "px"});

                floater_resize(true);


                //CN uncomment to activate overlay.
                // $('#floater_overlay').fadeIn(fading_enabled ? 100 : 0);

                floater.fadeIn(fading_enabled ? 100 : 0);

                $('#focus').focus();
                $('.extended').hide();
                $('#floater_content').css("height", $('#floater_dimensions').outerHeight() + 5);

                // toggle class of the proberbly existing extended options button
                $(".options").toggle(function () {
                    el = $(this);
                    el.addClass("up");
                    el.removeClass("down");
                    return false;
                }, function () {
                    el = $(this);
                    el.addClass("down");
                    el.removeClass("up");
                    return false;
                });

                if (callback != undefined)
                    callback();
            }
            $("#ajax_wait").hide();
        }

    );
}

function floater_resize(force) {

    var height, width, floaterTab, x, y;
    floater = $('#floater');

    // avoid processing an invisible form.
    if (!force && floater.css('display') == 'none') return;

    height = $(window).height();
    floaterTab = $('#floater_tabs');

    height -= floater.outerHeight() - floater.height(); // floater border and padding
    height -= floaterTab.outerHeight() - floaterTab.height(); // floaterTab border and padding
    height -= $('#floater_handle').outerHeight(true) + $('.menuBackground').outerHeight(true) + $('#formbuttons').outerHeight(true); // other elements heights
    floaterTab.css({'max-height': height + "px"});

    y = ($(window).height() - floater.height()) / 2;
    if (y < 0) y = 0;
    
    x = ($(window).width() - floater.width()) / 2;
    if (x < 0) x = 0;
    floater.css({top: y + "px", left: x + 'px'});
}

// ----------------------------------------------------------------------------------------
// hides dialog again
function floaterClose() {

    $("#floater").fadeOut(fading_enabled ? 100 : 0);
    $('#floater_overlay').fadeOut(fading_enabled ? 100 : 0);
}

// ----------------------------------------------------------------------------------------
// change extension by tab
function changeTab(target, path) {
    var flipTabs = $('#fliptabs li'),
        loader = $("#loader"),
        flipTabsAct = $('#fliptabs li.act'),
        exttab_id, div, is_extension_loaded;

    kill_reg_timeouts();

    flipTabs.removeClass('act');
    flipTabs.addClass('norm');

    exttab_id = '#exttab_' + target;
    $(exttab_id).removeClass('norm');
    $(exttab_id).addClass('act');

    $('.ext').css('display', 'none');

    div = '#extdiv_' + target;
    $(div).css('display', 'block');

    // we don't want to load the tab content every time the tab is activated ...
    is_extension_loaded = $(div).html();

    if (!is_extension_loaded || lists_visibility['extdiv_' + target] == undefined) {
        loader.show();
        lists_visible(lists_visibility['extdiv_' + target]);
        path = '../extensions/' + $(exttab_id).data().path;
        $(div).load(path);
    }

    else {
        loader.hide();
        // restore visibility of lists
        lists_visible(lists_visibility['extdiv_' + target]);
        lists_write_annotations();
    }

    if (user_id) {
        $.cookie('ki_active_tab_target_' + user_id, target,";path=/");
        $.cookie('ki_active_tab_path_' + user_id, path,";path=/");
    }
}

// ----------------------------------------------------------------------------------------
function set_lists_visibility(visible, id) {

    lists_visibility[id] = visible;
    lists_visible(visible);
}

// ----------------------------------------------------------------------------------------
function lists_visible(visible) {

    if (visible) {
        lists_resize();
        $('body>.lists').show();
        lists_resize();
    }
    else
        $('body>.lists').hide();
}

// ----------------------------------------------------------------------------------------
function kill_timeout(to) {
    var evalstring = "try {if (" + to + ") clearTimeout(" + to + ")}catch(e){}";
    eval(evalstring);
}

// ----------------------------------------------------------------------------------------
function showTools() {
    $('#main_tools_menu').fadeIn(fading_enabled ? 100 : 0);
}

// ----------------------------------------------------------------------------------------
function hideTools() {
    $('#main_tools_menu').fadeOut(fading_enabled ? 100 : 0);
}

// ----------------------------------------------------------------------------------------
// checks if a new stable Kimai version is available for download
function checkupdate(path) {

    $('#ajax_wait').show();
    $.post('core/checkupdate.php',
        function (response) {
            $('#ajax_wait').hide();
            $('#checkupdate').html(response);
        }
    );
}

function demoCountDown() {
    //demoNextReset
    var n_separator = ':',
        hours,
        minutes,
        seconds,
        hourString = "";

    hours = Math.floor(demoNextReset / (60 * 60));
    minutes = Math.floor((demoNextReset - (hours * 3600)) / (60));
    seconds = demoNextReset - (minutes * 60) - (hours * 3600);
    demoNextReset--;

    if (demoNextReset < 0 ) {
        demoNextReset = demoResetTime;
    }


    if (hours < 10) {
        hourString += "0" + hours;
    }
    else {
        hourString += hours;
    }

    hourString += n_separator;

    if (minutes < 10) {
        hourString += "0" + minutes;
    } else {
        hourString += minutes;
    }

    hourString += n_separator;


    if (seconds < 10) {
        hourString += "0" + seconds;
    } else {
        hourString += seconds;
    }

    $('#demo_countdown').html(hourString);
    setTimeout("demoCountDown()", 1000);

}

function n_hour() {

    var n_seperator = "<span style=\"color:#EAEAD7;\">:</span>",
        hours,
        minutes,
        seconds,
        hourString = "";

    cur_date = new Date();
    hours = cur_date.getHours();
    minutes = cur_date.getMinutes();
    seconds = cur_date.getSeconds();

    if (currentDay != cur_date.getDate()) {
        // it's the next day
        $('#n_date').html(weekdayNames[cur_date.getDay()] + " " + strftime(timeframeDateFormat, cur_date));
        currentDay = cur_date.getDate();

        // If the difference to the datepicker end date is less than one and a half day.
        // One day is exactly when we need to switch. Some more time is given (but not 2 full days).
        if (cur_date - $('#pick_out').datepicker("getDate") < 1.5 * 24 * 60 * 60 * 1000) {
            setTimeframe(undefined, cur_date);
        }
    }

    if (hours < 10) {
        hourString += "0" + hours;
    }
    else {
        hourString += hours;
    }

    if (seconds % 2 == 0) {
        hourString += n_seperator;
    }
    else {
        hourString += ":";
    }

    if (minutes < 10) {
        hourString += "0" + minutes;
    } else {
        hourString += minutes;
    }

    $('#n_hour').html(hourString);
    setTimeout("n_hour()", 1000);
}

// ----------------------------------------------------------------------------------------
// grabs entered timeframe and writes it to database
// after that it reloads all tables
function setTimeframe(fromDate, toDate) {

    timeframe = '';

    if (fromDate != undefined) {
        setTimeframeStart(fromDate);
        timeframe += strftime('%m-%d-%Y', fromDate);
    }
    else {
        timeframe += "0-0-0";
    }

    timeframe += "|";

    if (toDate != undefined) {
        setTimeframeEnd(toDate);
        timeframe += strftime('%m-%d-%Y', toDate);
    }
    else {
        timeframe += "0-0-0";
    }

    $('#ajax_wait').show();
    $.post("processor.php", {
            axAction: "setTimeframe",
            axValue: timeframe, id: 0},

        function (response) {
            $('#ajax_wait').hide();
            hook_timeframe_changed();
        }
    );

    updateTimeframeWarning();
}

function setTimeframeStart(fromDate) {
    $('#ts_in').html(strftime(timeframeDateFormat, fromDate));
    $('#pick_in').val(strftime('%m/%d/%Y', fromDate));
    $('#pick_out').datepicker("option", "minDate", fromDate);
}

function setTimeframeEnd(toDate) {
    $('#ts_out').html(strftime(timeframeDateFormat, toDate));
    $('#pick_out').val(strftime('%m/%d/%Y', toDate));
    $('#pick_in').datepicker("option", "maxDate", toDate);
}

function updateTimeframeWarning() {
    var today = new Date();

    today.setMilliseconds(0);
    today.setSeconds(0);
    today.setMinutes(0);
    today.setHours(0);

    if (new Date($('#pick_out').val()) < today) {
        $('#ts_out').addClass('datewarning')
    }
    else {
        $('#ts_out').removeClass('datewarning')
    }

}

// ----------------------------------------------------------------------------------------
// starts a new recording when the start-buzzer is hidden
function startRecord(projectID, activityID, userID) {
    var value;

    hour = 0;
    min = 0;
    sec = 0;
    value = projectID + "|" + activityID;

    $('#ajax_wait').show();
    $.post("processor.php", {
            axAction: "start_record",
            axValue: value,
            id: userID},

        function (response) {
            $('#ajax_wait').hide();
            var data = jQuery.parseJSON(response);

            currentRecording = data['id'];
            now = Math.floor(((new Date()).getTime()) / 1000);
            offset = 0;
            startsec = now;
            show_stopwatch();

            buzzer.removeClass('disabled');
            ts_ext_reload();
        }
    );
}

// ----------------------------------------------------------------------------------------
// stops the current recording when the stop-buzzer is hidden
function stopRecord() {
    $("#ts_m_tbl>tbody>tr#ts_" + currentRecording + ">td>a.stop>img").attr("src", "../grfx/loading13_red.gif");
    $("#ts_m_tbl>tbody>tr#ts_" + currentRecording + ">td").css("background-color", "#F00");
    $("#ts_m_tbl>tbody>tr#ts_" + currentRecording + ">td").css("color", "#FFF");
    show_selectors();

    $('#ajax_wait').show();
    $.post("processor.php", {
            axAction: "stop_record",
            axValue: 0,
            id: currentRecording},

        function () {
            $('#ajax_wait').hide();
            ts_ext_reload();
            document.title = default_title;
            if (open_after_recorded)
                ts_ext_editRecord(currentRecording);
        }
    );
}

function updateRecordStatus(record_ID, customerID, customerName, projectID, projectName, activityID, activityName) {

    if (record_ID == false) {
        // no recording is running anymore
        currentRecording = -1;
        show_selectors();
        return;
    }

    if (selected_project != projectID)
        buzzer_preselect_project(projectID, projectName, customerID, customerName, false);
}

function show_stopwatch() {
    $("#preselector").css('display', 'none');
    $("#stopwatch").css('display', 'block');
    buzzer.addClass("act");
    $("#ticker_customer").html($("#selected_customer").html());
    $("#ticker_project").html($("#selected_project").html());
    $("#ticker_activity").html($("#selected_activity").html());
    $("ul#ticker").newsticker();
    ticktac();
}

function show_selectors() {

    ticktack_off();
    $("#preselector").css('display', 'block');
    $("#stopwatch").css('display', 'none');
    buzzer.removeClass("act");
    if (!(selected_customer && selected_project && selected_activity)) {
        buzzer.addClass('disabled');
    }
}

function buzzer_onclick() {

    if (currentRecording == -1 && buzzer.hasClass('disabled')) return;


    if (currentRecording > -1) {
        stopRecord();
        currentRecording = -1;
    } else {
        setTimeframe(undefined, new Date());
        startRecord(selected_project, selected_activity, user_id);
        buzzer.addClass('disabled');
    }
}

function buzzer_preselect_project(projectID, projectName, customerID, customerName, updateRecording) {
    var sCust = $("#selected_customer");

    selected_customer = customerID;
    selected_project = projectID;

    $('#ajax_wait').show();
    $.post("processor.php", {
        axAction: "saveBuzzerPreselection",
        project: projectID},

        $('#ajax_wait').hide()
    );

    sCust.html(customerName);

    $("#selected_project").html(projectName);
    sCust.removeClass("none");

    lists_reload('activity', function () {
        buzzer_preselect_update_ui('projects', projectID, updateRecording);
    });
}

function buzzer_preselect_activity(activityID, activityName, updateRecording) {

    selected_activity = activityID;

    $('#ajax_wait').show();
    $.post("processor.php", {
        axAction: "saveBuzzerPreselection",
        activity: activityID},

        $('#ajax_wait').hide()
    );

    $("#selected_activity").html(activityName);

    buzzer_preselect_update_ui('activities', activityID, updateRecording);
}

function buzzer_preselect_update_ui(selector, selectedID, updateRecording) {

    if (updateRecording == undefined) {
        updateRecording = true;
    }

    $('#' + selector + '>table>tbody>tr>td>a.preselect>img').attr('src', '../grfx/preselect_off.png');
    $('#' + selector + '>table>tbody>tr>td>a.preselect#ps' + selectedID + '>img').attr('src', '../grfx/preselect_on.png');
    $('#' + selector + '>table>tbody>tr>td>a.preselect#ps' + selectedID).blur();

    if (selected_project && selected_activity && $('#activities>table>tbody>tr>td>a.preselect>img[src$="preselect_on.png"]').length > 0) {
        buzzer.removeClass('disabled');
    }
    else
        return;

    $("#ticker_customer").html($("#selected_customer").html());
    $("#ticker_project").html($("#selected_project").html());
    $("#ticker_activity").html($("#selected_activity").html());

    if (currentRecording > -1 && updateRecording) {

        $('#ajax_wait').show();
        $.post("../extensions/ki_timesheet/processor.php", {
                axAction: "edit_running",
                id: currentRecording,
                project: selected_project,
                activity: selected_activity
            },
            function (data) {
                $('#ajax_wait').hide();
                ts_ext_reload();
            }
        );
    }
}

// ----------------------------------------------------------------------------------------
// runs the stopwatch
// modified version by x-tin
// I would have added more credits - but you didn't leave any personal information on the forum ...
// ... so just THX! ;)
function ticktac() {
    startsecoffset = startsec ? startsec : offset;
    sek = Math.floor((new Date()).getTime() / 1000) - startsecoffset;
    hour = Math.floor(sek / 3600);
    min = Math.floor((sek - (hour * 3600)) / 60);
    sec = Math.floor(sek - (hour * 3600) - (min * 60));
    var htmp, mtmp, stmp, titleclock,
        ss = $("#s"),
        mm = $("#m"),
        hh = $("#h");

    if (sec == 60) {
        sec = 0;
        min++;
    }
    if (min > 59) {
        min = 0;
        hour++;
    }
    if (sec == 0) ss.html("00");
    else {
        ss.html(((sec < 10) ? "0" : "") + sec);
    }
    if (min == 0) mm.html("00");
    else {
        mm.html(((min < 10) ? "0" : "") + min);
    }
    if (hour == 0) hh.html("00");
    else {
        hh.html(((hour < 10) ? "0" : "") + hour);
    }

    htmp = hh.html();
    mtmp = mm.html();
    stmp = ss.html();
    titleclock = htmp + ":" + mtmp + ":" + stmp;
    document.title = titleclock;
    timeoutTicktack = setTimeout("ticktac()", 1000);
}

function ticktack_off() {
    if (timeoutTicktack) {
        clearTimeout(timeoutTicktack);
        timeoutTicktack = 0;
        $("#h").html("00");
        $("#m").html("00");
        $("#s").html("00");
    }
}

// ----------------------------------------------------------------------------------------
// shows dialogue for editing an item in either customer, project or activity list
function editSubject(subject, id) {
    var width = 450;

    if (subject == 'project') {
        width = 450;
    }
    floaterShow('floaters.php', 'add_edit_' + subject, 0, id, width);

    return false;
}


// ----------------------------------------------------------------------------------------
// filters project and activity fields in add/edit record dialog
function filter_selects(id, needle) {
    var selectedValue,
        idF = $('#' + id);

    // cache initialisieren
    if (typeof window['__cacheselect_' + id] == "undefined") {
        window['__cacheselect_' + id] = [];
        $('#' + id + ' option ').each(function (index) {
            window['__cacheselect_' + id].push({
                'value': $(this).val()
                , 'text': $(this).text()
            })
        })
    }

    selectedValue = idF.val();
    idF.removeOption(/./);

    var i, cs = window['__cacheselect_' + id];
    for (i = 0; i < cs.length; ++i) {
        if (cs[i].text.toLowerCase().indexOf(needle.toLowerCase()) !== -1) $('#' + id).addOption(cs[i].value, cs[i].text);
    }
    idF.val(selectedValue);
}

function lists_extensionShrinkShow() {
    $('#extensionShrink').css("background-color", "red");
}

function lists_extensionShrinkHide() {
    $('#extensionShrink').css("background-color", "transparent");
}

function lists_customerShrinkShow() {
    $('#customersShrink').css("background-color", "red");
}

function lists_customerShrinkHide() {
    $('#customersShrink').css("background-color", "transparent");
}

function lists_userShrinkShow() {
    $('#usersShrink').css("background-color", "red");
}

function lists_userShrinkHide() {
    $('#usersShrink').css("background-color", "transparent");
}

function lists_shrinkExtToggle() {
    (extensionShrinkMode) ? extensionShrinkMode = 0 : extensionShrinkMode = 1;
    if (extensionShrinkMode) {
        $('#extensionShrink').css("background-image", "url('../grfx/timeSheetShrink_down.png')");
    } else {
        $('#extensionShrink').css("background-image", "url('../grfx/timeSheetShrink_up.png')");
    }
    lists_set_heightTop();
    hook_resize();
}

function lists_shrinkCustomerToggle() {
    (customerShrinkMode) ? customerShrinkMode = 0 : customerShrinkMode = 1;
    if (customerShrinkMode) {
        $('#customers, #customers_head, #customers_foot').fadeOut(fading_enabled ? 100 : 0, lists_set_tableWrapperWidths);
        $('#customersShrink').css("background-image", "url('../grfx/customerShrink_right.png')");
        if (!userShrinkMode)
            $('#usersShrink').hide();
    } else {
        lists_set_tableWrapperWidths();
        $('#customers, #customers_head, #customers_foot').fadeIn(fading_enabled ? 100 : 0);
        $('#customersShrink').css("background-image", "url('../grfx/customerShrink_left.png')");
        lists_resize();
        if (!userShrinkMode)
            $('#usersShrink').show();
    }
}

function lists_shrinkUserToggle() {
    (userShrinkMode) ? userShrinkMode = 0 : userShrinkMode = 1;
    if (userShrinkMode) {
        $('#users, #users_head, #users_foot').fadeOut(fading_enabled ? 100 : 0, lists_set_tableWrapperWidths);
        $('#usersShrink').css("background-image", "url('../grfx/customerShrink_right.png')");
    } else {
        $('#users, #users_head, #users_foot').fadeIn(fading_enabled ? 100 : 0);
        lists_set_tableWrapperWidths();
        $('#usersShrink').css("background-image", "url('../grfx/customerShrink_left.png')");
    }
}

function lists_get_dimensions() {

    subtableCount = 4;
    if (customerShrinkMode) {
        subtableCount--;
    }
    if (userShrinkMode) {
        subtableCount--;
    }
    subtableWidth = (pageWidth() - 10) / subtableCount - 7;

    userColumnWidth = subtableWidth - 5;
    customerColumnWidth = subtableWidth - 5; // subtract the space between the panels
    projectColumnWidth = subtableWidth - 6;
    activityColumnWidth = subtableWidth - 5;
}

function lists_resize() {
    lists_set_tableWrapperWidths();
    lists_set_heightTop();
}

function lists_set_tableWrapperWidths() {
    lists_get_dimensions();
    $('#extensionShrink').css("width", pageWidth() - 22);
    // set width of faked table heads of subtables -----------------
    $("#users_head, #users_foot").css("width", userColumnWidth - 5);
    $("#customers_head, #customers_foot").css("width", customerColumnWidth - 5); // subtract the left padding inside the header
    $("#projects_head, #projects_foot").css("width", projectColumnWidth - 5); // which is 5px
    $("#activities_head, #activities_foot").css("width", activityColumnWidth - 5);
    $("#users").css("width", userColumnWidth);
    $("#customers").css("width", customerColumnWidth);
    $("#projects").css("width", projectColumnWidth);
    $("#activities").css("width", activityColumnWidth);
    lists_set_left();
    lists_set_TableWidths();
}

function lists_set_left() {
    var leftmargin = 0,
        rightmargin = 0,
        userShrinkPos = 0,
        customerShrinkPos;

    // push project/activity subtables in place LEFT

    if (userShrinkMode == 0) {
        leftmargin += subtableWidth;
        rightmargin += 7;
        userShrinkPos += subtableWidth + 7;
    }

    $("#customers, #customers_head, #customers_foot").css("left", leftmargin + rightmargin + 10);
    $('#usersShrink').css("left", userShrinkPos);

    customerShrinkPos = userShrinkPos;

    if (customerShrinkMode == 0) {
        leftmargin += subtableWidth;
        rightmargin += 7;
        customerShrinkPos += subtableWidth + 7;
    }

    $("#projects, #projects_head, #projects_foot").css("left", leftmargin + rightmargin + 10);

    $("#activities, #activities_head, #activities_foot").css("left", subtableWidth + leftmargin + rightmargin + 15); //22
    $('#customersShrink').css("left", customerShrinkPos);

}

function pageHeight() {
    var r1 = document.body != null ? document.body.clientHeight : null,
        r2 = document.documentElement && document.documentElement.clientHeight ? document.documentElement.clientHeight : r1;
    return window.innerHeight != null ? window.innerHeight : r2;
    // same is true for the page bottom margin
    // return $(window).height();
}

// ----------------------------------------------------------------------------------------
// returns the amount of space the Header and the Tabbar are currently taking
function headerHeight() {
    var header = 90,
        tabbar = 25;
    /* always plus 10 pixels of horizontal padding */
    return header + tabbar + 10;
}

function lists_set_heightTop() {
//CN...started to clean it...and stopped
    var subs,
        top,
        gui,
        filter,
        pageH = pageHeight(),
        headH = headerHeight(),
        header = 90,
        f_head = 25,
        f_body = 160,
        f_foot = 25;

    lists_get_dimensions();
    if (!extensionShrinkMode) {

        $('#gui>div').css("height", pageH - headH - 150 - 40);


        $("#users,#customers,#projects,#activities").css("height", "160px");
        $("#users_foot, #customers_foot, #projects_foot, #activities_foot").css("top", pageH - 30);


        $('#usersShrink').css("height", "211px");
        $('#customersShrink').css("height", "211px");

        // push customer/project/activity subtables in place TOP
        subs = pageH - headH - header + f_head;
        $("#users,#customers,#projects,#activities").css("top", subs);

        // push faked table heads of subtables in place
        subs = pageH - headH - header;
        $("#users_head,#customers_head,#projects_head,#activities_head").css("top", subs);

        $('#extensionShrink').css("top", subs - 10);
        $('#usersShrink').css("top", subs);
        $('#customersShrink').css("top", subs);
    }

    else {
        $("#gui>div").css("height", "105px");
        $("#users_head,#customers_head,#projects_head,#activities_head").css("top", headH + 107);
        $("#users,#customers,#projects,#activities").css("top", headH + 135);
        $("#users,#customers,#projects,#activities").css("height", pageH - headH - 165);
        $('#customersShrink').css("height", pageH - headH - 110);
        $('#usersShrink').css("height", pageH - headH - 110);
        $('#extensionShrink').css("top", headH + 97);
        $('#customersShrink').css("top", headH + 105);
        $('#usersShrink').css("top", headH + 105);
    }

    lists_set_TableWidths();
}

function lists_set_TableWidths() {
    var user_tbl = $("#users > table"),
        cust_tbl = $("#customers > table"),
        proj_tbl = $("#projects > table"),
        act_tbl = $("#activities > table"),
        scr;

    lists_get_dimensions();
    // set table widths   
    ($("#users").innerHeight() - user_tbl.outerHeight() > 0) ? scr = 0 : scr = scroller_width; // same goes for subtables ....
    user_tbl.css("width", userColumnWidth - scr);

    ($("#customers").innerHeight() - cust_tbl.outerHeight() > 0) ? scr = 0 : scr = scroller_width; // same goes for subtables ....
    cust_tbl.css("width", customerColumnWidth - scr);

    ($("#projects").innerHeight() - proj_tbl.outerHeight() > 0) ? scr = 0 : scr = scroller_width;
    proj_tbl.css("width", projectColumnWidth - scr);

    ($("#activities").innerHeight() - act_tbl.outerHeight() > 0) ? scr = 0 : scr = scroller_width;
    act_tbl.css("width", activityColumnWidth - scr);
}

// ----------------------------------------------------------------------------------------
// reloads timesheet, customer, project and activity tables
function lists_reload(subject, callback) {
    var scr;

    switch (subject) {

    case "user":
        $('#ajax_wait').show();
        $.post("processor.php", {
                axAction: "reload_users",
                axValue: 0,
                id: 0},

            function (data) {
                $('#ajax_wait').hide();
                $("#users").html(data);
                ($("#users").innerHeight() - $("#users table").outerHeight() > 0) ? scr = 0 : scr = scroller_width;
                $("#users table").css("width", customerColumnWidth - scr);
                lists_live_filter('user', $('#filt_user').val());
                lists_write_annotations('user');
                if (typeof(callback) != "undefined")
                    callback();
            }
        );
        break;

    case "customer":
        $('#ajax_wait').show();
        $.post("processor.php", {
                axAction: "reload_customers",
                axValue: 0,
                id: 0},

            function (data) {
                $('#ajax_wait').hide();
                $("#customers").html(data);
                ($("#customers").innerHeight() - $("#customers table").outerHeight() > 0) ? scr = 0 : scr = scroller_width;
                $("#customers table").css("width", customerColumnWidth - scr);
                lists_live_filter('customer', $('#filter_customer').val());
                lists_write_annotations('customer');
                if (typeof(callback) != "undefined")
                    callback();
            }
        );
        break;

    case "project":
        $('#ajax_wait').show();
        $.post("processor.php", {
                axAction: "reload_projects",
                axValue: 0,
                id: 0},
            function (data) {
                $('#ajax_wait').hide();
                $("#projects").html(data);
                ($("#projects").innerHeight() - $("#projects table").outerHeight() > 0) ? scr = 0 : scr = scroller_width;
                $("#projects table").css("width", projectColumnWidth - scr);
                $('#projects>table>tbody>tr>td>a.preselect#ps' + selected_project + '>img').attr('src', '../grfx/preselect_on.png');
                lists_live_filter('project', $('#filter_project').val());
                lists_write_annotations('project');
                if (typeof(callback) != "undefined")
                    callback();
            }
        );
        break;

    case "activity":
        $('#ajax_wait').show();
        $.post("processor.php", {
                axAction: "reload_activities",
                axValue: 0,
                id: 0,
                project: selected_project},

            function (data) {
                $('#ajax_wait').hide();
                $("#activities").html(data);
                ($("#activities").innerHeight() - $("#activities table").outerHeight() > 0) ? scr = 0 : scr = scroller_width;
                $("#activities table").css("width", activityColumnWidth - scr);
                $('#activities>table>tbody>tr>td>a.preselect#ps' + selected_activity + '>img').attr('src', '../grfx/preselect_on.png');
                lists_live_filter('activity', $('#filter_activity').val());
                lists_write_annotations('activity');
                if ($('#row_activity[data-id="' + selected_activity + '"]').length == 0) {
                    buzzer.addClass('disabled');
                }
                else {
                    buzzer.removeClass('disabled');
                }
                if (typeof(callback) != "undefined")
                    callback();
            }
        );
        break;
    }
}

// ----------------------------------------------------------------------------------------
//  Live Filter by The One And Only T.C. (TOAOTC) - THX - WOW! ;)
function lists_live_filter(div_list, needle) {
    $('#' + div_list + ' tr ').filter(function (index) {
        return ($(this).children('td:nth-child(2)').text().toLowerCase().indexOf(needle.toLowerCase()) === -1);
    }).css('display', 'none');
    $('#' + div_list + ' tr ').filter(function (index) {
        return ($(this).children('td:nth-child(2)').text().toLowerCase().indexOf(needle.toLowerCase()) !== -1);
    }).css('display', '');
}

function lists_customer_highlight(customer) {
    $(".customer").removeClass("filterProjectForPreselection");
    $(".project").removeClass("filterProjectForPreselection");
    $("#projects .customer" + customer).addClass("filterProjectForPreselection");
    $("#projects .project").removeClass("TableRowInvisible");
}

function lists_customer_prefilter(customer, filter, singleFilter) {
    if (singleFilter && filter)
        $("#projects .project").addClass("TableRowInvisible");

    if (filter)
        $("#projects .customer" + customer).removeClass("TableRowInvisible");
    else
        $("#projects .customer" + customer).addClass("TableRowInvisible");

    if (singleFilter && !filter)
        $("#projects .project").removeClass("TableRowInvisible");
}

// ----------------------------------------------------------------------------------------
//  table row changes color on rollover - preselection link on whole row
function lists_change_color(tableRow, highLight) {
    if (highLight) {
        $(tableRow).parents("tr").addClass("highlightProjectForPreselection");
    } else {
        $(tableRow).parents("tr").removeClass("highlightProjectForPreselection");
    }
}

function lists_update_annotations(id, user, customer, project, activity) {
    lists_user_annotations[id] = user;
    lists_customer_annotations[id] = customer;
    lists_project_annotations[id] = project;
    lists_activity_annotations[id] = activity;

    if ($('.menu li#exttab_' + id).hasClass('act'))
        lists_write_annotations();
}

function lists_write_annotations(part) {
    var id = parseInt($('#fliptabs li.act').attr('id').substring(7)),
        i;

    if (!part || part == 'user') {
        $('#users>table>tbody td.annotation').html("");
        if (lists_user_annotations[id] != null)
            for (i in lists_user_annotations[id])
                $('#row_user[data-id="' + i + '"]>td.annotation').html(lists_user_annotations[id][i]);
    }
    if (!part || part == 'customer') {
        $('#customers>table>tbody td.annotation').html("");
        if (lists_customer_annotations[id] != null)
            for (i in lists_customer_annotations[id])
                $('#row_customer[data-id="' + i + '"]>td.annotation').html(lists_customer_annotations[id][i]);
    }
    if (!part || part == 'project') {
        $('#projects>table>tbody td.annotation').html("");
        if (lists_project_annotations[id] != null)
            for (i in lists_project_annotations[id])
                $('#row_project[data-id="' + i + '"]>td.annotation').html(lists_project_annotations[id][i]);
    }
    if (!part || part == 'activity') {
        $('#activities>table>tbody td.annotation').html("");
        if (lists_activity_annotations[id] != null)
            for (i in lists_activity_annotations[id])
                $('#row_activity[data-id="' + i + '"]>td.annotation').html(lists_activity_annotations[id][i]);
    }
}

function lists_filter_select_all(subjectPlural) {
    var subjects = $('#' + subjectPlural + ' tr');

    if (subjects.size() > 0) {
        subjects.each(function (index) {
            if ($(this).hasClass('fhighlighted')) return;

            var subjectSingular = $(this).attr('id').substring(4);
            lists_toggle_filter(subjectSingular, parseInt($(this).attr('data-id')));
        });
        hook_filter();
    }
}

function lists_filter_deselect_all(subjectPlural) {
    var subjects = $('#' + subjectPlural + ' tr');

    if (subjects.size() > 0) {
        subjects.each(function (index) {
            if (!$(this).hasClass('fhighlighted')) return;

            var subjectSingular = $(this).attr('id').substring(4);
            lists_toggle_filter(subjectSingular, parseInt($(this).attr('data-id')));
        });

        //CN..always have minimum of current user as filtered. This is what goes on in the
        // server. This allows the user to see the reality of the filter (what the listed
        // timesheet reflects)
        if (subjectPlural === 'users') {
            lists_toggle_filter('user',user_id);
        }

        hook_filter();
    }
}

function lists_filter_select_invert(subjectPlural) {
    var subjects = $('#' + subjectPlural + ' tr');

    if (subjects.size() > 0) {
        subjects.each(function (index) {
            var subjectSingular = $(this).attr('id').substring(4);
            lists_toggle_filter(subjectSingular, parseInt($(this).attr('data-id')));
        });
        hook_filter();
    }
}

function lists_toggle_filter(subject, id) {

    var rowElement = $('#row_' + subject + '[data-id="' + id + '"]'),
        singleFilter;

    if (rowElement.hasClass('fhighlighted')) {
        rowElement.removeClass('fhighlighted');

        switch (subject) {
        case 'user':
            filterUsers.splice(filterUsers.indexOf(id), 1);
            break;
        case 'customer':
            filterCustomers.splice(filterCustomers.indexOf(id), 1);
            singleFilter = $('.fhighlighted', rowElement.parent()).length == 0;
            lists_customer_prefilter(id, false, singleFilter);
            break;
        case 'project':
            filterProjects.splice(filterProjects.indexOf(id), 1);
            break;
        case 'activity':
            filterActivities.splice(filterActivities.indexOf(id), 1);
            break;
        }
    }
    else {
        rowElement.addClass('fhighlighted');
        switch (subject) {
        case 'user':
            filterUsers.push(id);
            break;
        case 'customer':
            filterCustomers.push(id);
            singleFilter = $('.fhighlighted', rowElement.parent()).length == 1;
            lists_customer_prefilter(id, true, singleFilter);
            break;
        case 'project':
            filterProjects.push(id);
            break;
        case 'activity':
            filterActivities.push(id);
            break;
        }
    }
}

function lists_update_filter(subject, id) {
    lists_toggle_filter(subject, id);
    // let tab update its data
    hook_filter();
    // finally update timetable
}

function menu_resize() {
    return;
    $('#menu').css('width',
        $('#display').position()['left']
        - $('#menu').position()['left']
        - 20
        + parseInt($('#display').css('margin-left')));
}

function validatePassword(password, retypePassword) {
    if (password.length == 0 || retypePassword.length == 0) {
        password = '';
        retypePassword = '';
        return true;
    }

    if (password != retypePassword) {
        alert(lang_passwordsDontMatch);
        return false;
    }
    else if (password.length < 5) {
        alert(lang_passwordTooShort);
        return false;
    }
    else
        return true;
}

function setFloaterErrorMessage(fieldName, message) {
    if (fieldName == '')
        fieldName = "floater_tabs";

    var li = $("#floater_innerwrap #" + fieldName).closest('li');
    if (li.length == 0) {
        li = $("#floater_innerwrap [name='" + fieldName + "']").closest('li');
    }
    if (li.length == 0) {
        li = $("#floater_innerwrap form");
    }
    li.prepend('<div class="errorMessage">' + message + '</div>');
    li.addClass('errorField');

    // indicate in tab header
    var id = li.closest('fieldset').attr('id');
    $("#floater_innerwrap .menu a[href='#" + id + "']").addClass("tabError");
}

function clearFloaterErrorMessages() {
    $("#floater_innerwrap .errorMessage").remove();
    $("#floater_tabs li").removeClass("errorField");
    $("#floater_innerwrap .menu a").removeClass("tabError");
}
