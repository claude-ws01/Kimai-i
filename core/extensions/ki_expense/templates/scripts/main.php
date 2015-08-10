<?php global $kga ?>
<script type="text/javascript">
    $(document).ready(function () {
        xpe_ext_onload();
    });
</script>

<div id="xpe_head">
    <div class="left">
        <?php if (is_user()): ?>
            <a href="#"
               onClick="floaterShow('../extensions/ki_expense/floaters.php','add_edit_record',0,0,600);
                $(this).blur();
                return false;">
                <?php echo $kga['dict']['add'] ?></a>
        <?php endif; ?>
    </div>
    <table id="xpe_h_tbl">
        <colgroup>
            <?php if (is_user()) { ?><col class="option"/><?php } ?>
            <col class="date"/>
            <col class="time"/>
            <col class="value"/>
            <col class="refundable"/>
            <col class="customer"/>
            <col class="project"/>
            <col class="description"/>
            <col class="username"/>
        </colgroup>
        <tbody>
        <tr>
            <?php if (is_user()) { ?><td class="option">&nbsp;</td><?php } ?>
            <td class="date"><?php echo $kga['dict']['datum'] ?></td>
            <td class="time"><?php echo $kga['dict']['timelabel'] ?></td>
            <td class="value"><?php echo $kga['dict']['xpe_expense'] ?></td>
            <td class="refundable"><?php echo $kga['dict']['xpe_refundable'] ?></td>
            <td class="customer"><?php echo $kga['dict']['customer'] ?></td>
            <td class="project"><?php echo $kga['dict']['project'] ?></td>
            <td class="description"><?php echo $kga['dict']['description'] ?></td>
            <td class="username"><?php echo $kga['dict']['xpe_username'] ?></td>
        </tr>
        </tbody>
    </table>
</div>

<div id="xpe_main">
    <?php echo $this->expenses_display ?>
</div>
