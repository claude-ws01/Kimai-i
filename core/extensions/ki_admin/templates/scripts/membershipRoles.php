<?php global $kga; ?>
<div class="top">
<form>
    <input type=text
           id="newMembershipRole"
           class="formfield"
           placeholder="<?php echo $kga['dict']['membershiprole_name_add'] ?>">
    <input class='btn_ok'
           type=submit
           value="<?php echo $kga['dict']['addMembershipRole'] ?>"
           onclick="adm_ext_newMembershipRole(); return false;">
</form>
</div>
<table>
    <thead>
    <tr class='headerrow'>
        <th><?php echo $kga['dict']['options'] ?></th>
        <th><?php echo $kga['dict']['membershipRole'] ?></th>
        <th><?php echo $kga['dict']['users'] ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (!is_array($this->membershipRoles) || count($this->membershipRoles) === 0) {
        ?>
        <tr>
            <td style="white-space: nowrap;" colspan='3'>
                <?php echo $this->error(); ?>
            </td>
        </tr>
        <?php
    }
    else {
        foreach ($this->membershipRoles as $membershipRole) {
            ?>
            <tr class='<?php echo $this->cycle(array('odd', 'even'))->next() ?>'>

                <td class="option">
                    <a href="#"
                       onClick="adm_ext_editMembershipRole('<?php echo $membershipRole['membership_role_id'] ?>'); $(this).blur(); return false;">
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif"
                             title="<?php echo $kga['dict']['editMembershipRole'] ?>"
                             width="13"
                             height="13"
                             alt="<?php echo $kga['dict']['editMembershipRole'] ?>"
                             border="0"></a>
                    &nbsp;
                    <?php if ((int)$membershipRole['count_users'] === 0): ?>
                        <a href="#"
                           onClick="adm_ext_deleteMembershipRole(<?php echo $membershipRole['membership_role_id'] ?>)"><img
                                src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan.png"
                                title="<?php echo $kga['dict']['deleteMembershipRole'] ?>"
                                width="13"
                                height="13"
                                alt="<?php echo $kga['dict']['deleteMembershipRole'] ?>"
                                border="0"></a>
                    <?php else: ?>
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan_.png"
                             title="<?php echo $kga['dict']['deleteMembershipRole'] ?>"
                             width="13"
                             height="13"
                             alt="<?php echo $kga['dict']['deleteMembershipRole'] ?>"
                             border="0">
                    <?php endif; ?>

                </td>

                <td>
                    <?php echo $this->escape($membershipRole['name']); ?>
                </td>

                <td><?php echo $membershipRole['count_users'] ?></td>
            </tr>
            <?php
        }
    }
    ?>

    </tbody>
</table>
