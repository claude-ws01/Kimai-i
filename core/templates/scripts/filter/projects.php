<?php
// remove hidden entries from list
global $kga;
$projects = $this->filterListEntries($this->projects);
?>
<table>
    <tbody>
    <?php
    if (!is_array($projects) || count($projects) === 0) {
        ?>
        <tr>
        <td style="white-space: nowrap;" colspan='3'>
            <?php echo $this->error(); ?>
        </td>
        </tr><?php
    }
    else {
        foreach ($projects as $project) {
            ?>
        <tr id="row_project" data-id="<?php echo $project['project_id'] ?>"
            class="project customer<?php echo $project['customer_id'] ?> <?php echo $this->cycle(array('odd', 'even'))->next() ?>">
            <td style="white-space: nowrap;" class="option">

                <?php if ($this->show_project_edit_button) { ?>
                    <a href="#"
                       onclick="editObject('project',<?php echo $project['project_id'] ?>); $(this).blur(); return false;">
                        <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif'
                             width='13' height='13' alt='<?php echo $kga['dict']['edit'] ?>'
                             title='<?php echo $kga['dict']['edit'] ?> (ID:<?php echo $project['project_id'] ?>)'
                             border='0'/>
                    </a>
                <?php } ?>

                <a href="#"
                   onclick="lists_update_filter('project',<?php echo $project['project_id'] ?>); $(this).blur(); return false;">
                    <img src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/filter.png'
                         width='13' height='13' alt='<?php echo $kga['dict']['filter'] ?>'
                         title='<?php echo $kga['dict']['filter'] ?>' border='0'/>
                </a>

                <?php if (is_user()) { ?>
                    <a href="#" class="preselect" title="<?php echo $kga['dict']['tip']['g_select_for_recording']; ?>"
                       onclick="buzzer_preselect_project(<?php
                       echo($project['project_id'] . ', \'' . $this->jsEscape($project['name']) . '\','
                           . $project['customer_id'] . ', \'' . $this->jsEscape($project['customer_name']) . "'"); ?>); return false;"
                       id="ps<?php echo $project['project_id'] ?>">
                        <img src='../grfx/preselect_off.png'
                             width='13' height='13' alt='<?php echo $kga['dict']['tip']['g_select_for_recording'] ?>'
                             title='<?php echo $kga['dict']['tip']['g_select_for_recording'] ?> (ID:<?php echo $project['project_id'] ?>)'
                             border='0'/>
                    </a>
                <?php } ?>
            </td>
            <td style="width:100%;" class="projects" onmouseover="lists_change_color(this,true);"
                title="<?php echo $kga['dict']['tip']['g_select_for_recording']; ?>"
                onmouseout="lists_change_color(this,false);"
                onclick="buzzer_preselect_project(<?php
                echo($project['project_id'] . ', \'' . $this->jsEscape($project['name']) . '\','
                    . $project['customer_id'] . ', \'' . $this->jsEscape($project['customer_name']) . "'"); ?>);
                    lists_reload('activity'); return false;">
                <?php if ((int)$project['visible'] !== 1) { ?><span style="color:#bbb;"><?php } ?>

                    <?php if ($kga['pref']['flip_project_display']) { ?>
                        <?php if ((int)$kga['pref']['show_ids'] === 1) { ?>
                            <span class="ids"><?php echo $project['project_id'] ?></span>
                        <?php } ?>
                            <span class="lighter">
                                <?php echo $this->escape($this->truncate($project['customer_name'], 30, '...')) ?>:
                            </span>
                        <?php echo $this->escape($project['name']) ?>

                    <?php }
                    else {
                        if ((int)$kga['pref']['project_comment_flag'] === 1) {
                            if ((int)$kga['pref']['show_ids'] === 1) { ?>
                                <span class="ids">
                                        <?php echo $project['project_id'] ?>
                                    </span>
                            <?php } ?>
                                <?php echo $this->escape($project['name']) ?>
                                <span class="lighter">
                                    <?php if ($project['comment']) {
                                        echo '(' . $this->escape($this->truncate($project['comment'], 30, '...')) . ')';
                                    }
                                    else { ?>
                                        <span class="lighter"><?php echo '(' . $this->escape($project['customer_name']) . ')'; ?></span>
                                    <?php } ?>
                                </span>
                        <?php }
                        else { ?>
                            <?php if ((int)$kga['pref']['show_ids'] === 1) { ?>
                                <span class="ids"><?php echo $project['project_id'] ?></span>
                            <?php }
                            echo $this->escape($project['name']) ?>
                                <span class="lighter">
                                    <?php echo '(' . $this->escape($this->truncate($project['customer_name'], 30, '...')) . ')'; ?>
                                </span>
                        <?php } ?>
                    <?php } ?>
                    <?php if ((int)$project['visible'] !== 1) { ?> </span><?php } ?>
            </td>
            <td style="white-space: nowrap;" class="annotation"></td>
            </tr><?php
        }
    }
    ?>
    </tbody>
</table>  
