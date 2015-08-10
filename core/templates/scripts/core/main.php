<?php

global $kga;

function select_css_file($file)
{
    global $kga;

    $css = $kga['pref']['skin'] . '/' . $file . DEBUG_JS . '.css';

    if (file_exists(WEBROOT . '/skins/' . $css)) {
        return '<link rel="stylesheet" href="../skins/' . $css .
        '"type="text/css" media="screen" title="no title" charset="utf-8"/>';
    }
    else {
        $css = $file . '.css';

        return '<link rel="stylesheet" href="../skins/' . $kga['pref']['skin'] . '/' . $css .
        '" type="text/css" media="screen" title="no title" charset="utf-8"/>';
    }
}

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex,nofollow"/>

    <title><?php echo $this->escape($kga['who']['name']) ?> - Kimai-i</title>
    <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico">

    <!-- Default Stylesheets -->
    <?php
    echo select_css_file('styles');
    echo select_css_file('jquery.jqplot');
    ?>
    <!-- /Default Stylesheets -->

    <!-- Extension Stylesheets -->
    <?php
    foreach ($this->css_extension_files as $object) {
        echo '<link rel="stylesheet" href="' . $this->escape($object) .
            '" type="text/css" media="screen" title="no title" charset="utf-8"/>';
    } ?>
    <!-- /Extension Stylesheets -->

    <!-- Libs -->
    <script src="../libraries/jQuery/jquery-1.9.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="../libraries/jQuery/jquery.hoverIntent.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="../libraries/jQuery/jquery.form<?php echo DEBUG_JS ?>.js"
            type="text/javascript"
            charset="utf-8"></script>
    <script src="../libraries/jQuery/jquery.newsticker.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="../libraries/jQuery/jquery.cookie.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="../libraries/jQuery/jquery-ui-1.10.2.min.js" type="text/javascript" charset="utf-8"></script>
    <!--[if IE]>
    <script src="../libraries/jQuery/excanvas.min.js" type="text/javascript"></script>
    <![endif]-->
    <script src="../libraries/jQuery/jquery.jqplot.min.js" type="text/javascript"></script>
    <script src="../libraries/jQuery/jqplot.pieRenderer.min.js" type="text/javascript"></script>
    <script src="../libraries/jQuery/jquery-ui-timepicker/jquery.ui.timepicker<?php echo DEBUG_JS ?>.js"
            type="text/javascript"></script>
    <script src="../libraries/phpjs/strftime.min.js" type="text/javascript"></script>
    <script src="../libraries/jQuery/jquery.selectboxes.min.js" type="text/javascript" charset="utf-8"></script>
    <!-- /Libs -->

    <!-- Default JavaScripts -->
    <?php echo '<script type="text/javascript">', file_get_contents('../js/init' . DEBUG_JS . '.js'), '</script>'; ?>
    <?php echo '<script type="text/javascript">', file_get_contents('../js/main' . DEBUG_JS . '.js'), '</script>'; ?>
    <!-- Extension JavaScripts -->
    <?php
    if (DEBUG_JS === '.min') {
        foreach ($this->js_extension_files as $object) {
            echo '<script type="text/javascript">', "\n", file_get_contents($object), '</script>', "\n";
        }
    }
    else {
        foreach ($this->js_extension_files as $object) {
            echo '<script src="' . $this->escape($object) . '" type="text/javascript" charset="utf-8"></script>';
        }
    } ?>
    <!-- /Extension JavaScripts -->

    <script type="text/javascript">
        var skin = "<?php echo $this->escape($kga['pref']['skin']); ?>";
        var lang_checkCustomer = "<?php echo $this->escape($kga['dict']['checkCustomer']); ?>";
        var lang_checkUsername = "<?php echo $this->escape($kga['dict']['checkUsername']); ?>";
        var lang_checkGroupname = "<?php echo $this->escape($kga['dict']['checkGroupname']); ?>";
        var lang_checkStatusname = "<?php echo $this->escape($kga['dict']['checkStatusname']); ?>";
        var lang_checkGlobalRoleName = "<?php echo $this->escape($kga['dict']['checkGlobalRoleName']); ?>";
        var lang_checkMembershipRoleName = "<?php echo $this->escape($kga['dict']['checkMembershipRoleName']); ?>";
        var lang_passwordsDontMatch = "<?php echo $this->escape($kga['dict']['passwordsDontMatch']); ?>";
        var lang_passwordTooShort = "<?php echo $this->escape($kga['dict']['passwordTooShort']); ?>";
        var lang_sure = "<?php echo $this->escape($kga['dict']['sure']); ?>";

        var currentRecording = <?php echo $this->current_recording?>;

        var open_after_recorded = <?php echo json_encode($this->open_after_recorded) ?>;

        <?php if ($kga['pref']['quickdelete'] === '2'): ?>
        var confirmText = "<?php echo $this->escape($kga['dict']['sure']) ?>";
        <?php else: ?>
        var confirmText = undefined;
        <?php endif; ?>

        <?php if (is_user()): ?>
        var user_id = <?php echo $kga['who']['id']; ?>;
        <?php else: ?>
        var user_id = null;
        <?php endif; ?>


        <?php if ($kga['pref']['no_fading']): ?>
        fading_enabled = false;
        <?php endif; ?>

        var timeoutTicktack = 0;

        // use to recover running timer's time.
        var hour = <?php echo $this->current_timer_hour ?>;
        var min = <?php echo $this->current_timer_min ?>;
        var sec = <?php echo $this->current_timer_sec ?>;
        var startsec = <?php echo $this->current_timer_start ?>;
        var now = <?php echo $this->current_time ?>;

        // offset is not relevant. It just messes up the client's display.
        // offset = Math.floor(((new Date()).getTime()) / 1000) - now;
        var offset = 0;

        var default_title = "<?php echo $this->escape($kga['who']['name'])?> - Kimai";
        var revision = <?php echo $kga['core.revision'] ?>;
        var timeframeDateFormat = "<?php echo $this->escape($kga['conf']['date_format_2']) ?>";

        var selected_customer = '<?php echo $this->customerData['customer_id']?>';
        var selected_project = '<?php echo $this->projectData['project_id']?>';
        var selected_activity = '<?php echo $this->activityData['activity_id']?>';

        var pickerClicked = '';

        var weekdayNames = <?php echo $this->weekdays_short_array?>;
        var demoMode = <?php echo (DEMO_MODE ? 1 : 0); ?>;
        <?php
            $demo_next_reset = 0;
            $demo_reset_time = 24 * 3600;
            if (DEMO_MODE && defined('DEMO_RESET_TIME')) {
                $demo_reset_time = DEMO_RESET_TIME * 3600;
                $hour = (int)date('H');
                $next_reset_hour = ((int)($hour / DEMO_RESET_TIME) * DEMO_RESET_TIME) + DEMO_RESET_TIME;
                if ($next_reset_hour > 23) {
                    $next_reset_hour = 0;
                    $next_time = mktime($next_reset_hour, 0, 0, date('m'), date('j')+1);
                    $demo_next_reset = $next_time - time();
                }
                else {
                    $next_time = mktime($next_reset_hour, 0, 0);
                    $demo_next_reset = $next_time - time();
                }
            }
            ?>
        var demoNextReset = <?php echo $demo_next_reset ?>;
        var demoResetTime = <?php echo $demo_reset_time ?>;

        var pwdMinLength = <?php echo $kga['pwdMinLength'] ?>;

        $.datepicker.setDefaults({
            showOtherMonths: true,
            selectOtherMonths: true,
            nextText: '',
            prevText: '',
            <?php if ($kga['pref']['no_fading']): ?>
            showAnim: '',
            <?php endif; ?>
            dateFormat: 'dd.mm.yy', // TODO use correct format depending on admin panel setting
            dayNames: <?php echo $this->weekdays_array ?>,
            dayNamesMin: <?php echo $this->weekdays_short_array ?>,
            dayNamesShort: <?php echo $this->weekdays_short_array ?>,
            monthNames: <?php echo $this->months_array ?>,
            monthNamesShort: <?php echo $this->months_short_array ?>,
            firstDay: 1 //TODO should also be depending on user setting
        });


        // HOOKS
        function hook_timeframe_changed() { <?php echo $this->hook_timeframe_changed?>
        }

        function hook_buzzer_record() { <?php echo $this->hook_buzzer_record?>
        }

        function hook_buzzer_stopped() { <?php echo $this->hook_buzzer_stopped?>
        }

        function hook_users_changed() {
            lists_reload("user");
            <?php echo $this->hook_users_changed?>
        }

        function hook_customers_changed() {
            lists_reload("customer");
            lists_reload("project");
            <?php echo $this->hook_customers_changed?>
        }

        function hook_projects_changed() {
            lists_reload("project");
            <?php echo $this->hook_projects_changed?>
        }

        function hook_activities_changed() {
            lists_reload("activity");
            <?php echo $this->hook_activities_changed?>
        }

        function hook_filter() {<?php echo $this->hook_filter?>
            //CN..call example://  function hook_filter() {bud_ext_reload();xpe_ext_reload();xpo_ext_reload();ts_ext_reload();        }
        }

        function hook_resize() {<?php echo $this->hook_resize?>
        }

        function kill_reg_timeouts() {<?php echo $this->timeoutlist?>
        }

        function kimai_onload() {

            var ext_shrink = $('#extensionShrink'),
                cust_shrink = $('#customersShrink'),
                user_shrink = $('#usersShrink');

            ext_shrink.hover(lists_extensionShrinkShow, lists_extensionShrinkHide);
            ext_shrink.click(lists_shrinkExtToggle);
            cust_shrink.hover(lists_customerShrinkShow, lists_customerShrinkHide);
            cust_shrink.click(lists_shrinkCustomerToggle);
            <?php if (count($this->users) > 0): ?>
            user_shrink.hover(lists_userShrinkShow, lists_userShrinkHide);
            user_shrink.click(lists_shrinkUserToggle);
            <?php else: ?>
            user_shrink.hide();
            <?php endif; ?>

            <?php if ($kga['pref']['user_list_hidden'] or count($this->users) <= 1): ?>
            lists_shrinkUserToggle();
            <?php endif; ?>
            $('#projects>table>tbody>tr>td>a.preselect#ps' + selected_project + '>img').attr('src', '../grfx/preselect_on.png');
            $('#activities>table>tbody>tr>td>a.preselect#ps' + selected_activity + '>img').attr('src', '../grfx/preselect_on.png');

            $('#floater').draggable({
                zIndex: 20,
                ghosting: false,
                opacity: 0.7,
                cursor: 'move',
                handle: '#floater_handle'
            });

            $('#n_date').html(weekdayNames[cur_date.getDay()] + " " + strftime(timeframeDateFormat, new Date()));

            // give browser time to render page. afterwards make sure lists are resized correctly
            setTimeout(lists_resize, 500);
            clearTimeout(lists_resize);


            if ($('#row_activity[data-id="' + selected_activity + '"]').length == 0) {
                buzzer.addClass('disabled');
            }

            menu_resize();

            // add user filter, to show that user IS filtered on page loading
            lists_update_filter('user', <?php echo $kga['who']['id'] ?>);

            <?php if ($this->showInstallWarning): ?>
            floaterShow("floaters.php", "securityWarning", "installer", 0, 450);
            <?php endif; ?>
        }

    </script>

    <link href="../favicon.ico" rel="shortcut icon"/>

</head>

<body onload="kimai_onload();">

<div id="top">
    <table id="t_table">
        <tr>
            <td id="tt_logo">
                <div id="logo">
                    <img src="../grfx/kii_logo.png"
                         width="122" height="52" alt="Logo"/>
                </div>
            </td>
            <td id="tt_menu">
                <div id="menu">
                    <table id="menu_btns">
                        <tr>
                            <td id="first">
                                <a id="main_about_btn" href="#"><img
                                        src="/grfx/cu_about.png"
                                        width="31" height="27" alt="<?php echo $kga['dict']['btn_about'] ?>"
                                        title="<?php echo $kga['dict']['btn_about'] ?>"/>
                                </a>
                            </td>
                            <td>
                                <a id="main_prefs_btn" href="#"><img
                                        src="/grfx/cu_prefs.png"
                                        width="31" height="27" alt="<?php echo $kga['dict']['btn_preferences'] ?>"
                                        title="<?php echo $kga['dict']['btn_preferences'] ?>"/>
                                </a>
                            </td>
                            <td id="last">
                                <a id="main_logout_btn" href="../index.php?a=logout"><img
                                        src="/grfx/cu_logout.png"
                                        width="32" height="27" alt="<?php echo $kga['dict']['btn_logout'] ?>"
                                        title="<?php echo $kga['dict']['btn_logout'] ?>"/>
                                </a>
                            </td>
                        </tr>
                    </table>

                    <div id="logged_in_name"><?php echo $this->escape($kga['who']['name']) ?></div>
                </div>
            </td>
            <td id="tt_display">
                <div id="display" title="<?php echo $kga['dict']['tip']['g_date_area'] ?>">
                    <script type="text/javascript" charset="utf-8">
                        $(function () {
                            $('.date-pick').datepicker(
                                {
                                    dateFormat: 'mm/dd/yy',
                                    onSelect: function (dateText, instance) {
                                        if (this == $('#pick_in')[0]) {
                                            setTimeframe(new Date(dateText), undefined);
                                        }
                                        if (this == $('#pick_out')[0]) {
                                            setTimeframe(undefined, new Date(dateText));
                                        }
                                    }
                                });

                            setTimeframeStart(new Date(<?php echo $this->timeframe_in*1000?>));
                            setTimeframeEnd(new Date(<?php echo $this->timeframe_out*1000?>));
                            updateTimeframeWarning();

                        });
                    </script>

                    <div id="dates">
                        <input type="hidden" id="pick_in" class="date-pick"/>
                        <a href="#" id="ts_in" onclick="$('#pick_in').datepicker('show');return false"></a> -
                        <input type="hidden" id="pick_out" class="date-pick"/>
                        <a href="#" id="ts_out" onclick="$('#pick_out').datepicker('show');return false"></a>
                    </div>

                    <div id="infos">
                        <span id="n_date"></span> &nbsp;
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/g3_display_smallclock.png"
                             width="13" height="13" alt="Display Smallclock"/>
                        <span id="n_hour">00:00</span> &nbsp;
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/g3_display_eye.png"
                             width="15" height="12" alt="Display Eye"/>
                        <strong id="display_total"><?php echo $this->total ?></strong>
                    </div>
                </div>
            </td>
            <?php if (is_user()) { ?>

                <td id="tt_selector">
                    <div id="preselector">
                        <span>
                            <strong><?php echo $kga['dict']['selectedForRecording'] ?></strong><br/>

                            <strong class="short"><?php echo $kga['dict']['selectedCustomerLabel'] ?></strong><span
                                class="selection"
                                id="selected_customer"><?php echo $this->escape($this->customerData['name']) ?></span><br/>
                            <strong class="short"><?php echo $kga['dict']['selectedProjectLabel'] ?></strong><span
                                class="selection"
                                id="selected_project"><?php echo $this->escape($this->projectData['name']) ?></span><br/>
                            <strong class="short"><?php echo $kga['dict']['selectedActivityLabel'] ?></strong><span
                                class="selection"
                                id="selected_activity"><?php echo $this->escape($this->activityData['name']) ?></span><br/>
                        </span>
                    </div>
                    <div id="stopwatch">
                        <span id="sw_counter"><span id="h">00</span>:<span id="m">00</span>:<span id="s">00</span></span>
                        <span id="sw_ticker">
                           <ul id="ticker">
                               <li id="ticker_customer">&nbsp;</li>
                               <li id="ticker_project">&nbsp;</li>
                               <li id="ticker_activity">&nbsp;</li>
                           </ul>
                       </span>
                    </div>
                </td>
                <td id="tt_buzzer">
                    <div id="buzzer" class="disabled">
                        <div>&nbsp;</div>
                    </div>
                </td>
            <?php }
            else { ?>
                <td id="tt_selector"></td>
                <td id="tt_buzzer"></td>
            <?php } ?>
        </tr>
    </table>
</div>

<div id="fliptabs" class="menuBackground <?php
if (defined('DEMO_MODE') && DEMO_MODE) {
    echo 'demo_mode';
} ?>">
    <?php if (defined('DEMO_MODE') && DEMO_MODE) { ?>
        <span class="demo"><?php echo $kga['dict']['demo_mode']; ?></span>
        <span class="demo"><?php echo $kga['dict']['demo_reset_in']; ?><span id="demo_countdown">00:00:00</span></span>

    <?php } ?>
    <ul class="menu">
        <?php
        $gui = array();
        $i   = 0; // make timesheet the 1st tab //
        if (isset($this->extensions['ki_timesheet'])) { ?>
            <li class="tab act" id="exttab_0"
                data-path="<?php echo $this->extensions['ki_timesheet']['initFile'] ?>">
                <a href="javascript:void(0);"
                   onclick="changeTab(<?php
                   echo $i ?>, '<?php
                   echo $this->extensions['ki_timesheet']['initFile'] ?>');
                   <?php echo $this->extensions['ki_timesheet']['tabChangeTrigger'] ?>">
                    <span class="aa">&nbsp;</span>
           	        <span class="bb">
                    <?php if (isset($kga['dict']['extensions']['ki_timesheet'])) {
                        echo $kga['dict']['extensions']['ki_timesheet'];
                    }
                    else {
                        echo 'Timesheet';
                    }
                    ?></span>
                    <span class="cc">&nbsp;</span>
                </a>
            </li>
            <?php
            $gui[] = $this->extensions['ki_timesheet']['key'];
            $i++;
        }
        foreach ($this->extensions as $extension) {
            if (!$extension['name'] OR $extension['key'] === 'ki_timesheet') {
                continue;
            } ?>
            <li class="tab norm" id="exttab_<?php echo $i; ?>"
                data-path="<?php echo $extension['initFile'] ?>">
                <a href="javascript:void(0);"
                   onclick="changeTab(<?php echo $i; ?>, '<?php echo $extension['initFile'] ?>'); <?php echo $extension['tabChangeTrigger'] ?>">
                    <span class="aa">&nbsp;</span>
                <span class="bb">
                <?php if (isset($kga['dict']['extensions'][$extension['key']])) {
                    echo $kga['dict']['extensions'][$extension['key']];
                }
                else {
                    echo $this->escape($extension['name']);
                }
                ?></span>
                    <span class="cc">&nbsp;</span>
                </a>
            </li>
            <?php
            $gui[] = $extension['key'];
            $i++;
        } ?>
    </ul>
</div>

<div id="gui">
    <?php foreach ($gui as $key => $ext_key) { ?>
        <div id="extdiv_<?php echo $key; ?>" class="ext <?php echo $ext_key ?>"
             style="display:none;"></div>
    <?php } ?>
</div>
<!-- filter section start -->
<div class="lists" style="display:none;">
    <div id="users_head">
        <input class="livefilterfield" onkeyup="lists_live_filter('users', this.value);" type="text" id="filt_user"
               name="filt_user"
               placeholder="<?php echo $kga['dict']['searchFilter'] ?>"><?php echo $kga['dict']['users'] ?>
    </div>

    <div id="customers_head">
        <input class="livefilterfield" onkeyup="lists_live_filter('customers', this.value);" type="text"
               id="filter_customer" name="filter_customer"
               placeholder="<?php echo $kga['dict']['searchFilter'] ?>"><?php echo $kga['dict']['customers'] ?>
    </div>

    <div id="projects_head">
        <input class="livefilterfield" onkeyup="lists_live_filter('projects', this.value);" type="text"
               id="filter_project" name="filter_project"
               placeholder="<?php echo $kga['dict']['searchFilter'] ?>"><?php echo $kga['dict']['projects'] ?>
    </div>

    <div id="activities_head">
        <input class="livefilterfield" onkeyup="lists_live_filter('activities', this.value);" type="text"
               id="filter_activity" name="filter_activity"
               placeholder="<?php echo $kga['dict']['searchFilter'] ?>"><?php echo $kga['dict']['activities'] ?>
    </div>

    <div id="users"><?php echo $this->user_display ?></div>
    <div id="customers"><?php echo $this->customer_display ?></div>
    <div id="projects"><?php echo $this->project_display ?></div>
    <div id="activities"><?php echo $this->activity_display ?></div>

    <div id="users_foot">
        <a href="#" class="selectAllLink" title="<?php echo $kga['dict']['tip']['f_select_all'] ?>"
           onclick="lists_filter_select_all('users'); $(this).blur(); return false;"></a>
        <a href="#" class="deselectAllLink" title="<?php echo $kga['dict']['tip']['f_select_none'] ?>"
           onclick="lists_filter_deselect_all('users'); $(this).blur(); return false;"></a>
        <a href="#" class="selectInvertLink" title="<?php echo $kga['dict']['tip']['f_select_invert'] ?>"
           onclick="lists_filter_select_invert('users'); $(this).blur(); return false;"></a>

        <div style="clear:both;"></div>
    </div>

    <div id="customers_foot">
        <?php if ($this->show_customer_add_button): ?>
            <a href="#" class="addLink" title="<?php echo $kga['dict']['tip']['f_add_customer'] ?>"
               onclick="floaterShow('floaters.php','add_edit_customer',0,0,450); $(this).blur(); return false;"></a>
        <?php endif; ?>
        <a href="#" class="selectAllLink" title="<?php echo $kga['dict']['tip']['f_select_all'] ?>"
           onclick="lists_filter_select_all('customers'); $(this).blur(); return false;"></a>
        <a href="#" class="deselectAllLink" title="<?php echo $kga['dict']['tip']['f_select_none'] ?>"
           onclick="lists_filter_deselect_all('customers'); $(this).blur(); return false;"></a>
        <a href="#" class="selectInvertLink" title="<?php echo $kga['dict']['tip']['f_select_invert'] ?>"
           onclick="lists_filter_select_invert('customers'); $(this).blur(); return false;"></a>

        <div style="clear:both;"></div>
    </div>

    <div id="projects_foot">
        <?php if ($this->show_project_add_button): ?>
        <a href="#" class="addLink" title="<?php echo $kga['dict']['tip']['f_add_project'] ?>"
           onclick="floaterShow('floaters.php','add_edit_project',0,0,450); $(this).blur(); return false;"></a><?php endif; ?>
        <a href="#" class="selectAllLink" title="<?php echo $kga['dict']['tip']['f_select_all'] ?>"
           onclick="lists_filter_select_all('projects'); $(this).blur(); return false;"></a>
        <a href="#" class="deselectAllLink" title="<?php echo $kga['dict']['tip']['f_select_none'] ?>"
           onclick="lists_filter_deselect_all('projects'); $(this).blur(); return false;"></a>
        <a href="#" class="selectInvertLink" title="<?php echo $kga['dict']['tip']['f_select_invert'] ?>"
           onclick="lists_filter_select_invert('projects'); $(this).blur(); return false;"></a>

        <div style="clear:both;"></div>
    </div>

    <div id="activities_foot">
        <?php if ($this->show_activity_add_button): ?>
        <a href="#" class="addLink" title="<?php echo $kga['dict']['tip']['f_add_activity'] ?>"
           onclick="floaterShow('floaters.php','add_edit_activity',0,0,450); $(this).blur(); return false;"></a><?php endif; ?>
        <a href="#" class="selectAllLink" title="<?php echo $kga['dict']['tip']['f_select_all'] ?>"
           onclick="lists_filter_select_all('activities'); $(this).blur(); return false;"></a>
        <a href="#" class="deselectAllLink" title="<?php echo $kga['dict']['tip']['f_select_none'] ?>"
           onclick="lists_filter_deselect_all('activities'); $(this).blur(); return false;"></a>
        <a href="#" class="selectInvertLink" title="<?php echo $kga['dict']['tip']['f_select_invert'] ?>"
           onclick="lists_filter_select_invert('activities'); $(this).blur(); return false;"></a>

        <div style="clear:both;"></div>
    </div>

    <div id="extensionShrink">&nbsp;</div>
    <div id="usersShrink">&nbsp;</div>
    <div id="customersShrink">&nbsp;</div>
</div>
<!-- filter section end -->
<div id="loader">&nbsp;</div>
<div id="ajax_wait" style="display:none;">&nbsp;</div>
<div id="floater_overlay" style="width:100%;height:100%;top:0;left:0;z-index:400;position:absolute;display:none;"></div>
<div id="floater">floater</div>

</body>
</html>
