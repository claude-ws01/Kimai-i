<?php global $kga; ?>
<form>
    <input type=text id="newgroup" class="formfield" placeholder="<?php echo $kga['dict']['group_name_add'] ?>">
    <input class='btn_ok'
           type=submit
           value="<?php echo $kga['dict']['addgroup'] ?>"
           onclick="adm_ext_newGroup(); return false;">
</form>
<br/>
<table>
    <thead>
    <tr class='headerrow'>
        <th><?php echo $kga['dict']['options'] ?></th>
        <th><?php echo $kga['dict']['group'] ?></th>
        <th><?php echo $kga['dict']['members'] ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (!is_array($this->groups) || count($this->groups) === 0) {
        ?>
        <tr>
            <td style="white-space: nowrap;" colspan='3'>
                <?php echo $this->error(); ?>
            </td>
        </tr>
        <?php
    }
    else {
        foreach ($this->groups as $grouparray) {
            ?>
            <tr class='<?php echo $this->cycle(array('odd', 'even'))->next() ?>'>

                <td class="option">
                    <a href="#"
                       onClick="adm_ext_editGroup('<?php echo $grouparray['group_id'] ?>'); $(this).blur(); return false;">
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif"
                             title="<?php echo $kga['dict']['editGroup'] ?>"
                             width="13"
                             height="13"
                             alt="<?php echo $kga['dict']['editGroup'] ?>"
                             border="0">
                    </a>&nbsp;
                    <?php if ((int)$grouparray['count_users'] === 0): ?>
                        <a href="#" onClick="adm_ext_deleteGroup(<?php echo $grouparray['group_id'] ?>)"><img
                                src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan.png"
                                title="<?php echo $kga['dict']['delete_group'] ?>"
                                width="13"
                                height="13"
                                alt="<?php echo $kga['dict']['delete_group'] ?>"
                                border="0"></a>
                    <?php else: ?>
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan_.png"
                             title="<?php echo $kga['dict']['delete_group'] ?>"
                             width="13"
                             height="13"
                             alt="<?php echo $kga['dict']['delete_group'] ?>"
                             border="0">
                    <?php endif; ?>

                </td>

                <td>
                    <?php if ((int)$grouparray['group_id'] === 1){ ?>
                        <span style="color:red;"><?php echo $this->escape($grouparray['name']); ?></span>
                    <?php } else { ?>
                        <?php echo $this->escape($grouparray['name']); ?>
                    <?php } ?>
                </td>

                <td><?php echo $grouparray['count_users'] ?></td>
            </tr>
            <?php
        }
    }
    ?>

    </tbody>
</table>
