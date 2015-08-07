<?php global $kga; ?>
<script type="text/javascript">
    $(document).ready(function () {

        var options = {
            beforeSubmit: function () {
                var password = $('#password'),
                    retype = $('#retypePassword'),
                    pref = $('#core_prefs');

                if ((password.val() != "" || retype.val() != "")
                    && !validatePassword(password.val(), retype.val()))
                    return false;

                if (pref.attr('submitting')) {
                    return false;
                }
                else {
                    $('#ajax_wait').show();
                    pref.attr('submitting', true);
                    return true;
                }
            },
            success: function () {
                $('#core_prefs').removeAttr('submitting');

                window.location.reload();
            },
            'error': function () {
                $('#ajax_wait').hide();
                $('#core_prefs').removeAttr('submitting');
            }
        };

        $('#core_prefs').ajaxForm(options);
        $('#floater_innerwrap').tabs({selected: 0});

    });
</script>

<div id="floater_innerwrap">

    <div id="floater_handle">
        <span id="floater_title"><?php echo $kga['dict']['preferences'] ?></span>

        <div class="right">
            <a href="#" class="close" onclick="floaterClose();"><?php echo $kga['dict']['close'] ?></a>
        </div>
    </div>

    <div class="menuBackground">

        <ul class="menu tabSelection">
            <li class="tab norm"><a href="#prefGeneral">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['general'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a>
            </li>
            <li class="tab norm"><a href="#prefList">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['list'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a>
            </li>
            <li class="tab norm"><a href="#prefSublists">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['sublists'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a>
            </li>
        </ul>
    </div>

    <form id="core_prefs" action="processor.php" method="post">

        <div id="floater_tabs" class="floater_content">

            <fieldset id="prefGeneral">
                <ul>
                    <li>
                        <label for="skin"><?php echo $kga['dict']['skin'] ?>
                            :</label><?php echo $this->formSelect('skin', $kga['pref']['skin'], null, $this->skins); ?>
                    </li>

                    <li>
                        <label for="password"><?php echo $kga['dict']['newPassword'] ?>:</label>
                        <input name="password" size="15"
                               id="password"/> <?php echo $kga['dict']['minLength'] ?>
                    </li>

                    <li>
                        <label for="retypePassword"><?php echo $kga['dict']['retypePassword'] ?>:</label>
                        <input name="retypePassword" size="15" id="retypePassword"/>
                    </li>

                    <?php if (!array_key_exists('customer', $kga)) { ?>
                        <li>
                            <label for="rate"><?php echo $kga['dict']['my_rate'] ?>:</label>
                            <?php echo $this->formText('rate', str_replace('.', $kga['conf']['decimal_separator'], $this->rate), array(
                                'size' => 9)); ?>
                        </li>
                    <?php } ?>

                    <li>
                        <label for="language"><?php echo $kga['dict']['lang'] ?>:</label>
                        <?php echo $this->formSelect('language', $kga['pref']['language'], null, $this->langs); ?>
                    </li>

                    <li>
                        <label for="timezone"><?php echo $kga['dict']['timezone'] ?>:</label>
                        <?php echo $this->timeZoneSelect('timezone', $kga['pref']['timezone']); ?>
                    </li>

                    <li>
                        <label for="autoselection"></label>
                        <?php echo $this->formCheckbox('autoselection', '1', array('checked' => $kga['pref']['autoselection']));
                        echo $kga['dict']['autoselection'] ?>
                    </li>

                    <?php if (!array_key_exists('customer', $kga)) { ?>
                        <li>
                            <label for="open_after_recorded"></label>
                            <?php echo $this->formCheckbox('open_after_recorded', '1', array('checked' => isset($kga['conf']['open_after_recorded']) && $kga['conf']['open_after_recorded']));
                            echo $kga['dict']['open_after_recorded'] ?>
                        </li>
                    <?php } ?>
                    <li>
                        <label for="no_fading"></label>
                        <?php echo $this->formCheckbox('no_fading', '1', array('checked' => $kga['pref']['no_fading'])),
                        $kga['dict']['no_fading'] ?>
                    </li>
                </ul>

            </fieldset>

            <fieldset id="prefList">
                <ul>
                    <li>
                        <label for="rowlimit"><?php echo $kga['dict']['rowlimit'] ?>:</label>
                        <?php echo $this->formText('rowlimit', $kga['pref']['rowlimit'], array('size' => 9)); ?>
                    </li>
                    <?php if (!array_key_exists('customer', $kga)) { ?>
                        <li>
                            <label for="quickdelete"><?php echo $kga['dict']['quickdelete'] ?>:</label>
                            <?php
                            echo $this->formSelect(
                                'quickdelete',
                                $kga['pref']['quickdelete'],
                                null,
                                array($kga['dict']['quickdeleteHide'],
                                    $kga['dict']['quickdeleteShow'],
                                    $kga['dict']['quickdeleteShowConfirm'],
                                )
                            ); ?>
                        </li>
                    <?php } ?>
                    <li>
                        <label for="hide_cleared_entries"></label>
                        <?php echo $this->formCheckbox('hide_cleared_entries', '1', array('checked' => $kga['pref']['hide_cleared_entries'])), $kga['dict']['hide_cleared_entries'] ?>
                    </li>
                    <li>
                        <label for="show_comments_by_default"></label>
                        <?php
                        echo $this->formCheckbox(
                            'show_comments_by_default', '1',
                            array('checked' => isset($kga['conf']['show_comments_by_default'])
                                && $kga['conf']['show_comments_by_default']));
                        echo $kga['dict']['show_comments_by_default'] ?>
                    </li>
                    <li>
                        <label for="show_ref_code"></label>
                        <?php echo $this->formCheckbox('show_ref_code', '1', array('checked' => isset($kga['conf']['show_ref_code']) && $kga['conf']['show_ref_code'])), $kga['dict']['show_ref_code'] ?>
                    </li>
                    <li>
                        <label for="hide_overlap_lines"></label>
                        <?php echo $this->formCheckbox('hide_overlap_lines', '1', array('checked' => isset($kga['conf']['hide_overlap_lines']) && $kga['conf']['hide_overlap_lines'])), $kga['dict']['hide_overlap_lines'] ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="prefSublists">
                <ul>
                    <li>
                        <label for="sublist_annotations"><?php echo $kga['dict']['sublist_annotations'] ?>:</label>
                        <?php
                        echo $this->formSelect(
                            'sublist_annotations',
                            $kga['pref']['sublist_annotations'],
                            null,
                            array(
                                $kga['dict']['timelabel'],
                                $kga['dict']['export_extension']['costs'],
                                $kga['dict']['timelabel'] . ' & ' . $kga['dict']['export_extension']['costs'],
                            )); ?>
                    </li>

                    <li>
                        <label for="flip_project_display"></label>
                        <?php echo $this->formCheckbox('flip_project_display', '1', array('checked' => $kga['pref']['flip_project_display'])),
                        $kga['dict']['flip_project_display'] ?>
                    </li>
                    <li>
                        <label for="project_comment_flag"></label>
                        <?php echo $this->formCheckbox('project_comment_flag', '1', array('checked' => $kga['pref']['project_comment_flag'])),
                        $kga['dict']['project_comment_flag'] ?>
                    </li>
                    <li>
                        <label for="show_ids"></label>
                        <?php echo $this->formCheckbox('show_ids', '1', array('checked' => $kga['pref']['show_ids'])),
                        $kga['dict']['show_ids'] ?>
                    </li>
                    <li>
                        <label for="user_list_hidden"></label>
                        <?php echo $this->formCheckbox('user_list_hidden', '1', array('checked' => $kga['pref']['user_list_hidden'])),
                        $kga['dict']['user_list_hidden'] ?>
                    </li>
                </ul>
            </fieldset>

        </div>

        <input name="axAction" type="hidden" value="editPrefs"/>
        <input name="id" type="hidden" value="0"/>

        <div id="formbuttons">
            <input class='btn_norm' type='button' value='<?php echo $kga['dict']['cancel'] ?>'
                   onclick='floaterClose(); return false;'/>
            <input class='btn_ok' type='submit' value='<?php echo $kga['dict']['submit'] ?>'/>
        </div>

    </form>

</div>
