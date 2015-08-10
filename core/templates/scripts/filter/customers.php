<?php
// remove hidden entries from list
global $kga;
$customers = $this->filterListEntries($this->customers);
?>
<table>
    <tbody>
    <?php
    if (!is_array($customers) || count($customers) === 0) {
        ?>
        <tr>
            <td style="white-space: nowrap;" colspan='3'>
                <?php echo $this->error(); ?>
            </td>
        </tr>
        <?php
    }
    else {
        foreach ($customers as $customer) {
            ?>

            <tr id="row_customer" data-id="<?php echo $customer['customer_id'] ?>"
                class="customer customer<?php echo $customer['customer_id'] ?> <?php echo $this->cycle(array('odd', 'even'))->next() ?>">

                <!-- option cell -->
                <td style="white-space: nowrap;" class="option">
                    <?php if ($this->show_customer_edit_button): ?>
                        <a href="#"
                           onclick="editObject('customer',<?php echo $customer['customer_id'] ?>); $(this).blur(); return false;">
                            <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif'
                                 width='13' height='13' alt='<?php echo $kga['dict']['edit'] ?>'
                                 title='<?php echo $kga['dict']['edit'] ?>' border='0'/>
                        </a>
                    <?php endif; ?>
                    <a href="#"
                       onclick="lists_update_filter('customer',<?php echo $customer['customer_id'] ?>); $(this).blur(); return false;">
                        <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/filter.png'
                             width='13' height='13' alt='<?php echo $kga['dict']['filter'] ?>'
                             title='<?php echo $kga['dict']['filter'] ?>' border='0'/>
                    </a>
                </td>

                <!-- name cell -->
                <td style="width:100%;" class="clients" onmouseover="lists_change_color(this,true);"
                    onmouseout="lists_change_color(this,false);"
                    onclick="lists_customer_highlight(<?php echo $customer['customer_id'] ?>); $(this).blur(); return false;">
                    <?php if ((int)$customer['visible'] !== 1): ?><span style="color:#bbb;"><?php endif; ?>
                        <?php if ((int)$kga['pref']['show_ids'] === 1): ?><span
                            class="ids"><?php echo $customer['customer_id'] ?></span> <?php endif;
                        echo $this->escape($customer['name']) ?>
                        <?php if ((int)$customer['visible'] !== 1): ?></span><?php endif; ?>
                </td>

                <!-- annotation cell -->
                <td style="white-space: nowrap;" class="annotation"></td>
            </tr>
            <?php
        }
    }
    ?>
    </tbody>
</table>  
