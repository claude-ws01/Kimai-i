/*! ki_budget */
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

// =====================
// budget extension init
// =====================

// set path of extension
var bud_ext_path = "../extensions/ki_budget/";

var bud_width;
var bud_height;

var chartColors;

$(document).ready(function(){

    var budget_resizeTimer = null;
    $(window).bind('resize', function() {
       if (budget_resizeTimer) clearTimeout(budget_resizeTimer);
       budget_resizeTimer = setTimeout(bud_ext_resize, 500);
    });

    $.jqplot.config.enablePlugins = true;

    
});
