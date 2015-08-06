<script type="text/javascript">
    $(document).ready(function () {
        $('#adm_ext_form_editGroup').ajaxForm({
            'beforeSubmit': function () {
                clearFloaterErrorMessages();

                if ($('#adm_ext_form_editGroup').attr('submitting')) {
                    return false;
                }
                else {
                    $("#ajax_wait").show();
                    $('#adm_ext_form_editGroup').attr('submitting', true);
                    return true;
                }
            },
            'success': function (result) {
                $('#adm_ext_form_editGroup').removeAttr('submitting');
                $("#ajax_wait").hide();

                for (var fieldName in result.errors)
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);

                if (result.errors.length == 0) {
                    floaterClose();
                    adm_ext_refreshSubtab('groups');
                    adm_ext_refreshSubtab('users');
                }
            },
            'error': function () {
                $('#adm_ext_form_editGroup').removeAttr('submitting');
                $("#ajax_wait").hide();
            }
        });
    });
</script>

<div id="floater_innerwrap">

    <div id="floater_handle">
        <span id="floater_title"><?php echo $GLOBALS['kga']['dict']['editGroup'] ?></span>

        <div class="right">
            <a href="#" class="close" onClick="floaterClose();"><?php echo $GLOBALS['kga']['dict']['close'] ?></a>
        </div>
    </div>

    <div class="floater_content">
        <form id="adm_ext_form_editGroup" action="../extensions/ki_admin/processor.php" method="post">
            <fieldset>
                <ul>
                    <li>
                        <label for="name"><?php echo $GLOBALS['kga']['dict']['groupname'] ?>:</label>
                        <input class="formfield"
                               type="text"
                               name="name"
                               value="<?php echo $this->escape($this->group_details['name']) ?>"
                               size=35/>
                    </li>

                </ul>
                <input name="id" type="hidden" value="<?php echo $this->group_details['group_id'] ?>"/>
                <input name="axAction" type="hidden" value="sendEditGroup"/>

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
