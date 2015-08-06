/*! ki_invoice */
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

// ======================
// Invoice Extension init
// ======================

// set path of extension
var inv_ext_path = "../extensions/ki_invoice/";

$(document).ready(function(){

    var invoice_resizeTimer = null;
    $(window).bind('resize', function() {
       if (invoice_resizeTimer) clearTimeout(invoice_resizeTimer);
        invoice_resizeTimer = setTimeout(inv_ext_resize, 500);
    });
});
