/*! ki_export */
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

/**
 * Javascript functions used in the export extension.
 */

/**
 * The extension was loaded, do some setup stuff.
 */

function xpo_ext_onload() {

    set_lists_visibility(true, $('#gui').find('div.ext.ki_export').attr('id'));

    xpo_ext_resize();
    $("#loader").hide();
    lists_visible(true);

    $('#xpo_ext_select_filter').click(function () {
        xpo_ext_select_filter();
    });

    $('#xpo_ext_select_location').click(function () {
        xpo_ext_select_location();
    });

    $('#xpo_ext_select_timeformat').click(function () {
        xpo_ext_select_timeformat();
    });

    $('#xpo_ext_export_pdf').click(function () {
        this.blur();
        floaterShow('../extensions/ki_export/floaters.php', 'PDF', 0, 0, 600);
    });

    $('#xpo_ext_export_xls').click(function () {
        this.blur();
        floaterShow('../extensions/ki_export/floaters.php', 'XLS', 0, 0, 600);
    });
    $('#xpo_ext_export_csv').click(function () {
        this.blur();
        floaterShow('../extensions/ki_export/floaters.php', 'CSV', 0, 0, 600);
    });

    $('#xpo_ext_print').click(function () {
        this.blur();
        floaterShow('../extensions/ki_export/floaters.php', 'print', 0, 0, 600);
    });

    $('.helpfloater').click(function () {
        this.blur();
        floaterShow('../extensions/ki_export/floaters.php', 'help_timeformat', 0, 0, 600);
    });

    xpo_ext_select_filter();
    xpo_ext_reload();
}

/**
 * Show the tab which allows filtering.
 */
function xpo_ext_select_filter() {

    $('#xpo_ext_select_filter').addClass("pressed");
    $('#xpo_ext_tab_filter').css("display", "block");

    $('#xpo_ext_select_location').removeClass("pressed");
    $('#xpo_ext_tab_location').css("display", "none");
    $('#xpo_ext_select_timeformat').removeClass("pressed");
    $('#xpo_ext_tab_timeformat').css("display", "none");

}

/**
 * Show the tab via which the default location can be set.
 */
function xpo_ext_select_location() {

    $('#xpo_ext_select_location').addClass("pressed");
    $('#xpo_ext_tab_location').css("display", "block");

    $('#xpo_ext_select_filter').removeClass("pressed");
    $('#xpo_ext_tab_filter').css("display", "none");
    $('#xpo_ext_select_timeformat').removeClass("pressed");
    $('#xpo_ext_tab_timeformat').css("display", "none");
}

/**
 * Show the tab which lets the user define the date and time format.
 */
function xpo_ext_select_timeformat() {

    $('#xpo_ext_select_timeformat').addClass("pressed");
    $('#xpo_ext_tab_timeformat').css("display", "block");

    $('#xpo_ext_select_filter').removeClass("pressed");
    $('#xpo_ext_tab_filter').css("display", "none");
    $('#xpo_ext_select_location').removeClass("pressed");
    $('#xpo_ext_tab_location').css("display", "none");

}

/**
 * The window has been resized, we have to adjust to the new space.
 */
function xpo_ext_resize() {

    xpo_ext_set_tableWrapperWidths();
    xpo_ext_set_heightTop();
}

/**
 * Set width of table and faked table head.
 */
function xpo_ext_set_tableWrapperWidths() {

    var xpo_head = $("#xpo_head"),
        xpo_main = $("#xpo_main");

    xpo_ext_get_dimensions();

    xpo_head.css("width", xpo_width);
    xpo_main.css("width", xpo_width);

    xpo_ext_set_TableWidths();
}

/**
 * If the extension is being shrinked so the sublists are shown larger
 * adjust to that.
 */
function xpo_ext_set_heightTop() {

    var xpo_main = $("#xpo_main");

    xpo_ext_get_dimensions();
    if (!extensionShrinkMode) {
        xpo_main.css("height", xpo_height);
    } else {
        xpo_main.css("height", "20px");
    }

    xpo_ext_set_TableWidths();
}

/**
 * Update the dimension variables to reflect new height and width.
 */
function xpo_ext_get_dimensions() {

    (customerShrinkMode) ? subtableCount = 2 : subtableCount = 3;
    subtableWidth = (pageWidth() - 10) / subtableCount - 7;

    xpo_width = pageWidth() - 24;
    xpo_height = pageHeight() - 274 - headerHeight() - 28;
}

/**
 * Set the width of the table.
 */
function xpo_ext_set_TableWidths() {

    var scr,
        xpo_main = $("#xpo_main"),

        xpo_h_tbl = $("#xpo_h_tbl"),
        xpo_m_tbl = $("#xpo_m_tbl"),

        xpo_h_tr = xpo_h_tbl.find("tbody>tr"),
        xpo_m_tr = xpo_m_tbl.find("tbody>tr");

    xpo_ext_get_dimensions();

    (xpo_main.innerHeight() - xpo_m_tbl.outerHeight() > 0) ? scr = 0 : scr = scroller_width; // width of export table depending on scrollbar or not

    xpo_h_tbl.css("width", xpo_width - scr);
    xpo_m_tbl.css("width", xpo_width - scr);


    // manage width of DATA column

    // *** commented columns are set as fixed width in css ***

    xpo_h_tr.find("td.date").css("width", xpo_m_tr.find("td.date").width());
    xpo_h_tr.find("td.from").css("width", xpo_m_tr.find("td.from").width());
    xpo_h_tr.find("td.to").css("width", xpo_m_tr.find("td.to").width());
    xpo_h_tr.find("td.time").css("width", xpo_m_tr.find("td.time").width());
    xpo_h_tr.find("td.dec_time").css("width", xpo_m_tr.find("td.dec_time").width());
    xpo_h_tr.find("td.rate").css("width", xpo_m_tr.find("td.rate").width());
    xpo_h_tr.find("td.wage").css("width", xpo_m_tr.find("td.wage").width());
    xpo_h_tr.find("td.budget").css("width", xpo_m_tr.find("td.budget").width());
    xpo_h_tr.find("td.approved").css("width", xpo_m_tr.find("td.approved").width());
    xpo_h_tr.find("td.status").css("width", xpo_m_tr.find("td.status").width());
    xpo_h_tr.find("td.billable").css("width", xpo_m_tr.find("td.billable").width());
    xpo_h_tr.find("td.customer").css("width", xpo_m_tr.find("td.customer").width());
    xpo_h_tr.find("td.project").css("width", xpo_m_tr.find("td.project").width());
    xpo_h_tr.find("td.activity").css("width", xpo_m_tr.find("td.activity").width());
    xpo_h_tr.find("td.description").css("width", xpo_m_tr.find("td.description").width());
    xpo_h_tr.find("td.comment").css("width", xpo_m_tr.find("td.comment").width());
    xpo_h_tr.find("td.location").css("width", xpo_m_tr.find("td.location").width());
    xpo_h_tr.find("td.ref_code").css("width", xpo_m_tr.find("td.ref_code").width());
    xpo_h_tr.find("td.user").css("width", xpo_m_tr.find("td.user").width());
}

function xpo_ext_tab_changed() {

    if (xpo_timeframe_changed_hook_flag) {
        xpo_ext_reload();
        xpo_customers_changed_hook_flag = 0;
        xpo_projects_changed_hook_flag = 0;
        xpo_activities_changed_hook_flag = 0;
    }
    if (xpo_customers_changed_hook_flag) {
        xpo_ext_customers_changed();
        xpo_projects_changed_hook_flag = 0;
        xpo_activities_changed_hook_flag = 0;
    }
    if (xpo_projects_changed_hook_flag) {
        xpo_ext_projects_changed();
    }
    if (xpo_activities_changed_hook_flag) {
        xpo_ext_activities_changed();
    }

    xpo_timeframe_changed_hook_flag = 0;
    xpo_customers_changed_hook_flag = 0;
    xpo_projects_changed_hook_flag = 0;
    xpo_activities_changed_hook_flag = 0;

    if ($('.ki_export').html() != '')
        xpo_ext_reload();
}

function xpo_ext_timeframe_changed() {

    if ($('.ki_export').css('display') == "block") {
        xpo_ext_reload();
    } else {
        xpo_timeframe_changed_hook_flag++;
    }
}

function xpo_ext_customers_changed() {

    if ($('.ki_export').css('display') == "block") {
        xpo_ext_reload();
    } else {
        xpo_customers_changed_hook_flag++;
    }
}

function xpo_ext_projects_changed() {

    if ($('.ki_export').css('display') == "block") {
        xpo_ext_reload();
    } else {
        xpo_projects_changed_hook_flag++;
    }
}

function xpo_ext_activities_changed() {

    if ($('.ki_export').css('display') == "block") {
        xpo_ext_reload();
    } else {
        xpo_activities_changed_hook_flag++;
    }
}

// ----------------------------------------------------------------------------------------
// reloads timesheet, customer, project and activity tables
//
function xpo_ext_reload() {

    // don't reload if extension is not loaded
    if ($('.ki_export').html() == '') return;

    $('#ajax_wait').show();
    $.post(xpo_ext_path + "processor.php", {
            axAction: "reload",
            axValue: filterUsers.join(":")
            + '|' + filterCustomers.join(":")
            + '|' + filterProjects.join(":")
            + '|' + filterActivities.join(":"),
            id: 0,
            timeformat: $("#xpo_ext_timeformat").val(),
            dateformat: $("#xpo_ext_dateformat").val(),
            default_location: $("#xpo_ext_default_location").val(),
            filter_cleared: $('#xpo_ext_tab_filter_cleared').val(),
            filter_refundable: $('#xpo_ext_tab_filter_refundable').val(),
            filter_type: $('#xpo_ext_tab_filter_type').val(),
            first_day: new Date($('#pick_in').val()).getTime() / 1000,
            last_day: new Date($('#pick_out').val()).getTime() / 1000
        },

        function (data) {
            $('#ajax_wait').hide();

            var xpo_main = $("#xpo_main"),
                xpo_h_tbl = $("#xpo_h_tbl"),
                xpo_m_tbl = $("#xpo_m_tbl");

            xpo_main.html(data);

            // set export table width
            // width of export table depending on scrollbar or not
            (xpo_main.innerHeight() - xpo_m_tbl.outerHeight() > 0 ) ? scr = 0 : scr = scroller_width;

            xpo_m_tbl.css("width", xpo_width - scr);

            // stretch customer column in faked export table head
            xpo_h_tbl.find("td.customer").css("width", xpo_m_tbl.find("td.customer").width());

            // stretch project column in faked export table head
            xpo_h_tbl.find("td.project").css("width", xpo_m_tbl.find("td.project").width());
            xpo_ext_resize();
        }
    );
}

/**
 * Toggle the enabled state of a column.
 */
function xpo_ext_toggle_column(name) {

    var xpo_h_tbl = $("#xpo_h_tbl"),
        xpo_m_tbl = $("#xpo_m_tbl"),
        returnfunction;

    if (xpo_h_tbl.find("td." + name).hasClass('disabled')) {

        returnfunction = new Function("data", "$('#ajax_wait').hide();\
                    if (data!=1) return;\
                    $('#xpo_h_tbl td." + name + "').removeClass('disabled');\
                    $('#xpo_m_tbl td." + name + "').removeClass('disabled'); ");

        $('#ajax_wait').show();
        $.post(xpo_ext_path + "processor.php", {
                axAction: "toggle_header",
                axValue: name},

            returnfunction
        );

    }
    else {
        returnfunction = new Function("data", "$('#ajax_wait').hide();\
                    if (data!=1) return;\
                    $('#xpo_h_tbl td." + name + "').addClass('disabled'); \
                    $('#xpo_m_tbl td." + name + "').addClass('disabled'); ");

        $('#ajax_wait').show();
        $.post(xpo_ext_path + "processor.php", {
                axAction: "toggle_header",
                axValue: name},

            returnfunction
        );
    }
}

/**
 * Toggle the cleared state of an entry.
 */
function xpo_ext_toggle_cleared(id) {

    var path, returnfunction;

    path = "#xpo_" + id + ">td.cleared>a";
    if ($(path).hasClass("is_cleared")) {

        returnfunction = new Function("data", "$('#ajax_wait').hide();\
                    if (data!=1) return;\
                    $('" + path + "').removeClass('is_cleared');\
                    $('" + path + "').addClass('isnt_cleared');");

        $('#ajax_wait').show();
        $.post(xpo_ext_path + "processor.php", {
                axAction: "set_cleared",
                axValue: 0,
                id: id},

            returnfunction
        );
    }
    else {
        returnfunction = new Function("data", "$('#ajax_wait').hide();\
                    if (data!=1) return;\
                    $('" + path + "').removeClass('isnt_cleared');\
                    $('" + path + "').addClass('is_cleared');");

        $('#ajax_wait').show();
        $.post(xpo_ext_path + "processor.php", {
                axAction: "set_cleared",
                axValue: 1,
                id: id},

            returnfunction
        );
    }
    $(path).blur();
}

/**
 * Create a list of enabled columns.
 */
function xpo_ext_enabled_columns() {

    var columns = ['date', 'from', 'to', 'time', 'dec_time', 'rate', 'wage', 'budget', 'approved', 'status',
            'billable', 'customer', 'project', 'activity', 'description', 'comment', 'location', 'ref_code',
            'user', 'cleared'],
        columnsString = '',
        firstColumn = true,
        td = $('#xpo_h_tbl');

    $(columns).each(function () {
        if (td.find('.' + this).hasClass('disabled')) {
            columnsString += (firstColumn ? '' : '|') + this;
            firstColumn = false;
        }
    });
    return columnsString;
}
