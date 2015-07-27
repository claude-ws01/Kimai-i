/*! ki_admin */
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


// set path of extension
var adm_ext_path = "../extensions/ki_admin/";

var adm_ext_customers_changed_hook_flag = 0;
var adm_ext_projects_changed_hook_flag = 0;
var adm_ext_activities_changed_hook_flag = 0;
var adm_ext_users_changed_hook_flag = 0;

$(document).ready(function () {

    var adm_ext_resizeTimer = null;

    $(window).bind('resize', function () {
        if (adm_ext_resizeTimer) clearTimeout(adm_ext_resizeTimer);
        adm_ext_resizeTimer = setTimeout(adm_ext_resize, 500);
    });

});
