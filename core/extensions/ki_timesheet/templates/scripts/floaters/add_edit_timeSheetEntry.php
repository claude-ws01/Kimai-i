<?php global $kga ?>
<script type="text/javascript">
    var previousBudget = $('#budget').val();
    var previousUsed = 0;
    var previousApproved = 0;
    $(document).ready(function () {
        $('#help').hide();
        var ext_form_ts = $('#ts_ext_form_add_edit_timeSheetEntry');


        // only save the value, the update will happen automatically because we trigger a changed
        // activity on "edit_out_time"
        $('#currentTime, #end_day, #start_day').click(function () {
            saveDuration();
        });

        $("#approved").focus(function () {
            previousApproved = this.value;
        }).change(function () {
            if (isNaN($(this).val()) || $(this).val() == '') {
                $(this).val(0);
            }
            $('#budget_activity_approved').text(parseFloat($('#budget_activity_approved').text()) - previousApproved + parseFloat($(this).val()));
            return false;
        });
        if ($('#round_timesheet_entries').val().length > 0) {
            var step = $('#stepMinutes').val();
            var stepSeconds = $('#stepSeconds').val();
            if (isNaN(stepSeconds) || stepSeconds <= 0) {
                var configuration = {showPeriodLabels: false};
                if (!isNaN(step) && step > 0 && step < 60) {
                    configuration.step = parseInt(step);
                }

                $('#start_time').timepicker(configuration);
                $('#end_time').timepicker(configuration);
            }
        }

        // #rate already has an activity on click, so treat it below
        $("#eduration, #eend_time, #start_time").focus(function () {
            saveDuration();
        }).change(function () {
            updateDuration();
            generateChart();
            return false;
        });

        $('#add_edit_timeSheetEntry_activityID').change(function () {
            $.getJSON("../extensions/ki_timesheet/processor.php", {
                    axAction: "budgets",
                    project_id: $("#add_edit_timeSheetEntry_projectID").val(),
                    activity_id: $("#add_edit_timeSheetEntry_activityID").val(),
                    timeSheetEntryID: $('input[name="id"]').val()
                },
                function (data) {
                    ts_ext_updateBudget(data);
                }
            );
        });

        $("#budget").focus(function () {
            previousBudget = this.value;
        }).change(function () {
            $('#activityBudget').text(parseFloat($('#activityBudget').text()) - previousBudget + $(this).val());
            generateChart();
            return false;
        });

        $('#start_day').datepicker({
            onSelect: function (dateText, instance) {
                $('#end_day').datepicker("option", "minDate", $('#start_day').datepicker("getDate"));
                ts_ext_timeToDuration();
            }
        });

        $('#end_day').datepicker({
            onSelect: function (dateText, instance) {
                $('#start_day').datepicker("option", "maxDate", $('#end_day').datepicker("getDate"));
                ts_ext_timeToDuration();
            }
        });


        ext_form_ts.ajaxForm({
            'beforeSubmit': function () {
                var start_day = $('#start_day'),
                    end_day = $('#end_day'),
                    start_time = $('#start_time'),
                    end_time = $('#end_time'),
                    i, inVal, outVal;

                clearFloaterErrorMessages();
                if (!start_day.val().match(ts_dayFormatExp) ||
                    ( !end_day.val().match(ts_dayFormatExp) && end_day.val() != '') || !start_time.val().match(ts_timeFormatExp) ||
                    ( !end_time.val().match(ts_timeFormatExp) && end_time.val() != '')) {
                    alert("<?php echo $kga['lang']['TimeDateInputError']?>");
                    return false;
                }

                var endTimeSet = end_day.val() != '' || end_time.val() != '';

                if (!endTimeSet)
                    return true; // no need to validate timerange if end time is not set

                // test if start day is before end day
                var inDayMatches = start_day.val().match(ts_dayFormatExp);
                var outDayMatches = end_day.val().match(ts_dayFormatExp);
                for (i = 3; i >= 1; i--) {
                    inVal = inDayMatches[i];
                    outVal = outDayMatches[i];

                    inVal = parseInt(inVal);
                    outVal = parseInt(outVal);

                    if (inVal == undefined)
                        inVal = 0;
                    if (outVal == undefined)
                        outVal = 0;

                    if (inVal > outVal) {
                        alert("<?php $kga['lang']['StartTimeBeforeEndTime']?>");
                        return false;
                    }
                    else if (inVal < outVal)
                        break; // if this part is smaller we don't care for the other parts
                }
                if (inDayMatches[0] == outDayMatches[0]) {
                    // test if start time is before end time if it's the same day
                    var inTimeMatches = start_time.val().match(ts_timeFormatExp);
                    var outTimeMatches = end_time.val().match(ts_timeFormatExp);
                    for ( i = 1; i <= 3; i++) {
                        inVal = inTimeMatches[i];
                        outVal = outTimeMatches[i];

                        if (inVal == undefined)
                            inVal = 0;
                        if (outVal == undefined)
                            outVal = 0;

                        if (inVal[0] == ":")
                            inVal = inVal.substr(1);
                        if (outVal[0] == ":")
                            outVal = outVal.substr(1);

                        inVal = parseInt(inVal);
                        outVal = parseInt(outVal);

                        if (inVal > outVal) {
                            alert("<?php echo $kga['lang']['StartTimeBeforeEndTime']?>");
                            return false;
                        }
                        else if (inVal < outVal)
                            break; // if this part is smaller we don't care for the other parts
                    }
                }

                // unused
                //var edit_in_time = start_day.val() + start_time.val();
                //var edit_out_time = end_day.val() + end_time.val();
                //var deleted = $('#erase').is(':checked');

                if (ext_form_ts.attr('submitting')) {
                    return false;
                }
                else {
                    $("#ajax_wait").show();
                    ext_form_ts.attr('submitting', true);
                    return true;
                }


            },
            'success': function (result) {
                ext_form_ts.removeAttr('submitting');
                $("#ajax_wait").hide();
                for (var fieldName in result.errors)
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);

                if (result.errors.length == 0) {
                    floaterClose();
                    ts_ext_reload();
                }
            },

            'error': function () {
                $("#ajax_wait").hide();
                ext_form_ts.removeAttr('submitting');
            }
        });

        <?php
        if (isset($this->id)) { ?>
        ts_ext_reload_activities(<?php echo $this->project_id?>, true);
        <?php }
        else { ?>
        $("#add_edit_timeSheetEntry_projectID").val(selected_project);
        $("#add_edit_timeSheetEntry_activityID").val(selected_activity);
        ts_ext_reload_activities(selected_project);
        <?php } ?>

        $('#floater_innerwrap').tabs({selected: 0});
        ts_ext_timeToDuration();
        // ts_ext_timeToDuration will set the value of duration. The first time, the value
        // will be set and the duration is added to the budgetUsed eventhough it shouldn't
        // so maually subtract the value again
        var secs, rate;
        var durationArray = $("#duration").val().split(/:|\./);

        if (durationArray.length > 0 && durationArray.length < 4) {
            secs = durationArray[0] * 3600;
            if (durationArray.length > 1)
                secs += (durationArray[1] * 60);
            if (durationArray.length > 2)
                secs += parseInt(durationArray[2]);
            <?php if ($this->showRate): ?>
                rate = $('#rate').val();
            <?php else: ?>
                rate = 0;
            <?php endif; ?>
            var budgetCalculatedTwice = secs / 3600 * rate,
                used = $('#budget_activity_used');
            used.text(Math.round(parseFloat(used.text()) - budgetCalculatedTwice), 2);
        }
        <?php if (isset($this->id)) { ?>
        //TODO: chart will not be generated..WHY??
        //generateChart();
        <?php } ?>
    });
    // document ready

    function saveDuration() {
        var durationArray = $("#duration").val().split(/:|\./);
        var secs = 0;
        if (durationArray.length > 0 && durationArray.length < 4) {
            secs = durationArray[0] * 3600;
            if (durationArray.length > 1)
                secs += (durationArray[1] * 60);
            if (durationArray.length > 2)
                secs += parseInt(durationArray[2]);
        }
        <?php if ($this->showRate): ?>
        var rate = $('#rate').val();
        <?php else: ?>
        var rate = 0;
        <?php endif; ?>
        previousUsed = secs / 3600 * rate;
    }

    function updateDuration() {
        var durationArray = $("#duration").val().split(/:|\./);
        var secs = 0;
        if (durationArray.length > 0 && durationArray.length < 4) {
            secs = durationArray[0] * 3600;
            if (durationArray.length > 1)
                secs += (durationArray[1] * 60);
            if (durationArray.length > 2)
                secs += parseInt(durationArray[2]);
        }
        <?php if ($this->showRate): ?>
        var rate = $('#rate').val();
        <?php else: ?>
        var rate = 0;
        <?php endif; ?>
        var used = secs / 3600 * rate;
        $('#budget_activity_used').text(Math.round(parseFloat($('#budget_activity_used').text()) - previousUsed + used), 2);
    }
    function generateChart() {
        var durationArray = $("#duration").val().split(/:|\./);
        var secs = 0;
        if (durationArray.length > 0 && durationArray.length < 4) {
            secs = durationArray[0] * 3600;
            if (durationArray.length > 1)
                secs += (durationArray[1] * 60);
            if (durationArray.length > 2)
                secs += parseInt(durationArray[2]);
        }
        <?php if ($this->showRate): ?>
        var rate = $('#rate').val();
        <?php else: ?>
        var rate = 0;
        <?php endif; ?>
        var budget = $('#budget_val').val();
        var used = secs / 3600 * rate;
        var usedString = '<?php echo $kga['lang']['used']?>';
        var budgetString = '<?php echo $kga['lang']['budget_available']?>';
        var chartdata = [[usedString, used], [budgetString, budget - used]];

        try {
            $.jqplot('chart', [chartdata], {
                seriesDefaults: {
                    renderer: $.jqplot.PieRenderer,
                    rendererOptions: {
                        showDataLabels: true,
                        //                        // By default, data labels show the percentage of the donut/pie.
                        //                        // You can show the data 'value' or data 'label' instead.
                        dataLabels: 'value'
                    }
                },
                // Show the legend and put it outside the grid, but inside the
                // plot container, shrinking the grid to accomodate the legend.
                // A value of "outside" would not shrink the grid and allow
                // the legend to overflow the container.
                legend: {
                    show: true,
                    placement: 'insideGrid'
                },
                grid: {background: 'white', borderWidth: 0, shadow: false}
            });
        }
        catch (err) {
            // probably no data, so remove the chart
            $('#chart').remove();
        }

    }
</script>

<div id="floater_innerwrap">
    <div id="floater_handle">
        <span id="floater_title">
            <?php if (isset($this->id)) {
                $new_entry = false;
                echo $kga['lang']['edit'],
                ' - ', $this->escape($this->start_day),
                ' - ', $this->escape($this->start_time),
                ' - ', $this->escape($this->customer_name);
            }
            else {
                $new_entry = true;
                echo $kga['lang']['add'];
            } ?>
        </span>
        <div class="right">
            <a href="#" class="close" onClick="floaterClose();"><?php echo $kga['lang']['close'] ?></a>
            <a href="#"
               class="help"
               onClick="$(this).blur(); $('#help').slideToggle();"><?php echo $kga['lang']['help'] ?></a>
        </div>
    </div>

    <div id="help">
        <div class="content">
            <?php echo $kga['lang']['dateAndTimeHelp'] ?>
        </div>
    </div>

    <div class="menuBackground">

        <ul class="menu tabSelection">
            <li class="tab norm"><a href="#general">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['lang']['general'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#extended">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['lang']['advanced'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#budget">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['lang']['budget'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
        </ul>
    </div>

    <form id="ts_ext_form_add_edit_timeSheetEntry" action="../extensions/ki_timesheet/processor.php" method="post">
        <input name="id" type="hidden" value="<?php echo $this->id ?>"/>
        <input name="axAction" type="hidden" value="add_edit_timeSheetEntry"/>
        <input id="stepMinutes" type="hidden" value="<?php echo $kga['conf']['round_minutes'] ?>"/>
        <input id="stepSeconds" type="hidden" value="<?php echo $kga['conf']['round_seconds'] ?>"/>
        <input id="round_timesheet_entries"
               type="hidden"
               value="<?php echo $kga['conf']['round_timesheet_entries'] ?>"/>

        <div id="floater_tabs" class="floater_content">
            <fieldset id="general">
                <ul>
                    <li>
                        <label for="project_id"><?php echo $kga['lang']['project'] ?>:</label>

                        <div class="multiFields">
                            <?php echo $this->formSelect('project_id', $this->project_id, array(
                                'size'     => '5',
                                'id'       => 'add_edit_timeSheetEntry_projectID',
                                'class'    => 'formfield',
                                'style'    => 'width:400px',
                                'tabindex' => '1',
                                'onChange' => "ts_ext_reload_activities($('#add_edit_timeSheetEntry_projectID').val(),undefined,$('#add_edit_timeSheetEntry_activityID').val(), $('input[name=\'id\']').val());",
                            ), $this->projects); ?>
                            <br/>
                            <input type="text"
                                   style="width:380px;margin-top:3px"
                                   tabindex="2"
                                   size="10"
                                   placeholder="<?php echo $kga['lang']['searchFilter']; ?>"
                                   name="filter"
                                   id="filter"
                                   onkeyup="filter_selects('add_edit_timeSheetEntry_projectID', this.value);"/>
                        </div>
                    </li>
                    <li>
                        <label for="activity_id"><?php echo $kga['lang']['activity'] ?>:</label>

                        <div class="multiFields">
                            <?php echo $this->formSelect('activity_id', $this->activity_id, array(
                                'size'     => '5',
                                'id'       => 'add_edit_timeSheetEntry_activityID',
                                'class'    => 'formfield',
                                'style'    => 'width:400px',
                                'tabindex' => '3',
                                'onChange' => 'ts_ext_getBestRates();',
                            ), $this->activities); ?>
                            <br/>
                            <input type="text"
                                   style="width:380px;margin-top:3px"
                                   tabindex="4"
                                   size="10"
                                   placeholder="<?php echo $kga['lang']['searchFilter']; ?>"
                                   name="filter"
                                   id="filter"
                                   onkeyup="filter_selects('add_edit_timeSheetEntry_activityID', this.value);"/>
                        </div>
                    </li>

                    <li>
                        <label for="description"><?php echo $kga['lang']['description'] ?>:</label>
                        <textarea tabindex="5"
                                  style="width:395px"
                                  cols='40'
                                  rows='5'
                                  name="description"
                                  id="description"><?php echo $this->escape($this->description) ?></textarea>
                    </li>

                    <li>
                        <label><?php echo $kga['lang']['day'] ?>:</label>
                        <span style="width: 150px; display: inline-block;">
                         <input id='start_day'
                                type='text'
                                name='start_day'
                                value='<?php echo $this->escape($this->start_day) ?>'
                                maxlength='10'
                                size='8'
                                tabindex='6'
                                style="text-align:center;"
                                onChange="ts_ext_timeToDuration();" <?php if ($kga['pref']['autoselection']): ?>
                             onClick="this.select();" <?php endif; ?> />
                        </span>
                        <input id='end_day'
                               type='text'
                               name='end_day'
                               value='<?php echo $this->escape($this->end_day) ?>'
                               maxlength='10'
                               size='8'
                               tabindex='7'
                               style="text-align:center;"
                               onChange="ts_ext_timeToDuration();" <?php if ($kga['pref']['autoselection']): ?>
                            onClick="this.select();" <?php endif; ?> />
                    </li>

                    <li style="vertical-align:top;">
                        <label><?php echo $kga['lang']['timelabel'] ?>:</label>
                       <span style="width: 150px; display: inline-block;">
                            <input id='start_time' type='text' name='start_time' style="text-align:center;"
                                   value='<?php echo $this->escape($this->start_time) ?>'
                                   maxlength='5' size='3' tabindex='8' onChange="ts_ext_startTimeToDuration();"
                                <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();" <?php endif; ?> />
                            <a id="currentTime"
                               href="#"
                               onClick="ts_ext_pasteNowToStart(); ts_ext_startTimeToDuration(); $(this).blur(); return false;">
                                <?php echo $kga['lang']['now'] ?></a>
                       </span>

                        <input id='end_time' type='text' name='end_time' style="text-align:center;"
                               value='<?php echo $this->escape($this->end_time) ?>'
                               maxlength='5' size='3' tabindex='9' onChange="ts_ext_endTimeToDuration();"
                            <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();"
                            <?php endif; ?> />
                        <a id="currentTime"
                           href="#"
                           onClick="ts_ext_pasteNowToEnd(); ts_ext_endTimeToDuration(); $(this).blur(); return false;">
                            <?php echo $kga['lang']['now'] ?></a>
                    </li>
                    <li>
                        <label for="duration"><?php echo $kga['lang']['durationlabel'] ?>:</label>
                        <span style="width: 210px; display: inline-block;text-align:center;">
                            <input id='duration' type='text' name='duration' value='' style="text-align:center;"
                                   onChange="ts_ext_durationToTime();" maxlength='5' size='3' tabindex='10'
                                <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();"<?php endif; ?> />
                        </span>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="extended">

                <ul>

                    <li>
                        <label for="location"><?php echo $kga['lang']['location'] ?>:</label>
                        <input id='location'
                               type='text'
                               name='location'
                               value='<?php echo $this->escape($this->location) ?>'
                               maxlength='50'
                               size='20'
                               tabindex='11' <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();"<?php endif; ?> />
                    </li>

                    <?php if ($kga['conf']['ref_num_editable']): ?>
                        <li>
                            <label for="ref_code"><?php echo $kga['lang']['ref_code'] ?>:</label>
                            <input id='ref_code' type='text' name='ref_code'
                                   value='<?php echo $this->escape($this->ref_code) ?>' maxlength='20' size='20'
                                   tabindex='12' <?php if ($kga['pref']['autoselection']): ?>
                                onClick="this.select();"<?php endif; ?> />
                        </li>
                    <?php endif; ?>
                    <li>
                        <label for="comment"><?php echo $kga['lang']['comment'] ?>:</label>
                        <textarea id='comment'
                                  style="width:395px"
                                  class='comment'
                                  name='comment'
                                  cols='40'
                                  rows='5'
                                  tabindex='13'><?php echo $this->escape($this->comment) ?></textarea>
                    </li>

                    <li>
                        <label for="comment_type"><?php echo $kga['lang']['comment_type'] ?>:</label>
                        <?php echo $this->formSelect('comment_type', $this->comment_type, array(
                            'id'       => 'comment_type',
                            'class'    => 'formfield',
                            'tabindex' => '14',
                            'style'    => 'min-width: 150px;'), $this->commentTypes); ?>
                    </li>
                    <?php if (count($this->users) > 0): ?>
                        <li>
                            <label for="user_id"><?php echo $kga['lang']['user'] ?>:</label>
                            <?php echo $this->formSelect(
                                isset($this->id) ? 'user_id' : 'user_id[]',
                                $this->user_id,
                                array(
                                    'id'       => 'user_id',
                                    'class'    => 'formfield',
                                    'multiple' => isset($this->id) ? '' : 'multiple',
                                    'tabindex' => '15',
                                    'style'    => 'min-width: 150px;'),
                                $this->users); ?>
                        </li>
                    <?php else: ?>
                        <input type="hidden" name="user_id" value="<?php echo $kga['user']['user_id']; ?>"/>
                    <?php endif; ?>

                    <?php if (!$new_entry) { ?>
                        <li>
                           <label for="erase"><?php echo $kga['lang']['erase'] ?>:</label>
                           <input type='checkbox' id='erase' name='erase' tabindex='16'/>
                       </li>
                    <?php } ?>

                    <li>
                        <label for="cleared"><?php echo $kga['lang']['cleared'] ?>:</label>
                        <input type='checkbox'
                               id='cleared'
                               name='cleared' <?php if ($this->cleared): ?> checked="checked" <?php endif; ?>
                               tabindex='17'/>

                    </li>

                </ul>

            </fieldset>
            <fieldset id="budget">

                <ul>

                    <li>
                        <label for="budget"><?php echo $kga['lang']['budget'] ?>:</label>
                        <input id='budget_val'
                               type='text'
                               name='budget'
                               value='<?php echo $this->escape($this->budget) ?>'
                               maxlength='10'
                               size='5'
                               tabindex='18'
                               style="text-align:right;padding-left:5px;"
                            <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();"<?php endif; ?> />
                        <?php echo ' ', $kga['conf']['currency_sign']; ?>
                    </li>
                    <li>
                        <label for="approved"><?php echo $kga['lang']['approved'] ?>:</label>
                        <input id='approved'
                               type='text'
                               name='approved'
                               value='<?php echo $this->escape($this->approved) ?>'
                               maxlength='10'
                               size='5'
                               tabindex='19'
                               style="text-align:right;padding-left:5px;"
                            <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();"<?php endif; ?> />
                        <?php echo ' ', $kga['conf']['currency_sign']; ?>
                    </li>

                    <li>
                        <label for="status_id"><?php echo $kga['lang']['status'] ?>:</label>
                        <?php echo $this->formSelect('status_id', $this->status_id, array(
                            'id'       => 'status_id',
                            'class'    => 'formfield',
                            'tabindex' => '20',
                            'style'    => 'min-width: 75px;'), $this->status); ?>
                    </li>

                    <li>
                        <label for="billable"><?php echo $kga['lang']['billable'] ?>:</label>
                        <?php echo $this->formSelect('billable', $this->billable_active, array(
                            'id'       => 'billable',
                            'class'    => 'formfield',
                            'tabindex' => '21',
                            'style'    => 'min-width: 75px;'), $kga['bill_pct']); ?>
                    </li>
                    <?php if ($this->showRate): ?>
                        <li>
                            <label for="rate"><?php echo $kga['lang']['rate_hourly'] ?>:</label>
                            <input id='rate' type='text' name='rate' size='5' style="text-align:right;padding-left:5px;"
                                   tabindex='22' value='<?php echo $this->escape($this->rate) ?>'/>
                            <?php echo ' ', $kga['conf']['currency_sign']; ?>

                            <label for="fixed_rate" style="float: none; margin-left: 60px;vertical-align: top;">
                                <?php echo $kga['lang']['fixed_rate'] ?>:</label>
                            <input id='fixed_rate'
                                   type='text'
                                   name='fixed_rate'
                                   size='5'
                                   style="text-align:right;padding-left:5px;"
                                   tabindex='23'
                                   value='<?php echo $this->escape($this->fixed_rate) ?>'
                                <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();"<?php endif; ?> />
                            <?php echo ' ', $kga['conf']['currency_sign']; ?>
                        </li>
                    <?php endif; ?>

                    <li>
                        <table>
                            <tr>
                                <td style="text-align:right"><?php echo $kga['lang']['budget_activity'] ?>:</td>
                                <td>
                                    <span id="budget_activity"><?php echo $this->budget_activity ?></span></td>
                            </tr>
                            <tr>
                                <td style="text-align:right"><?php echo $kga['lang']['budget_activity_used'] ?>:</td>
                                <td>
                                    <span id="budget_activity_used"><?php echo $this->budget_activity_used ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:right"><?php echo $kga['lang']['budget_activity_approved'] ?>:</td>
                                <td>
                                    <span id="budget_activity_approved"><?php echo $this->approved_activity ?></span>
                                </td>
                            </tr>
                        </table>
                    </li>
                    <?php if (isset($this->id)) { ?>
                        <li style="border:none;">
                            <div id="chart"></div>
                        </li>
                    <?php } ?>

                </ul>

            </fieldset>

        </div>

        <div id="formbuttons">
            <input class='btn_norm'
                   type='button'
                   value='<?php echo $kga['lang']['cancel'] ?>'
                   onClick='floaterClose(); return false;'/>
            <input class='btn_ok' type='submit' value='<?php echo $kga['lang']['submit'] ?>'/>
        </div>

    </form>

</div>
