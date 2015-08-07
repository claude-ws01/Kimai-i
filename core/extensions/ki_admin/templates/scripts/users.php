<?php global $kga; ?>
<form>
    <input type="text" id="newuser" class="formfield" placeholder="<?php echo $kga['dict']['user_name_add'] ?>"/>
    <input class='btn_ok'
           type="submit"
           value="<?php echo $kga['dict']['adduser'] ?>"
           onclick="adm_ext_newUser(); return false;">
    <?php if ($this->showDeletedUsers): ?>
        <input class='btn_ok'
               type="button"
               value="<?php echo $kga['dict']['hidedeletedusers'] ?>"
               onclick="adm_ext_hideDeletedUsers(); return false;">
    <?php else: ?>
        <input class='btn_ok'
               type="button"
               value="<?php echo $kga['dict']['showdeletedusers'] ?>"
               onclick="adm_ext_showDeletedUsers(); return false;">
    <?php endif; ?>
</form>

<br/>

<table>

    <thead>
    <tr>
        <th><?php echo $kga['dict']['options'] ?></th>
        <th><?php echo $kga['dict']['username'] ?></th>
        <th><?php echo $kga['dict']['status'] ?></th>
        <th><?php echo $kga['dict']['group'] ?></th>
    </tr>
    </thead>

    <tbody>
    <?php
    if (!is_array($this->users) || count($this->users) === 0) {
        ?>
        <tr>
            <td style="white-space: nowrap;" colspan='4'>
                <?php echo $this->error(); ?>
            </td>
        </tr>
        <?php
    }
    else {
        foreach ($this->users as $userarray) {
            ?>
            <tr class='<?php echo $this->cycle(array('odd', 'even'))->next() ?>'>

                <!-- ########## Option cells ########## -->
                <td class="option">
                    <a href="#"
                       onClick="adm_ext_editUser('<?php echo $userarray['user_id'] ?>'); $(this).blur(); return false;">
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif"
                             title="<?php echo $kga['dict']['editUser'] ?>"
                             width="13"
                             height="13"
                             alt="<?php echo $kga['dict']['editUser'] ?>"
                             border="0"></a>

                    &nbsp;

                    <?php if ($userarray['mail']): ?>
                        <a href="mailto:<?php echo $this->escape($userarray['mail']); ?>"><img
                                src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_mail.gif"
                                title="<?php echo $kga['dict']['mailUser'] ?>"
                                width="12"
                                height="13"
                                alt="<?php echo $kga['dict']['mailUser'] ?>"
                                border="0"></a>
                    <?php else: ?>
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_mail_.gif"
                             title="<?php echo $kga['dict']['mailUser'] ?>"
                             width="12"
                             height="13"
                             alt="<?php echo $kga['dict']['mailUser'] ?>"
                             border="0">
                    <?php endif; ?>

                    &nbsp;

                    <?php if ($this->curr_user !== $userarray['name']) { ?>
                        <a href="#"
                           id="deleteUser<?php echo $userarray['user_id'] ?>"
                           onClick="adm_ext_deleteUser(<?php echo $userarray['user_id'] ?>, <?php echo($userarray['trash'] ? 'false' : 'true'); ?>)"><img
                                src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan.png"
                                title="<?php echo $kga['dict']['deleteUser'] ?>"
                                width="13"
                                height="13"
                                alt="<?php echo $kga['dict']['deleteUser'] ?>"
                                border="0"></a>
                    <?php }
                    else { ?>
                        <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan_.png"
                             title="<?php echo $kga['dict']['deleteUser'] ?>"
                             width="13"
                             height="13"
                             alt="<?php echo $kga['dict']['deleteUser'] ?>"
                             border="0">
                    <?php } ?>
                </td>
                <!-- ########## /Option cells ########## -->

                <!-- ########## USER NAME ########## -->
                <td>
                    <?php if ($this->curr_user === $userarray['name']): ?>
                        <strong style="color:#00E600;"><?php echo $this->escape($userarray['name']) ?></strong>
                    <?php else: ?>
                        <?php if ($userarray['trash']): ?><span style="color:#999;"><?php endif; ?>
                        <?php echo $this->escape($userarray['name']); ?>
                        <?php if ($userarray['trash']): ?></span><?php endif; ?>
                    <?php endif; ?>
                </td>
                <!-- ########## /USER NAME ########## -->

                <td>
                    <?php if ((int)$userarray['active'] === 1): ?>
                        <?php if ($this->curr_user !== $userarray['name']): ?>
                            <a href="#"
                               id="ban<?php echo $userarray['user_id'] ?>"
                               onClick="adm_ext_banUser('<?php echo $userarray['user_id'] ?>'); return false;">
                                <img src='../grfx/jipp.gif'
                                     alt='<?php echo $kga['dict']['activeAccount'] ?>'
                                     title='<?php echo $kga['dict']['activeAccount'] ?>'
                                     border="0"
                                     width="16"
                                     height="16"/></a>
                        <?php else: ?>
                            <img src='../grfx/jipp_.gif'
                                 alt='<?php echo $kga['dict']['activeAccount'] ?>'
                                 title='<?php echo $kga['dict']['activeAccount'] ?>'
                                 border="0"
                                 width="16"
                                 height="16"/>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ((int)$userarray['active'] === 0): ?>
                        <a href="#"
                           id="ban<?php echo $userarray['user_id'] ?>"
                           onClick="adm_ext_unbanUser('<?php echo $userarray['user_id'] ?>'); return false;">
                            <img src='../grfx/lock.png'
                                 alt='<?php echo $kga['dict']['bannedUser'] ?>'
                                 title='<?php echo $kga['dict']['bannedUser'] ?>'
                                 border="0"
                                 width="16"
                                 height="16"/></a>
                    <?php endif; ?>

                    &nbsp;

                    <?php if ($userarray['passwordSet'] === 'no'): ?>
                        <a href="#"
                           onClick="adm_ext_editUser('<?php echo $userarray['user_id'] ?>'); $(this).blur(); return false;">
                            <img src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/caution_mini.png"
                                 width="16"
                                 height="16"
                                 title='<?php echo $kga['dict']['nopasswordset'] ?>'
                                 border="0"></a>
                    <?php endif; ?>

                    &nbsp;

                    <?php if ($userarray['trash']): ?>
                        <strong style="color:red;">X</strong>
                    <?php endif; ?>
                </td>
                <!-- ########## /Status cells ########## -->

                <!-- ########## Group cells ########## -->
                <td>
                    <?php echo implode(', ', $userarray['groups']); ?>
                </td>
                <!-- ########## Group cells ########## -->
            </tr>
            <?php
        }
    }
    ?>
    </tbody>
</table>

<p>
    <strong><?php echo $kga['dict']['hint'] ?></strong> <?php echo $kga['dict']['rename_caution_before_username'] ?> '<?php echo $this->escape($this->curr_user) ?>' <?php echo $kga['dict']['rename_caution_after_username'] ?>
</p>
