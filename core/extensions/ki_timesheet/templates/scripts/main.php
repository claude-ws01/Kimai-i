<?php global $kga ?>
<script type="text/javascript">
    $(document).ready(function () {
        ts_ext_onload();
    });
</script>

<div id="ts_head">
    <div class="left">
        <?php if (array_key_exists('user', $kga)): ?>
            <a href="#"
               onClick="floaterShow('../extensions/ki_timesheet/floaters.php',
               'add_edit_timeSheetEntry',
               selected_project+'|'+selected_activity,0,650);
               $(this).blur();
               return false;"
               title="<?php echo $GLOBALS['kga']['dict']['tip']['ts_add'] ?>"><?php echo $GLOBALS['kga']['dict']['add'] ?></a>
        <?php endif; ?>
    </div>
    <table id="ts_h_tbl">
        <colgroup>
            <?php if (is_user()) echo '<col class="option"/>'; ?>
            <col class="date"/>
            <col class="from"/>
            <col class="to"/>
            <col class="time"/>
            <?php if ($this->showRates) { echo '<col class="wage"/>'; } ?>
            <col class="customer"/>
            <col class="project"/>
            <col class="activity"/>
            <?php if ($this->show_ref_code) { echo '<col class="ref_code"/>'; } ?>
            <col class="username"/>
        </colgroup>
        <tbody>
        <tr>
            <?php if (is_user()) echo '<td class="option">&nbsp;</td>'; ?>
            <td class="date"><?php echo $GLOBALS['kga']['dict']['datum'] ?></td>
            <td class="from"><?php echo $GLOBALS['kga']['dict']['in'] ?></td>
            <td class="to"><?php echo $GLOBALS['kga']['dict']['out'] ?></td>
            <td class="time"><?php echo $GLOBALS['kga']['dict']['time'] ?></td>
            <?php if ($this->showRates): ?>
                <td class="wage"><?php echo $GLOBALS['kga']['dict']['ts_wage'] ?></td>
            <?php endif; ?>
            <td class="customer"><?php echo $GLOBALS['kga']['dict']['customer'] ?></td>
            <td class="project"><?php echo $GLOBALS['kga']['dict']['project'] ?></td>
            <td class="activity"><?php echo $GLOBALS['kga']['dict']['activity'] ?></td>
            <?php if ($this->show_ref_code) { ?>
                <td class="ref_code"><?php echo $GLOBALS['kga']['dict']['ts_ref_code'] ?></td>
            <?php } ?>
            <td class="username"><?php echo $GLOBALS['kga']['dict']['ts_user'] ?></td>
        </tr>
        </tbody>
    </table>
</div>

<div id="ts_main">
    <?php echo $this->timeSheet_display ?>
</div>
