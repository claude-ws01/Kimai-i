    <script type="text/javascript"> 
        $(document).ready(function() {
            $('#adm_ext_form_editstatus').ajaxForm( {
              'beforeSubmit' :function() { 
                clearFloaterErrorMessages();

                if ($('#adm_ext_form_editstatus').attr('submitting')) {
                  return false;
                }
                else {
                  $('#adm_ext_form_editstatus').attr('submitting', true);
                  return true;
                }
              },
              'success': function (result) {
                $('#adm_ext_form_editstatus').removeAttr('submitting');

                for (var fieldName in result.errors)
                  setFloaterErrorMessage(fieldName,result.errors[fieldName]);
                
                if (result.errors.length == 0) {
                  floaterClose();
                  adm_ext_refreshSubtab('status');
                }
            },
            'error': function() {
              $('#adm_ext_form_editstatus').removeAttr('submitting');
            }});
        }); 
    </script>

<div id="floater_innerwrap">
    
    <div id="floater_handle">
        <span id="floater_title"><?php echo $GLOBALS['kga']['lang']['editstatus']?></span>
        <div class="right">
            <a href="#" class="close" onClick="floaterClose();"><?php echo $GLOBALS['kga']['lang']['close']?></a>
        </div>       
    </div>

    <div class="floater_content">
        <form id="adm_ext_form_editstatus" action="../extensions/ki_adminpanel/processor.php" method="post">
            <fieldset>
                <ul>
                    <li>
                        <label for="status"><?php echo $GLOBALS['kga']['lang']['status']?>:</label>
                        <input class="formfield" type="text" name="status" value="<?php echo $this->escape($this->status_details['status'])?>" size=35 />
                    </li>
                    <li>
                        <label for="default"><?php echo $GLOBALS['kga']['lang']['default']?>:</label>
                        <input class="formfield" type="checkbox" name="default" value="1" <?php if($this->status_details['status_id'] == $GLOBALS['kga']['conf']['defaultStatusID']) echo 'checked="checked"'?>/>
                    </li>
                                                
                </ul>
                <input name="id" type="hidden" value="<?php echo $this->status_details['status_id']?>" />
                <input name="axAction" type="hidden" value="sendEditStatus" />
                <div id="formbuttons">
                    <input class='btn_norm' type='button' value='<?php echo $GLOBALS['kga']['lang']['cancel']?>' onClick='floaterClose(); return false;' />
                    <input class='btn_ok' type='submit' value='<?php echo $GLOBALS['kga']['lang']['submit']?>' />
                </div>
            </fieldset>
        </form>
    </div>
</div>
