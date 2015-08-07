<div id="floater_innerwrap">
    <div id="floater_handle">
        <span id="floater_title"><?php echo $GLOBALS['kga']['dict']['securityWarning'] ?></span>

        <div class="right">
            <a href="#"
               class="close"
               onclick="floaterClose(); return false;"><?php echo $GLOBALS['kga']['dict']['close'] ?></a>
        </div>
    </div>
    <div class="floater_content" style="padding:20px;">
        <h2 style="color:red;"><?php echo $GLOBALS['kga']['dict']['securityWarning'] ?></h2>
        <b><?php echo $GLOBALS['kga']['dict']['installerWarningHeadline'] ?></b> <br/><br/>
        <?php echo $GLOBALS['kga']['dict']['installerWarningText'] ?>
    </div>
</div>
