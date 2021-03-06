<script type="text/javascript">
    $(document).ready(function () {
        $('#adm_ext_form_editstatus').ajaxForm({
            'beforeSubmit': function () {
                clearFloaterErrorMessages();

                if ($('#adm_ext_form_editstatus').attr('submitting')) {
                    return false;
                }
                else {
                    $("#ajax_wait").show();
                    $('#adm_ext_form_editstatus').attr('submitting', true);
                    return true;
                }
            },
            'success': function (result) {
                $('#adm_ext_form_editstatus').removeAttr('submitting');
                $("#ajax_wait").hide();

                for (var fieldName in result.errors)
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);

                if (result.errors.length == 0) {
                    floaterClose();
                    adm_ext_refreshSubtab('status');
                }
            },
            'error': function () {
                $('#adm_ext_form_editstatus').removeAttr('submitting');
                $("#ajax_wait").hide();
            }
        });
    });
</script>

<div id="floater_innerwrap">

    <div id="floater_handle">
        <span id="floater_title"><?php echo $GLOBALS['kga']['dict']['editstatus'] ?></span>

        <div class="right">
            <a href="#" class="close" onClick="floaterClose();"><?php echo $GLOBALS['kga']['dict']['close'] ?></a>
        </div>
    </div>

    <div class="floater_content">
        <form id="adm_ext_form_editstatus" action="../extensions/ki_admin/processor.php" method="post">
            <fieldset>
                <ul>
                    <li>
                        <label for="status"><?php echo $GLOBALS['kga']['dict']['status'] ?>:</label>
                        <input class="formfield"
                               type="text"
                               name="status"
                               value="<?php echo $this->escape($this->status_details['status']) ?>"
                               size=35/>
                    </li>
                    <li>
                        <label for="default"><?php echo $GLOBALS['kga']['dict']['default'] ?>:</label>
                        <input class="formfield"
                               type="checkbox"
                               name="default"
                               value="1" <?php if ($this->status_details['status_id'] == $GLOBALS['kga']['conf']['default_status_id']) echo 'checked="checked"' ?>/>
                    </li>

                </ul>
                <input name="id" type="hidden" value="<?php echo $this->status_details['status_id'] ?>"/>
                <input name="axAction" type="hidden" value="sendEditStatus"/>

                <div id="formbuttons">
                    <input class='btn_norm'
                           type='button'
                           value='<?php echo $GLOBALS['kga']['dict']['cancel'] ?>'
                           onClick='floaterClose(); return false;'/>
                    <input class='btn_ok' type='submit' value='<?php echo $GLOBALS['kga']['dict']['submit'] ?>'/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
