<?php global $kga ?>
<a href="#" onClick="floaterShow('floaters.php','add_edit_customer',0,0,450); $(this).blur(); return false;"><img
        src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/add.png" width="22" height="16"
        alt="<?php echo $kga['dict']['new_customer'] ?>"></a>&nbsp;<?php echo $kga['dict']['new_customer'] ?>
<br/><br/>

<table>

    <thead>
    <tr class='headerrow'>
        <th><?php echo $kga['dict']['options'] ?></th>
        <th><?php echo $kga['dict']['customers'] ?></th>
        <th><?php echo $kga['dict']['contactPerson'] ?></th>
        <th><?php echo $kga['dict']['groups'] ?></th>
    </tr>
    </thead>

    <tbody>
    <?php
    if (!is_array($this->customers) || count($this->customers) == 0) {
        ?>
        <tr>
            <td style="white-space: nowrap" colspan='3'>
                <?php echo $this->error(); ?>
            </td>
        </tr><?php
    }
    else {
        foreach ($this->customers as $row) {
            ?>
            <tr class="<?php echo $this->cycle(array("odd", "even"))->next() ?>">

                <td class="option">
                    <a href="#"
                       onClick="editSubject('customer',<?php echo $row['customer_id'] ?>); $(this).blur(); return false;"><img
                            src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif'
                            width='13' height='13' alt='<?php echo $kga['dict']['tip']['g_edit'] ?>'
                            title='<?php echo $kga['dict']['tip']['g_edit'] ?>' border='0'/></a>

                    &nbsp;

                    <a href="#" id="delete_customer<?php echo $row['customer_id'] ?>"
                       onClick="adm_ext_deleteCustomer(<?php echo $row['customer_id'] ?>)"><img
                            src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan.png"
                            title="<?php echo $kga['dict']['delete_customer'] ?>" width="13" height="13"
                            alt="<?php echo $kga['dict']['delete_customer'] ?>" border="0"></a>
                </td>

                <td class="clients">
                    <?php if ($row['visible'] != 1): ?><span
                        style="color:#bbb"><?php endif; ?><?php echo $this->escape($row['name']); ?><?php if ($row['visible'] != 1): ?></span><?php endif; ?>
                </td>

                <td>
                    <?php echo $this->escape($row['contact']); ?>
                </td>

                <td>
                    <?php echo $this->escape($row['groups']) ?>
                </td>

            </tr><?php
        }
    }
    ?>
    </tbody>
</table>
