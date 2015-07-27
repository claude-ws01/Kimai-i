<!-- 
    YOU ARE NOT ALLOWED TO REMOVE THE COPYRIGHT NOTES! YOU MAY CHANGE THE APPEARANCE OF THE LOGIN WINDOW
    BUT REMOVING THE CREDITS IS STRICTLY PROHIBITED. If you feel uncomfortable with this rule - use
    other time tracking software, please.
-->
<?php
global $kga;

$copyr_origin = isset($kga['lang']['copyr_origin']) ? $kga['lang']['copyr_origin'] :
    'Original Kimai - &copy; 2006-15 - <a href="http://www.kimai.org" target="_blank">Kimai Team</a>';

$copyr_provided = isset($kga['lang']['copyr_provided']) ? $kga['lang']['copyr_provided'] :
    'Software provided under the terms and conditions
         <br/>of the <a href="COPYING" target="_blank">
            General Public License GNU v3</a>';

$copyr_powerby = isset($kga['lang']['copyr_poweredby']) ? $kga['lang']['copyr_poweredby'] : ' Powered by ';
?>
<p>
    <strong>Kimai-i <span style="color:red"><?php echo $GLOBALS['kga']['core.status'] ?></span> <?php
        echo 'v' . $GLOBALS['kga']['core.version'], ' (db.', $GLOBALS['kga']['core.revision'] . ')'; ?>
    </strong> - &copy; <?php echo devTimeSpan(); ?> - <a href="https://github.com/cloudeasy/Kimai-i"
                                                         target="_blank">Claude Nadon</a>

    <br/><?php echo $copyr_origin; ?>
    <br/><?php echo $copyr_provided; ?>
    <br/><?php echo $copyr_powerby, ' '; ?>
    <a href="http://www.jquery.com" target="_blank">jQuery</a> |
    <a href="http://php.net/" target="_blank">PHP</a> |
    <a href="http://mysql.com/" target="_blank">MySQL</a>
</p>
