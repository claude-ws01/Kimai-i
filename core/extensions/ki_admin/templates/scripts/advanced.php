<?php
global $kga;
?>
<script type="text/javascript">
    function cb(result) {
        var adminOutput = $("#adm_ext_output");

        if (result.errors.length == 0) {
            window.location.reload();
            return;
        }
        $("#adm_ext_form_editadv_submit").blur();
        adminOutput.width($(".adm_ext_panel_header").width() - 22);
        adminOutput.fadeIn(fading_enabled ? 100 : 0, function () {
            adminOutput.fadeOut(fading_enabled ? 100 : 0);
        });
    }
    $(document).ready(function () {

        $('.disableInput').click(function () {
            var input = $(this);
            if (input.is(':checked')) {
                input.parent().removeClass("disabled");
                input.siblings().prop("disabled", false);
            }
            else {
                input.parent().addClass("disabled");
                input.siblings().prop("disabled", true);
            }
        });

        $('#adm_ext_form_editadv').ajaxForm({
            beforeSubmit: function () {
                $('#ajax_wait').show();
            },
            target: '#adm_ext_output',
            success: cb
        });
    });
</script>

<div class="content">

    <div id="adm_ext_output"></div>

    <form id="adm_ext_form_editadv" action="../extensions/ki_admin/processor.php" method="post">

        <fieldset class="adm_ext_advanced" style="padding-top:0;margin-top:0;">
            <div>
                <input type="text" name="admin_mail" size="20"
                       value="<?php echo $this->escape($kga['conf']['admin_mail']) ?>"
                       class="formfield"> <?php echo $kga['dict']['admin_mail'] ?>
            </div>
            <div>
                <input type="text" name="login_tries" size="2"
                       value="<?php echo $this->escape($kga['conf']['login_tries']) ?>"
                       class="formfield"> <?php echo $kga['dict']['login_tries'] ?>
            </div>
            <div>
                <input type="text" name="login_ban_time" size="4"
                       value="<?php echo $this->escape($kga['conf']['login_ban_time']) ?>"
                       class="formfield"> <?php echo $kga['dict']['bantime'] ?>
            </div>
            <div id="adm_ext_checkupdate">
                <a href="javascript:adm_ext_checkupdate();"><?php echo $kga['dict']['checkupdate'] ?></a>
            </div>
            <div>
                <input type="checkbox"
                       name="show_sensible_data" <?php if ($kga['conf']['show_sensible_data']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield"> <?php echo $kga['dict']['show_sensible_data'] ?>
            </div>
            <div>
                <input type="checkbox"
                       name="show_update_warn" <?php if ($kga['conf']['show_update_warn']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield"> <?php echo $kga['dict']['show_update_warn'] ?>
            </div>
            <div>
                <input type="checkbox"
                       name="check_at_startup" <?php if ($kga['conf']['check_at_startup']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield"> <?php echo $kga['dict']['check_at_startup'] ?>
            </div>
            <div>
                <input type="checkbox"
                       name="show_day_separator_lines" <?php if ($kga['conf']['show_day_separator_lines']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield"> <?php echo $kga['dict']['show_day_separator_lines'] ?>
            </div>
            <div>
                <input type="checkbox"
                       name="show_gab_breaks" <?php if ($kga['conf']['show_gab_breaks']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield"> <?php echo $kga['dict']['show_gab_breaks'] ?>
            </div>
            <div>
                <input type="checkbox"
                       name="show_record_again" <?php if ($kga['conf']['show_record_again']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield"> <?php echo $kga['dict']['show_record_again'] ?>
            </div>
            <div>
                <input type="checkbox"
                       name="ref_num_editable" <?php if ($kga['conf']['ref_num_editable']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield"> <?php echo $kga['dict']['ref_num_editable'] ?>
            </div>
            <div>
                <input type="text" name="bill_pct" size="20"
                       value="<?php echo $this->escape($kga['conf']['bill_pct']) ?>"
                       class="formfield"> <?php echo $kga['dict']['cf']['bill_pct'] ?>
            </div>
            <div>
                <input type="text" name="currency_name" size="8"
                       value="<?php echo $this->escape($kga['conf']['currency_name']) ?>"
                       class="formfield"> <?php echo $kga['dict']['currency_name'] ?>
            </div>
            <div>
                <input type="text" name="currency_sign" size="2"
                       value="<?php echo $this->escape($kga['conf']['currency_sign']) ?>"
                       class="formfield"> <?php echo $kga['dict']['currency_sign'] ?>
            </div>

            <div>
                <input type="text" name="vat_rate" size="2"
                       value="<?php echo str_replace('.',$kga['conf']['decimal_separator'],$kga['conf']['vat_rate']) ?>"
                       class="formfield"> <?php echo $kga['dict']['cf']['vat_rate'] ?>
            </div>

            <div>
                <input type="checkbox"
                       name="currency_first" <?php if ($kga['conf']['currency_first']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield"> <?php echo $kga['dict']['currency_first'] ?>
            </div>
            <div>
                <input type="text" name="date_format_2" size="15"
                       value="<?php echo $this->escape($kga['conf']['date_format_2']) ?>"
                       class="formfield"> <?php echo $kga['dict']['display_date_format'] ?>
            </div>
            <div>
                <input type="text" name="date_format_0" size="15"
                       value="<?php echo $this->escape($kga['conf']['date_format_0']) ?>"
                       class="formfield"> <?php echo $kga['dict']['display_currentDate_format'] ?>
            </div>
            <div>
                <input type="text" name="date_format_1" size="15"
                       value="<?php echo $this->escape($kga['conf']['date_format_1']) ?>"
                       class="formfield"> <?php echo $kga['dict']['table_date_format'] ?>
            </div>
            <div>
                <input type="checkbox"
                       name="duration_with_seconds" <?php if ($kga['conf']['duration_with_seconds']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield"> <?php echo $kga['dict']['duration_with_seconds'] ?>
            </div>
            <div>
                <?php echo $kga['dict']['round_time'] ?> <select name="round_precision" class="formfield">
                    <option
                        value="0" <?php if ((int)$kga['conf']['round_precision'] === 0): ?> selected="selected" <?php endif; ?>>
                        -
                    </option>
                    <option
                        value="1" <?php if ((int)$kga['conf']['round_precision'] === 1): ?> selected="selected" <?php endif; ?>>
                        1
                    </option>
                    <option
                        value="5" <?php if ((int)$kga['conf']['round_precision'] === 5): ?> selected="selected" <?php endif; ?>>
                        5
                    </option>
                    <option
                        value="10" <?php if ((int)$kga['conf']['round_precision'] === 10): ?> selected="selected" <?php endif; ?>>
                        10
                    </option>
                    <option
                        value="15" <?php if ((int)$kga['conf']['round_precision'] === 15): ?> selected="selected" <?php endif; ?>>
                        15
                    </option>
                    <option
                        value="15" <?php if ((int)$kga['conf']['round_precision'] === 20): ?> selected="selected" <?php endif; ?>>
                        20
                    </option>
                    <option
                        value="30" <?php if ((int)$kga['conf']['round_precision'] === 30): ?> selected="selected" <?php endif; ?>>
                        30
                    </option>
                </select> <?php echo $kga['dict']['round_time_minute'] ?>
                    <input type="checkbox"
                          name="allow_round_down" <?php if ($kga['conf']['allow_round_down']): ?> checked="checked" <?php endif; ?>
                          value="1" class="formfiled">
                    <?php echo $kga['dict']['allow_round_down']; ?>
            </div>
            <div>
                <?php echo $kga['dict']['decimal_separator'] ?>:
                <input type="text" name="decimal_separator"
                      size="1"
                      value="<?php echo $this->escape($kga['conf']['decimal_separator']) ?>"
                      class="formfield">
            </div>
            <div>
                <input type="checkbox"
                       name="exact_sums" <?php if ($kga['conf']['exact_sums']): ?> checked="checked" <?php endif; ?>
                       value="1" class="formfield">
                <?php echo $kga['dict']['exact_sums'] ?>
            </div>
            <div <?php if (!$this->edit_limit_enabled): ?> class="disabled" <?php endif; ?>>
                <input type="checkbox" name="edit_limit_enabled"
                       value="1" <?php if ($this->edit_limit_enabled): ?> checked="checked" <?php endif; ?>
                       class="formfield, disableInput"> <?php echo $kga['dict']['editLimitPart1'] ?>
                <input type="text" name="edit_limit_days" class="formfield"
                       size="3" value="<?php echo $this->edit_limit_days ?>"
                       <?php if (!$this->edit_limit_enabled): ?>disabled="disabled" <?php endif; ?>>
                <?php echo $kga['dict']['editLimitPart2'] ?>
                <input type="text" name="edit_limit_hours" size="3" class="formfield"
                       value="<?php echo $this->edit_limit_hours ?>" <?php if (!$this->edit_limit_enabled): ?>
                    disabled="disabled" <?php endif; ?>> <?php echo $kga['dict']['editLimitPart3'] ?>
            </div>

            <div style="border-bottom:4px double #666;" <?php if (!$this->round_timesheet_entries): ?> class="disabled" <?php endif; ?>>
                <input type="checkbox" name="round_timesheet_entries"
                    value="1"
                    <?php if ($this->round_timesheet_entries): ?> checked="checked" <?php endif; ?>
                    class="formfield, disableInput">
                <?php echo $kga['dict']['round_timesheet_entries'] ?>
                <input
                    type="text" name="round_minutes" size="3" class="formfield"
                    value="<?php echo $this->round_minutes ?>"
                    <?php if (!$this->round_timesheet_entries): ?>disabled="disabled" <?php endif; ?>>
                <?php echo $kga['dict']['minutes'] ?> <?php echo $kga['dict']['and'] ?>
                <input
                    type="text" name="round_seconds" size="3" class="formfield"
                    value="<?php echo $this->round_seconds ?>" <?php if (!$this->round_timesheet_entries): ?>
                    disabled="disabled" <?php endif; ?>>
                <?php echo $kga['dict']['seconds'] ?>
            </div>
            <!--  USER DEFAULTS  -->
            <div>
                <span style="font-weight:bold;">
                    <?php //  GENERAL OPTIONS   //
                    echo $kga['dict']['new_user_defaults'] ?>:
                </span>

                <div>
                    <?php echo $this->formSelect('ud_skin', $kga['conf']['ud.skin'],
                        array('style'=>'min-width:10px;'), $this->skins); ?>
                    <?php echo $kga['dict']['skin'] ?>
                </div>

                <div>
                    <?php echo $this->formSelect('ud_language', $kga['conf']['ud.language'], array('class' => 'formfield'), $this->langs); ?>
                    <?php echo $kga['dict']['lang'] ?></div>

                <div>
                    <?php echo $this->timeZoneSelect('ud_timezone', $kga['conf']['ud.timezone']); ?>
                    <?php echo $kga['dict']['ud']['timezone'] ?> </div>

                <div>
                    <?php echo $this->formCheckbox('ud_autoselection', '1', array('checked' => $kga['conf']['ud.autoselection']));
                    echo $kga['dict']['autoselection'] ?>
                </div>

                <div>
                    <?php echo $this->formCheckbox('ud_open_after_recorded', '1', array('checked' => $kga['conf']['ud.open_after_recorded']));
                    echo $kga['dict']['open_after_recorded'] ?>
                </div>

                <div style="border-bottom:2px solid #666;" >
                    <?php echo $this->formCheckbox('ud_no_fading', '1', array('checked' => $kga['conf']['ud.no_fading'])),
                    $kga['dict']['no_fading'] ?>
                </div>
                <?php //        NEW USER DEFAULTS          GRID-LIST OPTIONS   // ?>

                <div>
                    <?php echo $this->formText('ud_rowlimit', $kga['conf']['ud.rowlimit'], array('size' => 3, 'style' => 'text-align:center;')); ?>
                    <?php echo $kga['dict']['rowlimit'] ?></div>

                <div>
                    <?php
                    echo $this->formSelect(
                        'ud_quickdelete', $kga['conf']['ud.quickdelete'], null,
                        array($kga['dict']['quickdeleteHide'],
                            $kga['dict']['quickdeleteShow'],
                            $kga['dict']['quickdeleteShowConfirm'])
                    ); ?>
                    <?php echo $kga['dict']['quickdelete'] ?> </div>

                <div>
                    <?php echo $this->formCheckbox(
                        'ud_hide_cleared_entries',
                        '1',
                        array('checked' => $kga['conf']['ud.hide_cleared_entries']));
                    echo $kga['dict']['hide_cleared_entries'] ?>
                </div>

                <div>
                    <?php
                    echo $this->formCheckbox(
                        'ud_show_comments_by_default', '1',
                        array('checked' => $kga['conf']['ud.show_comments_by_default'])),
                    $kga['dict']['show_comments_by_default']; ?>
                </div>

                <div>
                    <?php
                    echo $this->formCheckbox(
                        'ud_show_ref_code', '1',
                        array('checked' =>$kga['conf']['ud.show_ref_code']));
                    echo $kga['dict']['show_ref_code']; ?>
                </div>

                <div style="border-bottom:2px solid #666;" >
                    <?php
                    echo $this->formCheckbox(
                        'ud_hide_overlap_lines', '1',
                        array('checked' => $kga['conf']['ud.hide_overlap_lines']));
                    echo $kga['dict']['hide_overlap_lines']
                    ?>
                </div>

                <?php //        NEW USER DEFAULTS          BOTTOM FILTER OPTIONS    // ?>
                <div>
                    <?php
                    echo $this->formSelect(
                        'ud_sublist_annotations',
                        $kga['conf']['ud.sublist_annotations'],
                        null,
                        array($kga['dict']['timelabel'],
                            $kga['dict']['export_extension']['costs'],
                            $kga['dict']['timelabel'] . ' & ' . $kga['dict']['export_extension']['costs'],
                        )
                    ); ?>
                    <?php echo $kga['dict']['sublist_annotations'] ?>  </div>

                <div>
                    <?php echo $this->formCheckbox('ud_flip_project_display', '1', array('checked' => $kga['conf']['ud.flip_project_display'])),
                    $kga['dict']['flip_project_display'] ?>
                </div>

                <div>
                    <?php echo $this->formCheckbox('ud_project_comment_flag', '1', array('checked' => $kga['conf']['ud.project_comment_flag'])),
                    $kga['dict']['project_comment_flag'] ?>
                </div>

                <div>
                    <?php echo $this->formCheckbox('ud_show_ids', '1', array('checked' => $kga['conf']['ud.show_ids'])),
                    $kga['dict']['show_ids'] ?>
                </div>

                <div>
                    <?php echo $this->formCheckbox('ud_user_list_hidden', '1', array('checked' => $kga['conf']['ud.user_list_hidden']));
                    echo $kga['dict']['user_list_hidden'];?>
                </div>
            </div>

            <input name="axAction" type="hidden" value="sendEditAdvanced"/>

            <div id="formbuttons">
                <input id="adm_ext_form_editadv_submit" class='btn_ok' type='submit'
                       value='<?php echo $kga['dict']['save'] ?>'/>
            </div>


        </fieldset>

    </form>

</div>
