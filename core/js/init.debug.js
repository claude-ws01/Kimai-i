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

// =====================================================================
// = Runs when the DOM of the Kimai GUI is loaded => MAIN init script! =
// =====================================================================

var scroller_width = 17;

var userColumnWidth;
var customerColumnWidth;
var projectColumnWidth;
var activityColumnWidth;

var currentDay = (new Date()).getDate();

var fading_enabled = true;

var extensionShrinkMode = 0; // 0 = show, 1 = hide
var customerShrinkMode = 0;
var userShrinkMode = 0;

var filterUsers = [];
var filterCustomers = [];
var filterProjects = [];
var filterActivities = [];

var lists_visibility = [];

var lists_user_annotations = {};
var lists_customer_annotations = {};
var lists_project_annotations = {};
var lists_activity_annotations = {};
var buzzer;
var cur_date;
var subtableWidth, subtableCount;

$(document).ready(function () {
    var ki_active_tab_target,
        ki_active_tab_path;

    // init
    buzzer = $('#buzzer');


    // scroll bar measurement by
    // http://davidwalsh.name/detect-scrollbar-width
    var scrollDiv = document.createElement("div");
    scrollDiv.className = "scrollbar-measure";
    document.body.appendChild(scrollDiv);
    // Get the scrollbar width
    scroller_width = scrollDiv.offsetWidth - scrollDiv.clientWidth;
    // Delete the DIV
    document.body.removeChild(scrollDiv);
    //\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\


    if (user_id) {
        // automatic tab-change on reload
        ki_active_tab_target = $.cookie('ki_active_tab_target_' + user_id);
        ki_active_tab_path = $.cookie('ki_active_tab_path_' + user_id);
    }
    else {
        ki_active_tab_target = null;
        ki_active_tab_path = null;
    }

    if (ki_active_tab_target && ki_active_tab_path) {
        changeTab(ki_active_tab_target, ki_active_tab_path);
    }
    else {
        //find what's the first tab

        changeTab(0, 'ki_timesheet/init.php');
    }

    $('#main_about_btn').click(function () {
        this.blur();
        floaterShow("floaters.php", "credits", 0, 0, 650);
    });

    $('#main_prefs_btn').click(function () {
        this.blur();
        floaterShow("floaters.php", "prefs", 0, 0, 450);
    });


    buzzer.click(function () {
        buzzer_onclick();
    });

    if ($('#exttab_0').length > 0
        && (currentRecording > -1
            || (selected_customer && selected_project && selected_activity)
        )) {
        buzzer.removeClass('disabled');
    }

    n_hour();
    if (demoMode === 1) {
        demoCountDown();
    }

    if (currentRecording > -1) {
        show_stopwatch();
    } else {
        show_selectors();
    }

    var lists_resizeTimer = null;
    $(window).bind('resize', function () {

        // menu_resize();
        floater_resize();

        if (lists_resizeTimer) clearTimeout(lists_resizeTimer);
        lists_resizeTimer = setTimeout(lists_resize, 500);
    });

    // Implement missing method for browsers like IE.
    // thanks to http://stellapower.net/content/javascript-support-and-arrayindexof-ie
    if (!Array.indexOf) {
        Array.prototype.indexOf = function (obj, start) {
            for (var i = (start || 0); i < this.length; i++) {
                if (this[i] == obj) {
                    return i;
                }
            }
            return -1;
        }
    }

});


