<?php /** @var $this Zend_View_Helper_FormSelect */
global $kga ?>
<div class="top">
    <form>
        <?php echo $this->formSelect('customer_id', null,
              array('class' => 'formfield', 'style' => 'min-width:14em;'), $this->customers); ?>
        <input class='btn_ok'
               type="submit"
               value="<?php echo $kga['dict']['add_project'] ?>"
               onclick="adm_ext_newProject(); return false;">
    </form>
</div>
<table>
    <thead>
    <tr class="headerrow">
        <th><?php echo $kga['dict']['options'] ?></th>
        <th><?php echo $kga['dict']['projects'] ?></th>
        <th><?php echo $kga['dict']['groups'] ?></th>
    </tr>
    </thead>

    <tbody>
    <?php
    if (!is_array($this->projects) || count($this->projects) === 0) {
        ?>
        <tr>
        <td style="white-space: nowrap;" colspan='3'>
            <?php echo $this->error(); ?>
        </td>
        </tr><?php
    }
    else {
        foreach ($this->projects as $row) {
            ?>
        <tr class="<?php echo $this->cycle(array('odd', 'even'))->next() ?>">

            <td class="option">
                <a href="#"
                   onClick="editObject('project',<?php echo $row['project_id'] ?>); $(this).blur(); return false;"><img
                        src='../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/edit2.gif'
                        width='13' height='13' alt='<?php echo $kga['dict']['tip']['g_edit'] ?>'
                        title='<?php echo $kga['dict']['tip']['g_edit'] ?>' border='0'/></a>
                &nbsp;
                <a href="#" id="delete_project<?php echo $row['project_id'] ?>"
                   onClick="adm_ext_deleteProject(<?php echo $row['project_id'] ?>)"><img
                        src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/button_trashcan.png"
                        title="<?php echo $kga['dict']['delete_project'] ?>" width="13" height="13"
                        alt="<?php echo $kga['dict']['delete_project'] ?>" border="0"></a>
            </td>

            <td class="projects">
                <?php if ((int)$row['visible'] !== 1): ?><span style="color:#bbb"><?php endif; ?>
                    <?php if ($kga['pref']['flip_project_display']): ?><span
                        class="lighter"><?php echo $this->escape($this->truncate($row['customer_name'], 30, '...')) ?>
                        :</span> <?php echo $this->escape($row['name']) ?><?php else: ?><?php echo $this->escape($row['name']) ?>
                        <span
                            class="lighter">(<?php echo $this->escape($this->truncate($row['customer_name'], 30, '...')) ?>
                                            )</span><?php endif; ?>
                    <?php if ((int)$row['visible'] !== 1): ?></span><?php endif; ?>
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
