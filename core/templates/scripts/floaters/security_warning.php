<div id="floater_innerwrap">
    <div id="floater_handle">
        <span id="floater_title"><?php echo $GLOBALS['kga']['lang']['securityWarning']?></span>
        <div class="right">
            <a href="#" class="close" onclick="floaterClose(); return false;"><?php echo $GLOBALS['kga']['lang']['close']?></a>
        </div>       
    </div>
    <div class="floater_content" style="padding:20px">
        <h2 style="color:red"><?php echo $GLOBALS['kga']['lang']['securityWarning']?></h2>
        <b><?php echo $GLOBALS['kga']['lang']['installerWarningHeadline']?></b> <br/><br/>
        <?php echo $GLOBALS['kga']['lang']['installerWarningText']?>
    </div>
</div>
