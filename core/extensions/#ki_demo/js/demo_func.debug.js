/*! demo */
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

function demo_ext_onload() {

    set_lists_visibility(false, $('#gui').find('div.ext.ki_demo').attr('id'));

    demo_ext_resize();

    $("#loader").hide();
}


function demo_ext_resize() {
    var pagew = pageWidth() - 22;

    $("#demo_head,#demo_main").css("width", pagew);
    $("#demo_main").css("height", pageHeight() - headerHeight() - 64);
}


function demo_ext_tab_changed() {

    var background = ["#000000", "#FFFF00", "#76EE00", "#CD3333", "#B23AEE", "#CDBE70"];
    var text = ["#FFFFFF", "#000000", "#000000", "#FFFFFF", "#FFFFFF", "#000000"];

    var test = $("#testdiv"),
        array_target = Math.round(Math.random() * background.length);



    test.css("color", text[array_target]);
    test.css("background-color", background[array_target]);
}

function demo_ext_timeframe_changed() {

    $('#ajax_wait').show();
    $.post(demo_ext_path + "processor.php", {
            axAction: "test",
            axValue: 0,
            id: 0
        },

        function (data) {
            $('#ajax_wait').hide();
            $('#demo_timeframe').find('span.timeframe_target').html(data);
        });
}

function demo_ext_triggerREC() {

    logfile("trigger REC demo_ext");
}

function demo_ext_triggerSTP() {

    logfile("trigger STP demo_ext");
}
