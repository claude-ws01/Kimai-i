<a href="#" onClick="floaterShow('floaters.php','add_edit_activity',0,0,500); $(this).blur(); return false;"><img src="../skins/<?php echo $this->escape($GLOBALS['kga']['pref']['skin'])?>/grfx/add.png" width="22" height="16" alt="<?php echo $GLOBALS['kga']['lang']['new_activity']?>"></a> <?php echo $GLOBALS['kga']['lang']['new_activity']?>

&nbsp;&nbsp;&nbsp;<?php echo $GLOBALS['kga']['lang']['view_filter']?>:
<select size="1" id="activity_project_filter" onchange="adm_ext_refreshSubtab('activities');">
  <option value="-2" <?php if ($this->selected_activity_filter==-2): ?> selected="selected"<?php endif; ?>> <?php echo $GLOBALS['kga']['lang']['unassigned']?></option>
  <option value="-1" <?php if ($this->selected_activity_filter==-1): ?> selected="selected"<?php endif; ?>> <?php echo $GLOBALS['kga']['lang']['all_activities']?></option>
  <?php foreach ($this->projects as $row): ?>
  <option value="<?php echo $row['project_id']?>"
     <?php if ($this->selected_activity_filter==$row['project_id']): ?> selected="selected"<?php endif; ?>> <?php echo $this->escape($row['name']),' (', $this->escape($this->truncate($row['customer_name'],30,"..."))?>)</option>
  <?php endforeach; ?>
</select>

<br/><br/>

<table>

    <thead>
        <tr class='headerrow'>
            <th><?php echo $GLOBALS['kga']['lang']['options']?></th>
            <th><?php echo $GLOBALS['kga']['lang']['activities']?></th>
            <th><?php echo $GLOBALS['kga']['lang']['groups']?></th>
        </tr>
    </thead>

<tbody>
<?php
    if (!is_array($this->activities) || count($this->activities) == 0)
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
        foreach ($this->activities as $activity)
        {
            ?>

            <tr class="<?php echo $this->cycle(array("odd","even"))->next()?>">

                <td class="option">
                    <a href ="#" onClick="editSubject('activity',<?php echo $activity['activity_id']?>); $(this).blur(); return false;">
                        <img src='../skins/<?php echo $this->escape($GLOBALS['kga']['pref']['skin'])?>/grfx/edit2.gif' width='13' height='13' alt='<?php echo $GLOBALS['kga']['lang']['edit']?>' title='<?php echo $GLOBALS['kga']['lang']['edit']?>' border='0' /></a>

                    &nbsp;

                    <a href="#" id="delete_activity<?php echo $activity['activity_id']?>" onClick="adm_ext_deleteActivity(<?php echo $activity['activity_id']?>)">
                      <img src="../skins/<?php echo $this->escape($GLOBALS['kga']['pref']['skin'])?>/grfx/button_trashcan.png" title="<?php echo $GLOBALS['kga']['lang']['delete_activity']?>" width="13" height="13" alt="<?php echo $GLOBALS['kga']['lang']['delete_activity']?>" border="0"></a>
                </td>

                <td class="activities">
                    <?php if ($activity['visible'] != 1): ?><span style="color:#bbb"> <?php endif; ?>
                    <?php echo $this->escape($activity['name']); ?>
                    <?php if ($activity['visible'] != 1): ?></span><?php endif; ?>
                </td>

                <td>
                    <?php echo $this->escape($activity['groups']); ?>
                </td>

            </tr>
            <?php
        }
    }
    ?>
    </tbody>
</table>
