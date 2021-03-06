/*! ki_debug */
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

function deb_ext_onload() {
    var that = this;

    set_lists_visibility(false, $('#gui').find('div.ext.ki_debug').attr('id'));

    // thx to joern zaefferer! ;) - www.bassistance.de -
    $("#deb_ext_shoutbox_field").focus(function () {
        if (that.value == that.defaultValue) {
            that.value = "";
        }
    }).blur(function () {
        if (!that.value.length) {
            that.value = that.defaultValue;
        }
    });

    $('#deb_ext_shoutbox').ajaxForm(function () {
        $('#deb_ext_shoutbox_field').val('');
    });

    deb_ext_reloadLogfileLoop();

    deb_ext_resize();
    $("#loader").hide();

}

function deb_ext_onblur() {
    deb_ext_clearTimeout();
}

function deb_ext_resize() {

    var pagew = pageWidth(),
        halb = (pagew - 10) / 2 - 10;

    $("#deb_ext_kga_header").css({
        "width": halb - 27,
        "top": headerHeight(),
        "left": 10
    });

    $("#deb_ext_kga_wrap").css({
        "top": headerHeight() + 23,
        "left": 10,
        "width": halb - 7
    });

    $("#deb_ext_kga").css("height", pageHeight() - headerHeight() - 57);

    $("#deb_ext_logfile_header").css({
        "width": halb - 17,
        "top": headerHeight(),
        "left": halb + 15
    });

    $("#deb_ext_logfile_wrap").css({
        "top": headerHeight() + 13,
        "left": halb + 5,
        "width": halb + 5
    });
    $("#deb_ext_logfile").css("height", pageHeight() - headerHeight() - 57);

}

function deb_ext_tab_changed() {

    deb_ext_reloadLogfileLoop();
}

function deb_ext_reloadLogfileLoop() {

    deb_ext_refreshTimer = setTimeout(deb_ext_reloadLogfileLoop, 2000);
    deb_ext_reloadLogfileOnce();
}

function deb_ext_reloadLogfileOnce() {

    $('a').blur();
    $('#ajax_wait').show();
    $.post(deb_ext_path + "processor.php", {
            axAction: "reloadLogfile",
            axValue: 0,
            id: 0},

        function (data) {
            $('#ajax_wait').hide();
            $("#deb_ext_logfile").html(data);
        });
}

function deb_ext_reloadKGA() {

    $('a').blur();
    $('#ajax_wait').show();
    $.post(deb_ext_path + "processor.php", {
            axAction: "reloadKGA",
            axValue: 0,
            id: 0},

        function (data) {
            $('#ajax_wait').hide();
            $("#deb_ext_kga").html(data);
        });
}

function deb_ext_clearTimeout() {

    clearTimeout(deb_ext_refreshTimer);
}

function deb_ext_clearLogfile() {

    $('a').blur();
    $('#ajax_wait').show();
    $.post(deb_ext_path + "processor.php", {
            axAction: "clearLogfile",
            axValue: 0,
            id: 0},

        function (data) {
            $('#ajax_wait').hide();
            $("#deb_ext_logfile").html(data);
        });
}

