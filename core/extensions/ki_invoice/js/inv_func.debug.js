/*! ki_invoice */
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

function inv_ext_onload() {

    set_lists_visibility(false, $('#gui').find('div.ext.ki_invoice').attr('id'));

    inv_ext_resize();
    $("#loader").hide();
}

function inv_ext_resize() {

    var pagew = pageWidth() - 24;

    $("#inv_head,#inv_main").css("width", pagew);
    $("#invoice_extension").css("height", pageHeight() - headerHeight() - 64);
}

function inv_ext_tab_changed() {
    inv_ext_resize();
}

function inv_ext_timeframe_changed() {

    $('#ajax_wait').show();
    $.post(inv_ext_path + "processor.php", {
            axAction: "reload_timespan"
        },

        function (data) {
            $('#ajax_wait').hide();
            $("#invoice_timespan").html(data);
        }
    );
}
