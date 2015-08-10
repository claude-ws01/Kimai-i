<?php global $kga ?>
<script type="text/javascript">
    $(document).ready(function () {
        xpo_ext_onload();
    });
</script>


<div id="xpo_head">
    <table id="xpo_h_tbl">
        <colgroup>
            <col class="date"/>
            <col class="from"/>
            <col class="to"/>
            <col class="time"/>
            <col class="dec_time"/>
            <col class="rate"/>
            <col class="wage"/>
            <col class="budget"/>
            <col class="approved"/>
            <col class="status"/>
            <col class="billable"/>
            <col class="customer"/>
            <col class="project"/>
            <col class="activity"/>
            <col class="description"/>
            <col class="comment"/>
            <col class="location"/>
            <col class="ref_code"/>
            <col class="user"/>
            <col class="cleared"/>
        </colgroup>
        <tbody>
        <tr><?php // @formatter:off  
            $K = &$kga['dict']['tip'];
            $DC = $this->disabled_columns; ?>
            
                <td class="date        <?php echo $DC['date'];?>"><a        title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('date');"><?php        echo $kga['dict']['xpo_date']?></a></td>
                <td class="from        <?php echo $DC['from'];?>"><a        title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('from');"><?php        echo $kga['dict']['in']?></a></td>
                <td class="to          <?php echo $DC['to'];?>"><a          title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('to');"><?php          echo $kga['dict']['out']?></a></td>
                <td class="time        <?php echo $DC['time'];?>"><a        title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('time');"><?php        echo $kga['dict']['time']?></a></td>
                <td class="dec_time    <?php echo $DC['dec_time'];?>"><a    title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('dec_time');"><?php    echo $kga['dict']['xpo_time']?></a></td>
                <td class="rate        <?php echo $DC['rate'];?>"><a        title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('rate');"><?php        echo $kga['dict']['xpo_rate']?></a></td>
                <td class="wage        <?php echo $DC['wage'];?>"><a        title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('wage');"><?php        echo $kga['dict']['xpo_tot']?></a></td>
                <td class="budget      <?php echo $DC['budget'];?>"><a      title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('budget');"><?php      echo $kga['dict']['xpo_bud']?></a></td>
                <td class="approved    <?php echo $DC['approved'];?>"><a    title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('approved');"><?php    echo $kga['dict']['xpo_appr']?></a></td>
                <td class="status      <?php echo $DC['status'];?>"><a      title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('status');"><?php      echo $kga['dict']['xpo_stat']?></a></td>
                <td class="billable    <?php echo $DC['billable'];?>"><a    title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('billable');"><?php    echo $kga['dict']['xpo_bill']?></a></td>
                <td class="customer    <?php echo $DC['customer'];?>"><a    title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('customer');"><?php    echo $kga['dict']['xpo_cust']?></a></td>
                <td class="project     <?php echo $DC['project'];?>"><a     title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('project');"><?php     echo $kga['dict']['project']?></a></td>
                <td class="activity    <?php echo $DC['activity'];?>"><a    title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('activity');"><?php    echo $kga['dict']['activity']?></a></td>
                <td class="description <?php echo $DC['description'];?>"><a title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('description');"><?php echo $kga['dict']['xpo_desc']?></a></td>
				<td class="comment     <?php echo $DC['comment'];?>"><a     title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('comment');"><?php     echo $kga['dict']['xpo_com']?></a></td>
                <td class="location    <?php echo $DC['location'];?>"><a    title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('location');"><?php    echo $kga['dict']['xpo_loc']?></a></td>
                <td class="ref_code    <?php echo $DC['ref_code'];?>"><a    title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('ref_code');"><?php    echo $kga['dict']['xpo_trk']?></a></td>
                <td class="user        <?php echo $DC['user'];?>"><a        title="<?php echo $K['xpo_exclude']; ?>" onClick="xpo_ext_toggle_column('user');"><?php        echo $kga['dict']['xpo_user']?></a></td>

                <td class="cleared"><a href="#" title="<?php echo $K['xpo_cleared_all']; ?>" onClick="$('#xpo_m_tbl td.cleared>a').click(); return false;">invert</a></td>
                <?php // @formatter:on ?>
        </tr>
        </tbody>
    </table>
</div>

<div id="xpo_main">
    <?php echo $this->table_display ?>
</div>
