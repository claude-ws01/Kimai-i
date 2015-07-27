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

function inv_ext_onload() {

    set_lists_visibility(false, $('#gui').find('div.ext.ki_invoice').attr('id'));

    inv_ext_resize();
    $("#loader").hide();
}

function inv_ext_resize() {

    var pagew = pageWidth() - 15;

    $("#inv_ext_header").css({
        "width": pagew - 27,
        "top": headerHeight(),
        "left": 10
    });

    $("#inv_ext_wrap").css({
        "top": headerHeight() + 30,
        "left": 10,
        "width": pagew - 7
    });

    $("#invoice_extension").css("height", pageHeight() - headerHeight() - 64);
}

function inv_ext_tab_changed() {
    inv_ext_resize();
}

function inv_ext_timeframe_changed() {

    $.post(inv_ext_path + "processor.php", {axAction: "reload_timespan"},
        function (data) {
            $("#invoice_timespan").html(data);
        }
    );
}
