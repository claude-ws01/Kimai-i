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

// ===========
// ADMIN PANEL
// ===========

function adm_ext_onload() {

    set_lists_visibility(false, $('#gui').find('div.ext.ki_admin').attr('id'));

    adm_ext_resize();
    subactivelink = "#adm_ext_sub1";
    $("#loader").hide();
}

function adm_ext_resize() {

    var subtab = $(".adm_ext_subtab"),
        panel = $("#adm_ext_panel"),
        pagew, panel_w, panel_h;

    pagew = pageWidth();
    drittel = (pagew - 10) / 3 - 7;

    panel_w = pagew - 24;
    panel_h = pageHeight() - 10 - headerHeight();

    subtab.css("display", "none");

    panel.css("width", panel_w);
    $(".adm_ext_panel_header").css("width", panel_w);
    panel.css("height", panel_h);

    subtab.css("height", panel_h - (10 * 21) - 20 - 1);

    adm_ext_subtab_autoexpand();
}

/**
 * Show one of the subtabs. All others are collapsed, so only their header
 * is visible.
 */
function adm_ext_subtab_expand(id) {

    var sub_id, subtab;

    $("#adm_ext_sub1").removeClass("active");
    $("#adm_ext_sub2").removeClass("active");
    $("#adm_ext_sub3").removeClass("active");
    $("#adm_ext_sub4").removeClass("active");
    $("#adm_ext_sub5").removeClass("active");
    $("#adm_ext_sub6").removeClass("active");
    $("#adm_ext_sub7").removeClass("active");
    $("#adm_ext_sub8").removeClass("active");
    $("#adm_ext_sub9").removeClass("active");
    $("#adm_ext_sub10").removeClass("active");
    $(".adm_ext_subtab").css("display", "none");

    sub_id = "#adm_ext_sub" + id;
    $(sub_id).addClass("active");

    subtab = "#adm_ext_s" + id;
    $(subtab).css("display", "block");

    $.cookie('adm_ext_activePanel_' + user_id, id);
}

/**
 * Show the last subtab, the user has seen. This information is stored in a
 * cookie. If we're unable to read it show the first subtab.
 */
function adm_ext_subtab_autoexpand() {

    var activ_panel = $.cookie('adm_ext_activePanel_' + user_id);

    if (activ_panel) {
        adm_ext_subtab_expand(activ_panel);
    } else {
        adm_ext_subtab_expand(1);
    }
}

// ------------------------------------------------------
function adm_ext_tab_changed() {

    if ($('.ki_admin').css('display') == "block") {
        adm_ext_refreshSubtab('customers');
        adm_ext_refreshSubtab('projects');
        adm_ext_refreshSubtab('activities');
    } else {
        tss_hook_flag++;
    }
    if (adm_ext_customers_changed_hook_flag) {
        adm_ext_customers_changed();
    }
    if (adm_ext_projects_changed_hook_flag) {
        adm_ext_projects_changed();
    }
    if (adm_ext_activities_changed_hook_flag) {
        adm_ext_activities_changed();
    }
    if (adm_ext_users_changed_hook_flag) {
        adm_ext_users_changed();
    }

    adm_ext_customers_changed_hook_flag = 0;
    adm_ext_projects_changed_hook_flag = 0;
    adm_ext_activities_changed_hook_flag = 0;
    adm_ext_users_changed_hook_flag = 0;
}

function adm_ext_customers_changed() {

    if ($('.ki_admin').css('display') == "block") {
        adm_ext_refreshSubtab('customers');
        adm_ext_refreshSubtab('projects');
    } else {
        adm_ext_customers_changed_hook_flag++;
    }
}

function adm_ext_projects_changed() {

    if ($('.ki_admin').css('display') == "block") {
        adm_ext_refreshSubtab('projects');
    } else {
        adm_ext_projects_changed_hook_flag++;
    }
}

function adm_ext_activities_changed() {

    if ($('.ki_admin').css('display') == "block") {
        adm_ext_refreshSubtab('activities');
    } else {
        adm_ext_activities_changed_hook_flag++;
    }
}

function adm_ext_users_changed() {

    if ($('.ki_admin').css('display') == "block") {
        adm_ext_refreshSubtab('users');
    } else {
        adm_ext_users_changed_hook_flag++;
    }
}

// ------------------------------------------------------
// ----------------------------------------------------------------------------------------
// graps the value of the newUser input field 
// and ajaxes it to the createUser function of the processor
//
function adm_ext_newUser() {

    var newuser = $("#newuser").val();

    if (newuser == "") {
        alert(lang_checkUsername);
        return false;
    }
    $.post(adm_ext_path + "processor.php", {axAction: "createUser", axValue: newuser, id: 0},
        function (data) {
            if (data.user_id === false) {
                alert(data.errors.join("\n"));
                return;
            }

            adm_ext_refreshSubtab('users');
            adm_ext_editUser(data.user_id);
        });
}

function adm_ext_showDeletedUsers() {

    $.post(adm_ext_path + "processor.php", {axAction: "toggleDeletedUsers", axValue: 1, id: 0},
        function (data) {
            adm_ext_refreshSubtab('users');
        });
}

function adm_ext_hideDeletedUsers() {

    $.post(adm_ext_path + "processor.php", {axAction: "toggleDeletedUsers", axValue: 0, id: 0},
        function (data) {
            adm_ext_refreshSubtab('users');
        });
}

// ----------------------------------------------------------------------------------------
// graps the value of the newGroup input field 
// and ajaxes it to the createGroup function of the processor
//
function adm_ext_newGroup() {

    var newgroup = $("#newgroup").val();

    if (newgroup == "") {
        alert(lang_checkGroupname);
        return false;
    }
    $.post(adm_ext_path + "processor.php", {axAction: "createGroup", axValue: newgroup, id: 0},
        function (data) {
            adm_ext_refreshSubtab('groups');
        });
}

//----------------------------------------------------------------------------------------
//graps the value of the newGroup input field 
//and ajaxes it to the createGroup function of the processor
//
function adm_ext_newStatus() {

    var newstatus = $("#newstatus").val();

    if (newstatus == "") {
        alert(lang_checkStatusname);
        return false;
    }
    $.post(adm_ext_path + "processor.php", {axAction: "createStatus", axValue: newstatus, id: 0},
        function (data) {
            adm_ext_refreshSubtab('status');
        });
}

//----------------------------------------------------------------------------------------
//graps the value of the newGlobalRole input field 
//and ajaxes it to the createGlobalRole function of the processor
//
function adm_ext_newGlobalRole() {

    var newGlobalRole = $("#newGlobalRole").val();

    if (newGlobalRole == "") {
        alert(lang_checkGlobalRoleName);
        return false;
    }
    $.post(adm_ext_path + "processor.php", {axAction: "createGlobalRole", axValue: newGlobalRole, id: 0},
        function (data) {
            if (data.errors.length > 0)
                alert(data.errors.join("\n"));
            else
                adm_ext_refreshSubtab('globalRoles');
        });
}

//----------------------------------------------------------------------------------------
//graps the value of the newMembershipRole input field 
//and ajaxes it to the createMembershipRole function of the processor
//
function adm_ext_newMembershipRole() {

    var newMembershipRole = $("#newMembershipRole").val();

    if (newMembershipRole == "") {
        alert(lang_checkMembershipRoleName);
        return false;
    }
    $.post(adm_ext_path + "processor.php", {
            axAction: "createMembershipRole",
            axValue: newMembershipRole,
            id: 0
        },
        function (data) {
            if (data.errors.length > 0)
                alert(data.errors.join("\n"));
            else
                adm_ext_refreshSubtab('membershipRoles');
        });
}

// ----------------------------------------------------------------------------------------
// by clicking on the edit button of a user the edit dialogue pops up
//
function adm_ext_editUser(id) {

    floaterShow(adm_ext_path + "floaters.php", "editUser", 0, id, 400);
}

// ----------------------------------------------------------------------------------------
// by clicking on the edit button of a group the edit dialogue pops up
//
function adm_ext_editGroup(id) {

    floaterShow(adm_ext_path + "floaters.php", "editGroup", 0, id, 450);
}

//----------------------------------------------------------------------------------------
//by clicking on the edit button of a status the edit dialogue pops up
//
function adm_ext_editStatus(id) {

    floaterShow(adm_ext_path + "floaters.php", "editStatus", 0, id, 450);
}

//----------------------------------------------------------------------------------------
//by clicking on the edit button of a global role the edit dialogue pops up
//
function adm_ext_editGlobalRole(id) {

    floaterShow(adm_ext_path + "floaters.php", "editGlobalRole", 0, id, 550, function () {
        $('.floatingTabLayout').each(doFloatingTabLayout);
    });
}

//----------------------------------------------------------------------------------------
//by clicking on the edit button of a membership role the edit dialogue pops up
//
function adm_ext_editMembershipRole(id) {

    floaterShow(adm_ext_path + "floaters.php", "editMembershipRole", 0, id, 550, function () {
        $('.floatingTabLayout').each(doFloatingTabLayout);
    });
}

// ----------------------------------------------------------------------------------------
// refreshes either user/group/advanced/DB subtab
//
function adm_ext_refreshSubtab(tab) {

    var options, target;

    options = {axAction: "refreshSubtab", axValue: tab, id: 0};
    if (tab == 'activities') {
        options.activity_filter = $('#activity_project_filter').val();
    }
    $.post(adm_ext_path + "processor.php", options,
        function (data) {
            switch (tab) {
            case "users":
                target = "#adm_ext_s1";
                break;
            case "groups":
                target = "#adm_ext_s2";
                break;
            case "status":
                target = "#adm_ext_s3";
                break;
            case "advanced":
                target = "#adm_ext_s4";
                break;
            case "database":
                target = "#adm_ext_s5";
                break;
            case "customers":
                target = "#adm_ext_s6";
                break;
            case "projects":
                target = "#adm_ext_s7";
                break;
            case "activities":
                target = "#adm_ext_s8";
                break;
            case "globalRoles":
                target = "#adm_ext_s9";
                break;
            case "membershipRoles":
                target = "#adm_ext_s10";
                break;
            }
            $(target).html(data);
        });
}

// ----------------------------------------------------------------------------------------
// delete user
//
function adm_ext_deleteUser(id, trash) {

    if (!confirm(lang_sure)) return;

    var axData = (trash ? 1 : 2);
    $.post(adm_ext_path + "processor.php", {axAction: "deleteUser", axValue: axData, id: id},
        function () {
            adm_ext_refreshSubtab('users');
            adm_ext_refreshSubtab('groups');
            hook_users_changed();
        }
    );
}

// ----------------------------------------------------------------------------------------
// delete group
//
function adm_ext_deleteGroup(id) {

    if (!confirm(lang_sure)) return;

    $.post(adm_ext_path + "processor.php", {axAction: "deleteGroup", id: id},
        function (result) {
            var error = result['errors'][""];

            if (error)
                alert(error);
            else
                adm_ext_refreshSubtab('groups');
        }
    );

}

//----------------------------------------------------------------------------------------
//delete status
//
function adm_ext_deleteStatus(id) {

    if (!confirm(lang_sure)) return;

    $.post(adm_ext_path + "processor.php", {axAction: "deleteStatus", id: id},
        function () {
            adm_ext_refreshSubtab('status');
        }
    );
}

// ----------------------------------------------------------------------------------------
// delete project
//
function adm_ext_deleteProject(id) {

    if (!confirm(lang_sure)) return;

    if (currentRecording == -1 && selected_project == id) {
        buzzer.addClass('disabled');
        selected_project = false;
        $("#sel_project").html('');
    }

    $.post(adm_ext_path + "processor.php", {axAction: "deleteProject", id: id},
        function () {
            adm_ext_refreshSubtab('projects');
            hook_projects_changed();
        }
    );
}

// ----------------------------------------------------------------------------------------
// delete customer
//
function adm_ext_deleteCustomer(id) {

    if (!confirm(lang_sure)) return;

    if (currentRecording == -1 && selected_customer == id) {
        buzzer.addClass('disabled');
        selected_customer = false;
        $("#sel_customer").html('');
    }

    $.post(adm_ext_path + "processor.php", {axAction: "deleteCustomer", id: id},
        function () {
            adm_ext_refreshSubtab('customers');
            hook_customers_changed();
        }
    );
}

// ----------------------------------------------------------------------------------------
// delete activity
//
function adm_ext_deleteActivity(id) {

    if (!confirm(lang_sure)) return;

    if (currentRecording == -1 && selected_activity == id) {
        buzzer.addClass('disabled');
        selected_activity = false;
        $("#selected_activity").html('');
    }

    $.post(adm_ext_path + "processor.php", {axAction: "deleteActivity", id: id},
        function () {
            adm_ext_refreshSubtab('activities');
            hook_activities_changed();
        }
    );
}

//----------------------------------------------------------------------------------------
//delete global role
//
function adm_ext_deleteGlobalRole(id) {

    if (!confirm(lang_sure)) return;

    $.post(adm_ext_path + "processor.php", {axAction: "deleteGlobalRole", id: id},
        function () {
            adm_ext_refreshSubtab('globalRoles');
        }
    );
}

//----------------------------------------------------------------------------------------
//delete membership role
//
function adm_ext_deleteMembershipRole(id) {

    if (!confirm(lang_sure)) return;

    $.post(adm_ext_path + "processor.php", {axAction: "deleteMembershipRole", id: id},
        function () {
            adm_ext_refreshSubtab('membershipRoles');
        }
    );
}

// ----------------------------------------------------------------------------------------
// activates user for login
//
function adm_ext_unbanUser(id) {

    var ban = $("#ban" + id);

    ban.blur();
    ban.html("<img border='0' width='16' height='16' src='../skins/" + skin + "/grfx/loading13.gif'/>");
    $.post(adm_ext_path + "processor.php", {axAction: "unbanUser", axValue: 0, id: id},
        function (data) {
            ban.html(data);
            ban.attr({"ONCLICK": "adm_ext_banUser('" + id + "'); return false;"});
        }
    );
}

// ----------------------------------------------------------------------------------------
// toggle ban and unban of users in admin panel
//
function adm_ext_banUser(id) {

    var ban = $("#ban" + id);

    ban.blur();
    ban.html("<img border='0' width='16' height='16' src='../skins/" + skin + "/grfx/loading13.gif'/>");
    $.post(adm_ext_path + "processor.php", {axAction: "banUser", axValue: 0, id: id},
        function (data) {
            ban.html(data);
            ban.attr({"ONCLICK": "adm_ext_unbanUser('" + id + "'); return false;"});
        }
    );
}

function adm_ext_checkupdate() {

    $.post("checkupdate.php",
        function (data) {
            $('#adm_ext_checkupdate').html(data);
        }
    );

}
