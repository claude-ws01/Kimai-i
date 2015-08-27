<?php global $kga;

$copyr_origin = isset($kga['dict']['copyr_origin']) ? $kga['dict']['copyr_origin'] :
    'Original Kimai - &copy; 2006-15 - by <a href="http://www.kimai.org" target="_blank">Kimai Team</a>';

?>
<div id="floater_innerwrap">
    <div id="floater_handle">
        <span id="floater_title"><?php echo $kga['dict']['about'] ?></span>

        <div class="right">
            <a href="#" class="close" onclick="floaterClose(); return false;"><?php echo $kga['dict']['close'] ?></a>
        </div>
    </div>
    <div class="floater_content" style="padding:0 10px 10px;">
        <h2>Kimai-i - Open Source Time Tracking</h2>
        <strong>Kimai-i <span style="color:red;"><?php echo $GLOBALS['kga']['core.status'] ?></span> <?php
            echo 'v' . $GLOBALS['kga']['core.version'], ' (db.', $GLOBALS['kga']['core.revision'] . ')'; ?>
        </strong> - &copy; <?php echo devTimeSpan(); ?> - <a href="https://github.com/claude-ws01/Kimai-i"
                                                             target="_blank">Claude Nadon</a>
        <?php echo $kga['dict']['credits'] ?>
    </div>
</div>
