<?php global $kga ?>
<script type="text/javascript">
    $(document).ready(function () {
        xpe_ext_onload();
    });
</script>

<div id="xpe_head">
    <div class="left">
        <?php if (array_key_exists('user', $kga)): ?>
            <a href="#"
               onClick="floaterShow('../extensions/ki_expense/floaters.php','add_edit_record',0,0,600);
                $(this).blur();
                return false;">
                <?php echo $kga['lang']['add'] ?></a>
        <?php endif; ?>
    </div>
    <table id="xpe_h_tbl">
        <colgroup>
            <col class="option"/>
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
            <td class="option">&nbsp;</td>
            <td class="date"><?php echo $kga['lang']['datum'] ?></td>
            <td class="time"><?php echo $kga['lang']['timelabel'] ?></td>
            <td class="value"><?php echo $kga['lang']['xpe_expense'] ?></td>
            <td class="refundable"><?php echo $kga['lang']['xpe_refundable'] ?></td>
            <td class="customer"><?php echo $kga['lang']['customer'] ?></td>
            <td class="project"><?php echo $kga['lang']['project'] ?></td>
            <td class="description"><?php echo $kga['lang']['description'] ?></td>
            <td class="username"><?php echo $kga['lang']['xpe_username'] ?></td>
        </tr>
        </tbody>
    </table>
</div>

<div id="xpe_main">
    <?php echo $this->expenses_display ?>
</div>
