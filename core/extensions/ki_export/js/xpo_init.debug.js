/*! ki_export */
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

// ===========
// XP EXT init
// ===========

// set path of extension
var xpo_ext_path = "../extensions/ki_export/";

var drittel;
var xpo_width;
var xpo_height;

var xpo_timeframe_changed_hook_flag = 0;
var xpo_customers_changed_hook_flag = 0;
var xpo_projects_changed_hook_flag = 0;
var xpo_activities_changed_hook_flag = 0;

$(document).ready(function () {
    var export_resizeTimer = null;

    $(window).bind('resize', function () {
        if (export_resizeTimer) clearTimeout(export_resizeTimer);
        export_resizeTimer = setTimeout(xpo_ext_resize, 500);
    });
});
