<?php global $kga ?>
<script type="text/javascript">

    $(document).ready(function () {
        $('#help').hide();

        $('#edit_day').datepicker();

        $('#xpe_ext_form_add_edit_record').ajaxForm({
            'beforeSubmit': function () {
                clearFloaterErrorMessages();

                if ($('#xpe_ext_form_add_edit_record').attr('submitting')) {
                    return false;
                }
                else {
                    $("#ajax_wait").show();
                    $('#xpe_ext_form_add_edit_record').attr('submitting', true);
                    return true;
                }
            },
            'success': function (result) {
                $('#xpe_ext_form_add_edit_record').removeAttr('submitting');
                $("#ajax_wait").hide();

                for (var fieldName in result.errors)
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);


                if (result.errors.length == 0) {
                    floaterClose();
                    xpe_ext_reload();
                }
            },
            'error': function () {
                $('#xpe_ext_form_add_edit_record').removeAttr('submitting');
                $("#ajax_wait").hide();
            }
        });

        <?php if (!isset($this->id)): ?>
        $("#add_edit_expense_project_ID").val(selected_project);
        <?php endif; ?>
        $('#floater_innerwrap').tabs({selected: 0});
    });

</script>

<div id="floater_innerwrap">

    <div id="floater_handle">
        <span
            id="floater_title"><?php echo !isset($this->id) ? $kga['lang']['add'] : $kga['lang']['edit'] ?></span>

        <div class="right">
            <a href="#" class="close" onClick="floaterClose();"><?php echo $kga['lang']['close'] ?></a>
            <a href="#" class="help"
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
        </ul>
    </div>

    <form id="xpe_ext_form_add_edit_record" action="../extensions/ki_expenses/processor.php" method="post">
        <input name="id" type="hidden" value="<?php echo $this->id ?>"/>
        <input name="axAction" type="hidden" value="add_edit_record"/>

        <div id="floater_tabs" class="floater_content" style="padding-bottom: 0;">
            <fieldset id="general">
                <ul>
                    <li>
                        <label for="project_id"><?php echo $kga['lang']['project'] ?>:</label>

                        <div class="multiFields">
                            <?php
                            echo $this->formSelect('project_id', $this->selected_project, array(
                                'id'       => 'add_edit_expense_project_ID',
                                'class'    => 'formfield',
                                'size'     => '5',
                                'style'    => 'width:400px',
                                'tabindex' => '1',
                            ), $this->projects);
                            ?><br/>
                            <input type="text" style="width:395px;margin-top:3px" tabindex="2" size="10" name="filter"
                                   placeholder="<?php echo $kga['lang']['searchFilter']; ?>"
                                   id="filter" onkeyup="filter_selects('add_edit_expense_project_ID', this.value);"/>
                        </div>
                    </li>

                    <li>
                        <label for="edit_day"><?php echo $kga['lang']['day'] ?>:</label>
                        <input id='edit_day' type='text' name='edit_day' style="text-align:center;"
                               value='<?php echo $this->escape($this->edit_day) ?>' maxlength='10' size='8'
                               tabindex='5' <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();" <?php endif; ?> />
                    </li>

                    <li>
                        <label for="edit_time"><?php echo $kga['lang']['timelabel'] ?>:</label>
                        <input id='edit_time' type='text' name='edit_time' style="text-align:center;"
                               value='<?php echo $this->escape($this->edit_time) ?>' maxlength='5' size='3'
                               tabindex='7' <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();" <?php endif; ?> />
                        <a href="#"
                           onClick="xpe_ext_pasteNow(); $(this).blur(); return false;"><?php echo $kga['lang']['now'] ?></a>
                    </li>

                    <li>
                        <label for="multiplier"><?php echo $kga['lang']['multiplier'] ?>:</label>
                        <input id='multiplier' type='text' name='multiplier' style="text-align:center;"
                               value='<?php echo $this->escape($this->multiplier) ?>' maxlength='5' size='3'
                               tabindex='9' <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();" <?php endif; ?> />
                    </li>

                    <li>
                        <label for="edit_value"><?php echo $kga['lang']['expense'] ?>:</label>
                        <input id='edit_value' type='text' name='edit_value' style="text-align:right;padding-right:5px;"
                               value='<?php echo $this->escape($this->edit_value) ?>' maxlength='10' size='4'
                               tabindex='10' <?php if ($kga['pref']['autoselection']): ?> onClick="this.select();" <?php endif; ?> />
                    </li>

                    <li style="padding-bottom:0;border:none;">
                        <label for="description"><?php echo $kga['lang']['description'] ?>:</label>
                        <textarea id='description'
                                  style="max-width:420px; width:395px"
                                  class='comment'
                                  name='description'
                                  cols='40'
                                  rows='5'
                                  tabindex='11'><?php echo $this->escape($this->description) ?></textarea>

                    </li>
                </ul>
            </fieldset>

            <fieldset id="extended">
                <ul>
                    <li>
                        <label for="erase"><?php echo $kga['lang']['refundable_long'] ?>:</label>
                        <input type='checkbox' id='refundable'
                               name='refundable' <?php if ($this->refundable): ?> checked="checked" <?php endif; ?>
                               tabindex='12'/>
                    </li>

                    <li>
                        <label for="comment"><?php echo $kga['lang']['comment'] ?>:</label>
                        <textarea id='comment'
                                  style="max-width:420px; width:395px"
                                  class='comment'
                                  name='comment'
                                  cols='40'
                                  rows='5'
                                  tabindex='13'><?php echo $this->escape($this->comment) ?></textarea>
                    </li>

                    <li>
                        <label for="comment_type"><?php echo $kga['lang']['comment_type'] ?>
                            :</label><?php echo $this->formSelect('comment_type', $this->comment_type, array(
                            'id'       => 'comment_type',
                            'class'    => 'formfield',
                            'tabindex' => '14'), $this->commentTypes); ?>
                    </li>

                    <li>
                        <label for="erase"><?php echo $kga['lang']['erase'] ?>:</label>
                        <input type='checkbox' id='erase' name='erase' tabindex='15'/>
                    </li>

                </ul>
            </fieldset>
        </div>
        <div id="formbuttons">
            <input class='btn_norm' type='button' value='<?php echo $kga['lang']['cancel'] ?>'
                   onClick='floaterClose(); return false;'/>
            <input class='btn_ok' type='submit' value='<?php echo $kga['lang']['submit'] ?>'/>
        </div>
    </form>
</div>
