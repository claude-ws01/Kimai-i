<?php 
global $kga;
if (count($this->exportData) < 1): ?>
    <div style='padding:5px;color:#f00'>
        <strong><?php echo $kga['lang']['noEntries'] ?></strong>
    </div>
<?php else: ?>

    <table id="xpo_m_tbl">

    <colgroup>
        <col class="date"/>
        <col class="from"/>
        <col class="to"/>
        <col class="time"/>
        <col class="dec_time"/>
        <col class="rate"/>
        <col class="wage"/>
        <col class="budget"/>
        <col class="approved"/>
        <col class="status"/>
        <col class="billable"/>
        <col class="customer"/>
        <col class="project"/>
        <col class="activity"/>
        <col class="description"/>
        <col class="comment"/>
        <col class="location"/>
        <col class="ref_code"/>
        <col class="user"/>
        <col class="cleared"/>
    </colgroup>

    <tbody>

    <?php
    $day_buffer = 0;
    $time_in_buffer = 0;

    foreach ($this->exportData as $row):
        $isExpense = $row['type'] === 'expense';
        
        $td_class = null;
        if (strftime('%d', $row['time_in']) != $day_buffer && $kga['conf']['show_day_separator_lines']) {
            $td_class = ' break_day ';
        }
        elseif ($row['time_out'] != $time_in_buffer && $kga['conf']['show_gab_breaks']) {
            $td_class = ' break_gap ';
        }
        ?>

        <tr id="xpo_<?php echo $row['type'], $row['id'] ?>"
            class="<?php echo $this->cycle(['odd', 'even'])->next() ?> <?php if (!$row['time_out']) {echo ' active';} ?>
                         <?php if ($isExpense) {echo ' expense';} ?>">

        <td class="date <?php echo $td_class, $this->disabled_columns['date'];?>">
            <?php echo $this->escape(strftime($this->dateformat, $row['time_in'])) ?>
        </td>

        <td class="from <?php echo $td_class, $this->disabled_columns['from'];?>">
            <?php echo $this->escape(strftime($this->timeformat, $row['time_in'])) ?>
        </td>

        <td class="to <?php echo $td_class, $this->disabled_columns['to'];?>">
            <?php
            if ($row['time_out']) {
                echo $this->escape(strftime($this->timeformat, $row['time_out']));
            }
            else {
                echo '&ndash;&ndash;:&ndash;&ndash;';
            }
            ?>
        </td>

        <td class="time <?php echo $td_class, $this->disabled_columns['time'];?>">
            <?php if ($row['duration']) {
                echo $row['formatted_duration'];
            }
            else {
                echo '&ndash;:&ndash;&ndash;';
            } ?>
        </td>

        <td class="dec_time <?php echo $td_class, $this->disabled_columns['dec_time'];?>">
            <?php if ($row['decimal_duration']) {
                echo $this->escape(str_replace('.', $kga['conf']['decimal_separator'], $row['decimal_duration']));
            }
            else {
                echo '&ndash;:&ndash;&ndash;';
            } ?>
        </td>

        <td class="rate <?php echo $td_class, $this->disabled_columns['rate'];?>">
            <?php echo $this->escape(str_replace('.', $kga['conf']['decimal_separator'], $row['rate'])); ?>
        </td>

        <td class="wage <?php echo $td_class, $this->disabled_columns['wage'];?>">
            <?php
            if ($row['wage']) {
                echo $this->escape(str_replace('.', $kga['conf']['decimal_separator'], $row['wage']));
            }
            else {
                echo '&ndash;';
            }
            ?>
        </td>

        <td class="budget <?php echo $td_class, $this->disabled_columns['budget'];?>">
            <?php echo !$isExpense ? $this->escape($row['budget']) : '&ndash;'; ?>
        </td>


        <td class="approved <?php echo $td_class, $this->disabled_columns['approved'];?>">
            <?php echo !$isExpense ? $this->escape($row['approved']) : '&ndash;'; ?>
        </td>


        <td class="status <?php echo $td_class, $this->disabled_columns['status'];?>">
            <?php echo !$isExpense ? $this->escape($row['status']) : '&ndash;'; ?>
        </td>


        <td class="billable <?php echo $td_class, $this->disabled_columns['billable'];?>">
            <?php echo !$isExpense ? $this->escape($row['billable']) . '%' : '&ndash;'; ?>
        </td>


        <td class="customer <?php echo $td_class, $this->disabled_columns['customer'];?>">
            <?php echo $this->escape($row['customer_name']) ?>
        </td>


        <td class="project <?php echo $td_class, $this->disabled_columns['project'];?>">
            <a href="#" class="preselect_lnk" title="<?php echo $kga['lang']['tip']['g_select_for_recording']; ?>"
               onClick="buzzer_preselect_project(<?php echo $row['project_id'] ?>,'<?php echo $this->jsEscape($row['project_name']) ?>',<?php echo $row['customer_id'] ?>,'<?php echo $this->jsEscape($row['customer_name']) ?>');return false;">
                <?php echo $this->escape($row['project_name']) ?>
                <?php if ($kga['pref']['project_comment_flag'] == 1): ?>
                    <?php if ($row['project_comment']): ?>
                        <span class="lighter">(<?php echo $this->jsEscape($row['project_comment']); ?>)</span>
                    <?php endif; ?>
                <?php endif; ?>
            </a>
        </td>


        <td class="activity <?php echo $td_class, $this->disabled_columns['activity'];?>">
            <?php
            if (!$isExpense): ?>
            <a href="#" class="preselect_lnk" title="<?php echo $kga['lang']['tip']['g_select_for_recording']; ?>"
               onClick="buzzer_preselect_activity(<?php echo $row['activity_id'] ?>,'<?php echo $this->jsEscape($row['activity_name']) ?>',0,0);return false;">
                <?php echo $this->escape($row['activity_name']);
                else: echo '&ndash;';
                endif;
                ?>
            </a>
        </td>

        <td class="description <?php echo $td_class, $this->disabled_columns['description'];?>">
            <?php echo nl2br($this->escape($row['description'])) ?>
        </td>


        <td class="comment <?php echo $td_class, $this->disabled_columns['comment'];?>">
            <?php echo nl2br($this->escape($row['comment'])) ?>
        </td>

        <td class="location <?php echo $td_class, $this->disabled_columns['location'];?>">
            <?php echo $this->escape($row['location']) ?>
        </td>


        <td class="ref_code <?php echo $td_class, $this->disabled_columns['ref_code'];?>">
            <?php echo $this->escape($row['ref_code']) ?>
        </td>


        <td class="user <?php echo $td_class, $this->disabled_columns['user'];?>">
            <?php echo $this->escape($row['username']) ?>
        </td>


        <td class="cleared <?php echo $td_class, $this->disabled_columns['cleared'];?>"
            title="<?php echo $kga['lang']['tip']['xpo_cleared_all']; ?>">
            <a class="<?php echo ($row['cleared']) ? 'is_cleared' : 'isnt_cleared' ?>" href="#"
               onClick="xpo_ext_toggle_cleared('<?php echo $row['type'], $row['id'] ?>'); return false;"
               title="<?php echo $kga['lang']['tip']['xpo_cleared']; ?>"></a>
        </td>

        </tr>

        <?php
        $day_buffer     = strftime("%d", $row['time_in']);
        $time_in_buffer = $row['time_in'];
    endforeach;
    ?>

    </tbody>
    </table>
<?php endif; ?>

<script type="text/javascript">
    var ts_user_annotations = <?php echo json_encode($this->user_annotations); ?>,
    ts_customer_annotations = <?php echo json_encode($this->customer_annotations) ?>,
    ts_project_annotations = <?php echo json_encode($this->project_annotations) ?>,
    ts_activity_annotations = <?php echo json_encode($this->activity_annotations) ?>,
    ts_total = '<?php echo $this->total?>';

    lists_update_annotations(parseInt($('#gui').find('div.ext.ki_export').attr('id').substring(7)), ts_user_annotations, ts_customer_annotations, ts_project_annotations, ts_activity_annotations);
    $('#display_total').html(ts_total);

</script>
