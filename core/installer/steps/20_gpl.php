<?php


if ($_REQUEST['language'] === 'en') {

    echo <<<EOD
                <h2>License</h2>
                <div class="gpl">
EOD;
    require('20_gpl_en.php');
    echo <<<EOD
                </div>
                <div style="width:100%;text-align:center">
                <label for="accept" style="margin-top:5px">I agree to the terms of the General Public License.</label>
                <input id="accept" type="checkbox" name="accept" value='1' onClick="gpl_agreed();" style="width:15px;height:15px;display:inline;">
                </div>
                <!--<button onClick="step_back(); return false;" class="">Back</button>-->
                <button onClick="gpl_proceed(); return false;" class="invisible proceed" style="float:right;">Proceed</button>
EOD;

}
else {


    echo <<<EOD
                <h2>Lizenz</h2>
                <div class="gpl">
EOD;
    require('20_gpl_de.php');
    echo <<<EOD
                </div>
                <div style="width:100%;text-align:center">
                <label for="accept" style="margin-top:5px">Ich stimme den Bedingungen der General Public License zu.</label>
                <input id="accept" type="checkbox" name="accept" value='1' onClick="gpl_agreed();" style="width:15px;height:15px;display:inline;">
                </div>
                <!--<button onClick="step_back(); return false;" class="">Zurück</button>-->
                <button onClick="gpl_proceed(); return false;" class="invisible proceed" style="float:right;">Fortfahren</button>
EOD;

}

