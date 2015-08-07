<?php global $kga; ?>
<form>
    <input type=text id="newGlobalRole" class="formfield" placeholder="<?php echo $kga['dict']['globalrole_name_add'] ?>">
    <input class='btn_ok' type=submit value="<?php echo $kga['dict']['addGlobalRole'] ?>"
           onclick="adm_ext_newGlobalRole(); return false;">
</form><br/>
<table>
    <thead>
    <tr class='headerrow'>
        <th><?php echo $kga['dict']['options'] ?></th>
        <th><?php echo $kga['dict']['globalRole'] ?></th>
        <th><?php echo $kga['dict']['users'] ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (!is_array($this->globalRoles) || count($this->globalRoles) === 0) {
        ?>
        <tr>
        <td style="white-space: nowrap;" colspan='3'>
            <?php echo $this->error(); ?>
        </td>
        </tr><?php
    }
    else {
        $i_edit2           = '../skins/' . $this->escape($kga['pref']['skin']) . '/grfx/edit2.gif';
        $i_button_trashcan = '../skins/' . $this->escape($kga['pref']['skin']) . '/grfx/button_trashcan.png';
        $i_button_trashcan_ = '../skins/' . $this->escape($kga['pref']['skin']) . '/grfx/button_trashcan_.png';

        foreach ($this->globalRoles as $globalRole) {?>
        <tr class='<?php echo $this->cycle(array('odd', 'even'))->next() ?>'>

            <td class="option">
                <a href="#"
                   onClick="adm_ext_editGlobalRole('<?php echo $globalRole['global_role_id'] ?>'); $(this).blur(); return false;">
                    <img src="<?php echo $i_edit2 ?>"
                         title="<?php echo $kga['dict']['editGlobalRole'] ?>" width="13" height="13"
                         alt="<?php echo $kga['dict']['editGlobalRole'] ?>" border="0">
                </a>&nbsp;
                <?php if ((int)$globalRole['count_users'] === 0): ?>
                    <a href="#"
                       onClick="adm_ext_deleteGlobalRole(<?php echo $globalRole['global_role_id'] ?>)">
                        <img
                            src="<?php echo $i_button_trashcan ?>"
                            title="<?php echo $kga['dict']['deleteGlobalRole'] ?>" width="13" height="13"
                            alt="<?php echo $kga['dict']['deleteGlobalRole'] ?>" border="0">
                    </a>
                <?php else: ?>
                    <img src="<?php echo $i_button_trashcan_ ?>"
                         title="<?php echo $kga['dict']['deleteGlobalRole'] ?>" width="13" height="13"
                         alt="<?php echo $kga['dict']['deleteGlobalRole'] ?>" border="0"><?php endif; ?>

            </td>

            <td>
                <?php echo $this->escape($globalRole['name']); ?>
            </td>

            <td><?php echo $globalRole['count_users'] ?></td>
            </tr><?php
        }
    }
    ?>
    </tbody>
</table>
