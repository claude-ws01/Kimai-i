<form>
    <input type=text id="newGlobalRole" class="formfield">
    <input class='btn_ok' type=submit value="<?php echo $GLOBALS['kga']['lang']['addGlobalRole'] ?>"
           onclick="adm_ext_newGlobalRole(); return false;">
</form><br/>
<table>
    <thead>
    <tr class='headerrow'>
        <th><?php echo $GLOBALS['kga']['lang']['options'] ?></th>
        <th><?php echo $GLOBALS['kga']['lang']['globalRole'] ?></th>
        <th><?php echo $GLOBALS['kga']['lang']['users'] ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (!is_array($this->globalRoles) || count($this->globalRoles) == 0) {
        ?>
        <tr>
            <td style="white-space: nowrap" colspan='3'>
                <?php echo $this->error(); ?>
            </td>
        </tr><?php
    }
    else {
        foreach ($this->globalRoles as $globalRole) {
            ?>
            <tr class='<?php echo $this->cycle(array("odd", "even"))->next() ?>'>

                <td class="option">
                    <a href="#"
                       onClick="adm_ext_editGlobalRole('<?php echo $globalRole['global_role_id'] ?>'); $(this).blur(); return false;">
                        <img src="../skins/<?php echo $this->escape($GLOBALS['kga']['pref']['skin']) ?>/grfx/edit2.gif"
                             title="<?php echo $GLOBALS['kga']['lang']['editGlobalRole'] ?>" width="13" height="13"
                             alt="<?php echo $GLOBALS['kga']['lang']['editGlobalRole'] ?>" border="0"></a>

                    &nbsp;

                    <?php if ($globalRole['count_users'] == 0): ?>
                    <a href="#"
                       onClick="adm_ext_deleteGlobalRole(<?php echo $globalRole['global_role_id'] ?>)">
                        <img
                            src="../skins/<?php echo $this->escape($GLOBALS['kga']['pref']['skin']) ?>/grfx/button_trashcan.png"
                            title="<?php echo $GLOBALS['kga']['lang']['deleteGlobalRole'] ?>" width="13" height="13"
                            alt="<?php echo $GLOBALS['kga']['lang']['deleteGlobalRole'] ?>" border="0">
                        </a>
                    <?php else: ?>
                        <img src="../skins/<?php echo $this->escape($GLOBALS['kga']['pref']['skin']) ?>/grfx/button_trashcan_.png"
                        title="<?php echo $GLOBALS['kga']['lang']['deleteGlobalRole'] ?>" width="13" height="13"
                        alt="<?php echo $GLOBALS['kga']['lang']['deleteGlobalRole'] ?>" border="0"><?php endif; ?>

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
