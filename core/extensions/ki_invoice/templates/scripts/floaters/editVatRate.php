    <script type="text/javascript"> 
       
        $(document).ready(function() {
            var editVat = $('#inv_ext_editVat');

            editVat.ajaxForm(function() {
                floaterClose();
            });

            // prepare Options Object 
            var options = {
                beforeSubmit: function() {
                    $('#ajax_wait').show();
                },
                success:    function(response) {
                    $('#ajax_wait').hide();
                    if (response == 1) {
                      $('#defaultVat').html($('#vat').val());
                    }
                    floaterClose();
                } 
            }; 
            
            // pass options to ajaxForm 
            editVat.ajaxForm(options);

        });
        
    </script>

<div id="floater_innerwrap">

    <div id="floater_handle">
        <span id="floater_title"><?php echo $GLOBALS['kga']['lang']['vat']?></span>
        <div class="right">
            <a href="#" class="close" onClick="floaterClose();"><?php echo $GLOBALS['kga']['lang']['close']?></a>
        </div>  
    </div>

    <div class="floater_content">

        
        <form id="inv_ext_editVat" action="../extensions/ki_invoice/processor.php" method="post">
        <input name="id" type="hidden" value="0" />
        <input name="axAction" type="hidden" value="editVatRate" />
            <fieldset>   

                
                <label for="vat_rate"><?php echo $GLOBALS['kga']['lang']['vat']?></label>
                <input size="4" name="vat_rate" id="vat_rate" type="text"
                       value="<?php
                       echo $this->escape(str_replace('.',
                                                      $GLOBALS['kga']['conf']['decimal_separator'],
                                                      $GLOBALS['kga']['conf']['vat_rate'])); ?>"/> %

                <div id="formbuttons">
                    <input class='btn_norm' type='button' value='<?php echo $GLOBALS['kga']['lang']['cancel']?>' onClick='floaterClose(); return false;' />
                    <input class='btn_ok' type='submit' value='<?php echo $GLOBALS['kga']['lang']['submit'] ?>'/>

            </fieldset>
	</form>
    </div>
</div>
