<form>
    <input type=text id="newgroup" class="formfield">
    <input class='btn_ok' type=submit value="<?php echo $GLOBALS['kga']['lang']['addgroup']?>" onclick="adm_ext_newGroup(); return false;">
</form>
<br />
<table>
    <thead>
        <tr class='headerrow'>
            <th><?php echo $GLOBALS['kga']['lang']['options']?></th>
            <th><?php echo $GLOBALS['kga']['lang']['group']?></th>
            <th><?php echo $GLOBALS['kga']['lang']['members']?></th>
        </tr>
    </thead>
    <tbody>
    <?php
    if (!is_array($this->groups) || count($this->groups) == 0)
    {
        ?>
    <tr>
        <td style="white-space: nowrap" colspan='3'>
            <?php echo $this->error(); ?>
        </td>
    </tr>
        <?php
    }
    else
    {
        foreach ($this->groups as $grouparray)
        {
            ?>
            <tr class='<?php echo $this->cycle(array("odd","even"))->next()?>'>

                <td class="option">
                    <a href="#" onClick="adm_ext_editGroup('<?php echo $grouparray['group_id']?>'); $(this).blur(); return false;">
                        <img src="../skins/<?php echo $this->escape($GLOBALS['kga']['pref']['skin'])?>/grfx/edit2.gif" title="<?php echo $GLOBALS['kga']['lang']['editGroup']?>" width="13" height="13" alt="<?php echo $GLOBALS['kga']['lang']['editGroup']?>" border="0"></a>

                    &nbsp;

                    <?php if ($grouparray['count_users'] == 0): ?>
                        <a href="#" onClick="adm_ext_deleteGroup(<?php echo $grouparray['group_id']?>)"><img
                                src="../skins/<?php echo $this->escape($GLOBALS['kga']['pref']['skin'])?>/grfx/button_trashcan.png"
                                title="<?php echo $GLOBALS['kga']['lang']['delete_group']?>" width="13" height="13" alt="<?php echo $GLOBALS['kga']['lang']['delete_group']?>" border="0"></a>
                    <?php else: ?>
                        <img src="../skins/<?php echo $this->escape($GLOBALS['kga']['pref']['skin'])?>/grfx/button_trashcan_.png" title="<?php echo $GLOBALS['kga']['lang']['delete_group']?>" width="13" height="13" alt="<?php echo $GLOBALS['kga']['lang']['delete_group']?>" border="0">
                    <?php endif; ?>

                </td>

                <td>
                    <?php if ($grouparray['group_id'] == 1): ?>
                        <span style="color:red"><?php echo $this->escape($grouparray['name']); ?></span>
                    <?php else: ?>
                        <?php echo $this->escape($grouparray['name']); ?>
                    <?php endif; ?>
                </td>

                <td><?php echo $grouparray['count_users']?></td>
            </tr>
            <?php
        }
    }
?>

</tbody>
</table>
