<?php global $kga; ?>
<table>
    <tbody>
    <?php
    if (!is_array($this->users) || count($this->users) == 0) {
        ?>
        <tr>
            <td style="white-space: nowrap" colspan='3'>
                <?php echo $this->error(); ?>
            </td>
        </tr>
    <?php
    }
    else {
        foreach ($this->users as $user) {
            ?>
            <tr id="row_user" data-id="<?php echo $user['user_id'] ?>"
                class="<?php echo $this->cycle(array('odd', 'even'))->next() ?>">
                <!--  option cell -->
                <td style="white-space: nowrap" class="option">
                    <a href="#"
                       onclick="lists_update_filter('user',<?php echo $user['user_id'] ?>); $(this).blur(); return false;"><img
                            src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/filter.png'
                            width='13' height='13' alt='<?php echo $kga['lang']['filter'] ?>'
                            title='<?php echo $kga['lang']['filter'] ?>' border='0'/>
                    </a>
                </td>

                <!-- name cell -->
                <td style="width:100%;" class="clients">
                    <?php echo $this->escape($user['name']) ?>
                </td>

                <!-- annotation cell -->
                <td style="white-space: nowrap" class="annotation"></td>
            </tr>
        <?php
        }
    }
    ?>
    </tbody>
</table>  
