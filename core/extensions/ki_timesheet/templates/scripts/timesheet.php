<?php
global $kga;
if ($this->timeSheetEntries) {
    ?>
    <table id="ts_m_tbl">

        <colgroup>
            <?php if (is_user()) echo '<col class="option"/>'; ?>
            <col class="date"/>
            <col class="from"/>
            <col class="to"/>
            <col class="time"/>
            <?php if ($this->showRates) { echo '<col class="wage"/>'; } ?>
            <col class="customer"/>
            <col class="project"/>
            <col class="activity"/>
            <?php if ($this->show_ref_code) { echo '<col class="ref_code"/>'; } ?>
            <col class="username"/>
        </colgroup>

        <tbody>

        <?php
        $day_buffer  = 0; // last day entry
        $time_buffer = 0; // last time entry
        $end_buffer  = 0; // last time entry
        $ts_buffer   = 0; // current time entry

        foreach ($this->timeSheetEntries as $rowIndex => $row) {
            //Assign initial value to time buffer which must be larger than or equal to "end"
            if ($time_buffer == 0) {
                $time_buffer = $row['end'];
            }

            if ($end_buffer == 0) {
                $end_buffer = $row['end'];
            }

            $start     = strftime("%d", $row['start']);
            $end       = (isset($row['end']) && $row['end']) ? $row['end'] : 0;
            $ts_buffer = strftime("%H%M", $end);

            $tdClass = "";
            if ($this->showOverlapLines && $end > $time_buffer) {
                $tdClass = " time_overlap";
            }
            elseif ($kga['conf']['show_day_separator_lines'] && $start != $day_buffer) {
                $tdClass = " break_day";
            }
            elseif ($kga['conf']['show_gab_breaks'] && (strftime("%H%M", $time_buffer) - strftime("%H%M", $row['end']) > 1)) {
                $tdClass = " break_gap";
            }
            /*
            if ($row['end'] > $time_buffer                      && $this->showOverlapLines)              echo "time_overlap";
            elseif (strftime("%d",$row['start']) != $day_buffer && $kga['conf']['show_day_separator_lines']) echo "break_day";
            elseif ($row['end'] != $start_buffer                && $kga['conf']['show_gab_breaks'])         echo "break_gap";
            */

            ?>

            <?php if ($row['end']): ?>
                <tr id="ts_<?php echo $row['time_entry_id'] ?>"
                    class="<?php echo $this->cycle(array("odd", "even"))->next() ?>"><?php else: ?><tr id="ts_Entry<?php echo $row['time_entry_id'] ?>" class="<?php echo $this->cycle(array("odd", "even"))->next() ?> active"><?php endif; ?>

            <?php if (is_user()) { ?>
            <td style="white-space: nowrap" class="option <?php echo $tdClass; ?>">
                <table style="width:100%;border:none;">
                    <tr>

                    <?php if ($row['end']) { ?>
                        <?php if ($kga['conf']['show_record_again']) { ?>
                            <td style="padding:0;border:none;">
                                <a onClick="ts_ext_recordAgain(<?php echo $row['project_id'] ?>,<?php echo $row['activity_id'] ?>,<?php echo $row['time_entry_id'] ?>); return false;"
                                    href="#" class="recordAgain">
                                    <img
                                    src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_recordthis.gif'
                                    width='13' height='13' alt='<?php echo $kga['dict']['tip']['ts_recordAgain'] ?>'
                                    title='<?php echo $kga['dict']['tip']['ts_recordAgain'] ?> (ID:<?php echo $row['time_entry_id'] ?>)'
                                    border='0'/>
                                </a>
                            </td>
                        <?php } ?>

                    <?php } else { // Stop oder Record Button? ?>
                        <td style="padding:0;border:none;">
                            <a href='#' class='stop'
                               onClick="ts_ext_stopRecord(<?php echo $row['time_entry_id'] ?>); return false;">
                                <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_stopthis.gif'
                                    width='13' height='13' alt='<?php echo $kga['dict']['tip']['ts_stop'] ?>'
                                    title='<?php echo $kga['dict']['tip']['ts_stop'] ?> (ID:<?php echo $row['time_entry_id'] ?>)'
                                    border='0'/>
                            </a>
                        </td>
                    <?php } ?>
                    <?php if ($kga['conf']['edit_limit'] == "-" || time() - $row['end'] <= $kga['conf']['edit_limit']) {
                        //Edit Record Button
                        ?>
                        <td style="padding:0;border:none;">
                            <a href='#'
                                 onClick="ts_ext_editRecord(<?php echo $row['time_entry_id'] ?>); $(this).blur(); return false;"
                                 title='<?php echo $kga['dict']['tip']['g_edit'] ?>'>
                                <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif'
                                    width='13' height='13' alt='<?php echo $kga['dict']['tip']['g_edit'] ?>'
                                    title='<?php echo $kga['dict']['tip']['g_edit'] ?>' border='0'/>
                            </a>
                        </td>
                    <?php } ?>
                    <?php if ($kga['pref']['quickdelete'] > 0) {
                        // quick erase trashcan
                        ?>
                        <td style="padding:0;border:none;">
                            <a href='#' class='quickdelete'
                                 onClick="ts_ext_quickdelete(<?php echo $row['time_entry_id'] ?>); return false;">
                                <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan.png'
                                    width='13' height='13' alt='<?php echo $kga['dict']['tip']['g_delete'] ?>'
                                    title='<?php echo $kga['dict']['tip']['g_delete'] ?>' border=0/>
                            </a>
                        </td>
                    <?php } ?>
                    </tr>
                </table>
            </td>
            <?php }; ?>

            <td class="date <?php echo $tdClass; ?>">
                <?php echo $this->escape(strftime($kga['conf']['date_format_1'], $row['start'])); ?>
            </td>

            <td class="from <?php echo $tdClass; ?>">
                <?php echo $this->escape(strftime("%H:%M", $row['start'])); ?>
            </td>

            <td class="to <?php echo $tdClass; ?>">
                <?php
                if ($row['end']) {
                    echo $this->escape(strftime("%H:%M", $row['end']));
                }
                else {
                    echo "&ndash;&ndash;:&ndash;&ndash;";
                }
                ?>
            </td>

            <td class="time <?php echo $tdClass; ?>">
                <?php
                if (isset($row['duration'])) {
                    echo $row['formatted_duration'];
                }
                else {
                    echo "&ndash;:&ndash;&ndash;";
                }
                ?>
            </td>

            <?php if ($this->showRates): ?>
            <td class="wage <?php echo $tdClass; ?> ">
                <?php
                if (isset($row['wage'])) {
                    echo $this->escape(str_replace('.', $kga['conf']['decimal_separator'], $row['wage']));
                }
                else {
                    echo "&ndash;";
                }
                ?>
                </td>
            <?php endif; ?>

            <td class="customer <?php echo $tdClass; ?>">
                <?php echo $this->escape($row['customer_name']) ?>
            </td>
            <td class="project <?php echo $tdClass; ?>">
                <a href="#" class="preselect_lnk" title="<?php echo $kga['dict']['tip']['g_select_for_recording']; ?>"
                   onClick="buzzer_preselect_project(<?php echo $row['project_id'] ?>,'<?php echo $this->jsEscape($row['project_name']) ?>',<?php echo $this->jsEscape($row['customer_id']) ?>,'<?php echo $this->jsEscape($row['customer_name']) ?>');return false;">
                    <?php echo $this->escape($row['project_name']) ?>
                    <?php if ($kga['pref']['project_comment_flag'] == 1 && $row['project_comment']): ?>
                        <span class="lighter">(<?php echo $this->escape($row['project_comment']) ?>
                                              )</span><?php endif; ?>
                </a>
            </td>

            <td class="activity <?php echo $tdClass; ?>">
                <a href="#" class="preselect_lnk" title="<?php echo $kga['dict']['tip']['g_select_for_recording']; ?>"
                   onClick="buzzer_preselect_activity(<?php echo $row['activity_id'] ?>,'<?php echo $this->jsEscape($row['activity_name']) ?>',0,0);return false;">
                    <?php echo $this->escape($row['activity_name']) ?>
                </a>

                <?php if ($row['comment']): ?><?php if ($row['comment_type'] == '0'): ?><a href="#"
                                                                                           onClick="ts_ext_comment(<?php echo $row['time_entry_id'] ?>); $(this).blur(); return false;">
                    <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/blase.gif'
                         width="12" height="13" title='<?php echo $this->escape($row['comment']) ?>' border="0"/>
                    </a><?php elseif ($row['comment_type'] == '1'): ?><a href="#"
                                                                         onClick="ts_ext_comment(<?php echo $row['time_entry_id'] ?>); $(this).blur(); return false;">
                    <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/blase_sys.gif'
                         width="12" height="13" title='<?php echo $this->escape($row['comment']) ?>' border="0"/>
                    </a><?php
                elseif ($row['comment_type'] == '2'): ?><a href="#"
                                                           onClick="ts_ext_comment(<?php echo $row['time_entry_id'] ?>); $(this).blur(); return false;">
                    <img
                        src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/blase_caution.gif'
                        width="12" height="13" title='<?php echo $this->escape($row['comment']) ?>' border="0"/>
                    </a><?php endif; ?><?php endif; ?>
            </td>

            <?php if ($this->show_ref_code) { ?>
                <td class="ref_code <?php echo $tdClass; ?>">
                    <?php echo $this->escape($row['ref_code']) ?>
                </td>
            <?php }; ?>

            <td class="username <?php echo $tdClass; ?>">
                <?php echo $this->escape($row['username']) ?>
            </td>

            </tr>

            <?php if ($row['comment']): ?>
            <tr id="c<?php echo $row['time_entry_id'] ?>"
                class="comm<?php echo $this->escape($row['comment_type']) ?>" <?php if ($this->hideComments): ?> style="display:none" <?php endif; ?> >
                <td colspan="11"><?php echo nl2br($this->escape($row['comment'])) ?></td>
                </tr><?php endif; ?>

            <?php
            $day_buffer  = strftime("%d", $row['start']);
            $time_buffer = $row['start'];
            $end_buffer  = $row['end'];
        }
        ?>

        </tbody>
    </table>
<?php
}
else {
    echo $this->error();
}
?>

<script type="text/javascript">
    var ts_user_annotations = <?php echo json_encode($this->user_annotations); ?>,
    ts_customer_annotations = <?php echo json_encode($this->customer_annotations) ?>,
    ts_project_annotations = <?php echo json_encode($this->project_annotations) ?>,
    ts_activity_annotations = <?php echo json_encode($this->activity_annotations) ?>,
    ts_total = '<?php echo $this->total?>';

    lists_update_annotations(parseInt($('#gui').find('div.ext.ki_timesheet').attr('id').substring(7)), ts_user_annotations, ts_customer_annotations, ts_project_annotations, ts_activity_annotations);
    $('#display_total').html(ts_total);

    <?php if ($this->latest_running_entry == null): ?>
    updateRecordStatus(false);
    <?php else: ?>

    updateRecordStatus(<?php echo $this->latest_running_entry['time_entry_id']?>, <?php echo $this->latest_running_entry['start']?>,
        <?php echo $this->latest_running_entry['customer_id']?>, '<?php echo $this->jsEscape($this->latest_running_entry['customer_name'])?>',
        <?php echo $this->latest_running_entry['project_id']?>, '<?php echo $this->jsEscape($this->latest_running_entry['project_name'])?>',
        <?php echo $this->latest_running_entry['activity_id']?>, '<?php echo $this->jsEscape($this->latest_running_entry['activity_name'])?>');
    <?php endif; ?>

    function timesheet_hide_column(name) {
        $('.' + name).hide();
    }

    <?php if (!$this->show_ref_code) { ?>
        timesheet_hide_column('ref_code');
    <?php } ?>

</script>
