<?php
global $kga;
if ($this->expenses) {
    ?>
        <table id="xpe_m_tbl">
            <colgroup>
                <col class="option"/>
                <col class="date"/>
                <col class="time"/>
                <col class="value"/>
                <col class="refundable"/>
                <col class="customer"/>
                <col class="project"/>
                <col class="description"/>
                <col class="username"/>
            </colgroup>
            <tbody>
            <?php
            $day_buffer = 0;
            $timestamp_buffer = 0;

            foreach ($this->expenses as $row) {
                $cur_day_buffer       = strftime("%d", $row['timestamp']);
                $cur_timestamp_buffer = strftime("%H%M", $row['timestamp']);

                $td_class = null;
                if ($cur_day_buffer != $day_buffer && $kga['conf']['show_day_separator_lines']) {
                    $td_class =  " break_day ";
                }
                elseif ($cur_timestamp_buffer != $timestamp_buffer && $kga['conf']['show_gab_breaks']) {
                    $td_class =  " break_gap ";
                }


                ?>
                <tr id="xpe_<?php echo $row['expense_id'] ?>"
                    class="<?php echo $this->cycle(array("odd", "even"))->next() ?>">

                    <td style="white-space: nowrap" class="option <?php echo $td_class ?>">

                        <?php if (isset($kga['user']) && ($kga['conf']['edit_limit'] == "-" || time() - $row['timestamp'] <= $kga['conf']['edit_limit'])): ?>
                            <a href='#'
                               onClick="xpe_ext_editRecord(<?php echo $row['expense_id'] ?>); $(this).blur(); return false;"
                               title='<?php echo $kga['lang']['edit'] ?>'>
                                <img
                                    src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif'
                                    width='13' height='13' alt='<?php echo $kga['lang']['tip']['g_edit'] ?>'
                                    title='<?php echo $kga['lang']['tip']['g_edit'] ?>' border='0'/></a>

                            <?php if ($kga['pref']['quickdelete'] > 0): ?>
                                <a href='#' class='quickdelete'
                                   onClick="xpe_ext_quickdelete(<?php echo $row['expense_id'] ?>); return false;">
                                    <img
                                        src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan.png'
                                        width='13' height='13' alt='<?php echo $kga['lang']['tip']['g_delete'] ?>'
                                        title='<?php echo $kga['lang']['tip']['g_delete'] ?>' border=0/>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                    </td>

                    <td class="date <?php echo $td_class ?>">
                        <?php echo $this->escape(strftime($kga['conf']['date_format_1'], $row['timestamp'])) ?>
                    </td>

                    <td class="time <?php echo $td_class ?>">
                        <?php echo $this->escape(strftime("%H:%M", $row['timestamp'])) ?>
                    </td>

                    <td class="value <?php echo $td_class ?>">
                        <?php echo $this->escape(number_format($row['value'] * $row['multiplier'], 0, $kga['conf']['decimal_separator'], '')) ?>
                    </td>

                    <td class="refundable <?php echo $td_class ?>">
                        <?php echo $row['refundable'] ? $kga['lang']['yes'] : $kga['lang']['no'] ?>
                    </td>

                    <td class="customer <?php echo $td_class ?>">
                        <?php echo $this->escape($row['customer_name']) ?>
                    </td>

                    <td class="project <?php echo $td_class ?>">
                        <?php echo $this->escape($row['project_name']) ?>
                    </td>

                    <td class="description <?php echo $td_class ?>">
                        <?php echo $this->escape($row['description']) ?>

                        <?php if ($row['comment']): ?>
                            <?php if ($row['comment_type'] == '0'): ?>
                                <a href="#"
                                   onClick="xpe_ext_comment(<?php echo $row['expense_id'] ?>); $(this).blur(); return false;"><img
                                        src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/blase.gif'
                                        width="12" height="13" title='<?php echo $this->escape($row['comment']); ?>'
                                        border="0"/></a>
                            <?php elseif ($row['comment_type'] == '1'): ?>
                                <a href="#"
                                   onClick="xpe_ext_comment(<?php echo $row['expense_id'] ?>); $(this).blur(); return false;"><img
                                        src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/blase_sys.gif'
                                        width="12" height="13" title='<?php echo $this->escape($row['comment']); ?>'
                                        border="0"/></a>
                            <?php
                            elseif ($row['comment_type'] == '2'): ?>
                                <a href="#"
                                   onClick="xpe_ext_comment(<?php echo $row['expense_id'] ?>); $(this).blur(); return false;"><img
                                        src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/blase_caution.gif'
                                        width="12" height="13" title='<?php echo $this->escape($row['comment']); ?>'
                                        border="0"/></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>

                    <td class="username <?php echo $td_class ?>">
                        <?php echo $this->escape($row['username']) ?>
                    </td>

                </tr>

                <?php
                if ($row['comment']) {
                    ?>
                    <tr id="xpe_c_<?php echo $row['expense_id'] ?>"
                        class="comm<?php echo $row['comment_type'] ?>" <?php if ($this->hideComments): ?> style="display:none;"<?php endif; ?>>
                        <td colspan="8"><?php echo nl2br($this->escape($row['comment'])); ?></td>
                    </tr>
                <?php }
                $day_buffer       = $cur_day_buffer;
                $timestamp_buffer = $cur_timestamp_buffer;
            } ?>
            </tbody>
        </table>
<?php }
else { echo $this->error(); } ?>

<script type="text/javascript">
    var expense_user_annotations = <?php echo json_encode($this->user_annotations); ?>;
    var expense_customer_annotations = <?php echo json_encode($this->customer_annotations) ?>;
    var expense_project_annotations = <?php echo json_encode($this->project_annotations) ?>;
    var expense_activity_annotations = <?php echo json_encode($this->activity_annotations) ?>;
    expenses_total = '<?php echo $this->total?>';

    lists_update_annotations(parseInt($('#gui').find('div.ext.ki_expenses').attr('id').substring(7)), expense_user_annotations, expense_customer_annotations, expense_project_annotations, expense_activity_annotations);
    $('#display_total').html(expenses_total);

</script>
