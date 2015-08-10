<?php
global $kga, $extensions;
?>
<script type="text/javascript">
    $(document).ready(function () {
        deb_ext_onload();
        deb_ext_onblur();
    });
</script>

<div id="deb_ext_kga_header">
    <a href="#"
       title="Clear"
       style="float:left;padding-right:5px;"
       onclick="deb_ext_reloadKGA();return false;"><img src="/grfx/action_refresh.png"
                                                        alt="Reload KGA"></a>
    KIMAI GLOBAL ARRAY ($kga)
</div>

<div id="deb_ext_kga_wrap">
    <div id="deb_ext_kga">
        <pre>
<?php echo $this->kga_display; ?>
        </pre>
    </div>
</div>

<div id="deb_ext_logfile_header">
    <?php if ($kga['delete_logfile']): ?>
        <div id="deb_ext_buttons">
            <a href="#"
               title="Clear"
               onclick="deb_ext_clearLogfile();return false;">
                <img src="../skins/<?php echo $kga['pref']['skin'] ?>/grfx/button_trashcan.png"
                     width="13"
                     height="13"
                     alt="Clear"></a>
        </div>
    <?php endif; ?>
    DEBUG LOGFILE <?php echo $this->limitText ?>

    <form id="deb_ext_shoutbox" action="../extensions/ki_debug/processor.php" method="post">
        <input type="text" id="deb_ext_shoutbox_field" name="axValue" value="shoutbox"/>
        <input name="id" type="hidden" value="0"/>
        <input name="axAction" type="hidden" value="shoutbox"/>
    </form>

</div>

<div id="deb_ext_logfile_wrap">
    <div id="deb_ext_logfile"></div>
</div>
