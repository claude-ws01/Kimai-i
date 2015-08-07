<?php global $kga; ?>
<form>
    <input type="text" id="newstatus" class="formfield" placeholder="<?php echo $kga['dict']['status_name_add'] ?>"/>
    <input class='btn_ok'
           type=submit
           value="<?php echo $kga['dict']['new_status'] ?>"
           onclick="adm_ext_newStatus(); return false;">
</form>
<br/>
<table>
    <thead>
    <tr class='headerrow'>
        <th><?php echo $kga['dict']['options'] ?></th>
        <th><?php echo $kga['dict']['status'] ?></th>
        <th><?php echo $kga['dict']['default'] ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (!is_array($this->arr_status) || count($this->arr_status) === 0) {
        ?>
        <tr>
            <td style="white-space: nowrap;" colspan='3'>
                <?php echo $this->error(); ?>
            </td>
        </tr>
        <?php
    }
    else {
        foreach ($this->arr_status as $statusarray) {
            ?>
            <tr class='<?php echo $this->cycle(array('odd', 'even'))->next() ?>'>

                <td class="option">
                    <a href="#"
                       onClick="adm_ext_editStatus('<?php echo $statusarray['status_id'] ?>'); $(this).blur(); return false;"><img
                            src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif"
                            title="<?php echo $kga['dict']['editstatus'] ?>"
                            width="13"
                            height="13"
                            alt="<?php echo $kga['dict']['editstatus'] ?>"
                            border="0">
                    </a>&nbsp;
                    <?php if ((int)$statusarray['timeSheetEntryCount'] === 0): ?>
                        <a href="#" onClick="adm_ext_deleteStatus(<?php echo $statusarray['status_id'] ?>)"><img
                                src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan.png"
                                title="<?php echo $kga['dict']['delete_status'] ?>"
                                width="13"
                                height="13"
                                alt="<?php echo $kga['dict']['delete_status'] ?>"
                                border="0"></a>
                    <?php else: ?>
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan_.png"
                             title="<?php echo $kga['dict']['delete_status'] ?>"
                             width="13"
                             height="13"
                             alt="<?php echo $kga['dict']['delete_status'] ?>"
                             border="0">
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo $this->escape($statusarray['status']) ?>
                </td>
                <td>
                    <?php echo $statusarray['status_id'] === $kga['conf']['default_status_id'] ? $kga['dict']['default'] : '' ?>
                </td>
            </tr>
            <?php
        }
    }
    ?>
    </tbody>
</table>
