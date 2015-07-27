<?php
// remove hidden entries from list
global $kga;
$activities = $this->filterListEntries($this->activities);
?>
<table>
    <tbody>
    <?php
    if (!is_array($activities) || count($activities) == 0) {
        ?>
        <tr>
            <td style="white-space: nowrap" colspan='3'>
                <?php echo $this->error(); ?>
            </td>
        </tr>
    <?php
    }
    else {
        foreach ($activities as $activity) {
            ?>
            <tr id="row_activity" data-id="<?php echo $activity['activity_id'] ?>"
                class="<?php echo $this->cycle(array('odd', 'even'))->next() ?>">

                <td style="white-space: nowrap" class="option">
                    <?php if ($this->show_activity_edit_button): ?>
                        <a href="#"
                           onclick="editSubject('activity',<?php echo $activity['activity_id'] ?>); $(this).blur(); return false;">
                            <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif'
                                 width='13' height='13' alt='<?php echo $kga['lang']['edit'] ?>'
                                 title='<?php echo $kga['lang']['edit'] ?> (ID:<?php echo $activity['activity_id'] ?>)'
                                 border='0'/>
                        </a>
                    <?php endif; ?>

                    <a href="#"
                       onclick="lists_update_filter('activity',<?php echo $activity['activity_id'] ?>); $(this).blur(); return false;">
                        <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/filter.png'
                             width='13' height='13' alt='<?php echo $kga['lang']['filter'] ?>'
                             title='<?php echo $kga['lang']['filter'] ?>' border='0'/>
                    </a>
                    <?php if (isset($kga['user'])) { ?>
                        <a href="#" class="preselect"
                           onclick="buzzer_preselect_activity(<?php echo $activity['activity_id'] ?>,'<?php echo $this->jsEscape($activity['name']) ?>'); return false;"
                           id="ps<?php echo $activity['activity_id'] ?>">
                            <img
                                src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/preselect_off.png'
                                width='13' height='13' alt='<?php echo $kga['lang']['select'] ?>'
                                title='<?php echo $kga['lang']['select'] ?> (ID:<?php echo $activity['activity_id'] ?>)'
                                border='0'/>
                        </a>
                    <?php } ?>
                </td>

                <td style="width:100%;" class="activities"
                    onclick="buzzer_preselect_activity(<?php echo $activity['activity_id'] ?>,'<?php echo $this->jsEscape($activity['name']) ?>'); return false;"
                    onmouseover="lists_change_color(this,true);" onmouseout="lists_change_color(this,false);">
                    <?php if ($activity['visible'] != 1): ?><span style="color:#bbb"><?php endif; ?>
                    <?php if ($kga['pref']['show_ids'] == 1): ?>
                        <span class="ids"><?php echo $activity['activity_id'] ?></span>
                    <?php endif;
                    echo $this->escape($activity['name']) ?>
                    <?php if ($activity['visible'] != 1): ?></span><?php endif; ?>
                </td>

                <td style="white-space: nowrap" class="annotation"></td>
            </tr>
        <?php
        }
    }
    ?>
    </tbody>
</table>  
