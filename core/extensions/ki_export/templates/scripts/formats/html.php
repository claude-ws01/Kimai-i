<?php global $kga ?>
<html>
<head>
    <title></title>
    <meta content="">

    <style type="text/css" media="all">
        body, div, dl, dt, dd, ul, ol, li, h1, h2, h3, h4, h5, h6, pre, form, fieldset, input, textarea, p, blockquote, th, td {
            margin: 0;
            padding: 0;
        }
        .total_header,
        th {
            background: #ccc;
            border-top: 1px solid #999;
            border-bottom: 1px solid #999;
            font-family: Arial, Verdana, sans-serif;
            font-size: 11px;
            font-weight: bold;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 30px;
        }

        fieldset, img {
            border: 0;
        }

        address, caption, cite, code, dfn, em, strong, th, var {
            font-style: normal;
            font-weight: normal;
        }

        ol, ul {
            list-style: none;
        }

        caption, th {
            text-align: left;
        }

        h2 {
            margin-bottom: 10px;
        }

        q:before {
            content: '';
        }

        q:after {
            content: '';
        }

        abbr, acronym {
            border: 0;
        }

        th {
            text-align: left;
            font-weight: bold;
        }

        th {
            border-right: 1px solid #999999;
            padding: 5px;
        }

        th:first-child {
            border-left: 1px solid #999999;
        }

        td {
            border-right: 1px solid #999999;
            padding: 5px;
        }

        td:first-child {
            border-left: 1px solid #999999;
        }

        h1 {
            font-size: 150%;
            font-weight: bold;
            margin-bottom: 10px;
        }

    </style>

    <style type="text/css" media="print">

        body {
            color: black;
            font-family: Arial, Verdana, sans-serif;
            font-size: 11px;
        }

        td {
            border-bottom: 1px solid #999;
            font-family: Arial, Verdana, sans-serif;
            font-size: 11px;
        }

        #div_selectform {
            display: none;
        }

        #div_liste {

        }

        #invertbtn, .invertclm {
            display: none;
        }

    </style>

    <style type="text/css" media="screen">
        body {
            color: black;
            font-family: Arial, Verdana, sans-serif;
            font-size: 11px;
            padding: 10px;
        }


        td {
            border-bottom: 1px solid #999;
            font-family: Arial, Verdana, sans-serif;
            font-size: 11px;
        }
        th, td {
            text-align: center;
        }
    </style>

</head>
<body>

<h2> <?php echo $kga['dict']['export_extension']['time_period'] ?>
    : <?php echo $this->escape($this->timespan) ?></h2>

<?php if ($this->customersFilter !== ''): ?><br/>
    <b><?php echo $kga['dict']['customers'] ?></b>: <?php echo $this->escape($this->customersFilter) ?><?php endif;
if ($this->projectsFilter !== ''): ?><br/>
    <b><?php echo $kga['dict']['projects'] ?></b>: <?php echo $this->escape($this->projectsFilter) ?><?php endif; ?>
<br/>

<?php // @formatter:off ?>
<?php if ((int)$this->summary !== 0) { ?><h2><?php echo $kga['dict']['export_extension']['summary'] ?></h2>
<table border="1">
    <tbody>
        <tr>
            <th><?php echo $kga['dict']['activity'] ?></th>
            <?php if (!isset($this->columns['dec_time'])): ?><th><?php echo $kga['dict']['export_extension']['duration'] ?></th><?php endif; ?>
            <?php if (!isset($this->columns['wage'])): ?><th><?php echo $kga['dict']['export_extension']['costs'] ?></th><?php endif; ?>
            <?php if (!isset($this->columns['budget'])): ?><th><?php echo $kga['dict']['budget'] ?></th><?php endif; ?>
            <?php if (!isset($this->columns['approved'])): ?><th><?php echo $kga['dict']['approved'] ?></th><?php endif; ?>
        </tr>
        <?php foreach ($this->summary as $row): ?>
            <tr>
                <td><?php echo $this->escape($row['name']) ?></td>
                <?php if (!isset($this->columns['dec_time'])): ?><td> <?php if ($row['time'] !== -1) { echo $this->escape($row['time']); } ?> </td><?php endif; ?>
                <?php if (!isset($this->columns['wage'])): ?><td><?php echo $this->escape($row['wage']) ?></td><?php endif; ?>
                <?php if (!isset($this->columns['budget'])): ?><td><?php echo $this->escape($row['budget']) ?></td><?php endif; ?>
                <?php if (!isset($this->columns['approved'])): ?><td><?php echo $this->escape($row['approved']) ?></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td>
                <i><?php echo $kga['dict']['export_extension']['finalamount'] ?></i>
            </td><?php if (!isset($this->columns['dec_time'])): ?><td><?php echo $this->escape(number_format($this->timeSum, 2, $kga['conf']['decimal_separator'], '')) ?></td><?php endif; ?>
            <?php if (!isset($this->columns['wage'])): ?><td><?php echo $this->escape(number_format($this->wageSum, 2, $kga['conf']['decimal_separator'], '')) ?></td><?php endif; ?>
            <?php if (!isset($this->columns['wage'])): ?><td><?php echo $this->escape(number_format($this->budgetSum, 2, $kga['conf']['decimal_separator'], '')) ?></td><?php endif; ?>
            <?php if (!isset($this->columns['wage'])): ?><td><?php echo $this->escape(number_format($this->approvedSum, 2, $kga['conf']['decimal_separator'], '')) ?></td><?php endif; ?>
        </tr>
    </tbody>
</table>
<?php } ?>
<h2><?php echo $kga['dict']['export_extension']['full_list'] ?></h2>
<table border="1">
    <tbody>
        <tr>
            <?php if (!isset($this->columns['date'])): ?><th><?php echo $kga['dict']['datum'] ?></th>       <?php endif; ?>
            <?php if (!isset($this->columns['from'])): ?><th><?php echo $kga['dict']['in'] ?></th>          <?php endif; ?>
            <?php if (!isset($this->columns['to'])): ?><th><?php echo $kga['dict']['out'] ?></th>         <?php endif; ?>
            <?php if (!isset($this->columns['time'])): ?><th><?php echo $kga['dict']['time'] ?></th>        <?php endif; ?>
            <?php if (!isset($this->columns['dec_time'])): ?><th><?php echo $kga['dict']['timelabel'] ?></th>   <?php endif; ?>
            <?php if (!isset($this->columns['rate'])): ?><th><?php echo $kga['dict']['rate'] ?></th>        <?php endif; ?>
            <?php if (!isset($this->columns['wage'])): ?><th><?php echo $kga['dict']['wage'] ?></th>       <?php endif; ?>
            <?php if (!isset($this->columns['budget'])): ?><th><?php echo $kga['dict']['budget'] ?></th>      <?php endif; ?>
            <?php if (!isset($this->columns['approved'])): ?><th><?php echo $kga['dict']['approved'] ?></th>    <?php endif; ?>
            <?php if (!isset($this->columns['status'])): ?><th><?php echo $kga['dict']['status'] ?></th>      <?php endif; ?>
            <?php if (!isset($this->columns['billable'])): ?><th><?php echo $kga['dict']['billable'] ?></th>    <?php endif; ?>
            <?php if (!isset($this->columns['customer'])): ?><th><?php echo $kga['dict']['customer'] ?></th>    <?php endif; ?>
            <?php if (!isset($this->columns['project'])): ?><th><?php echo $kga['dict']['project'] ?></th>     <?php endif; ?>
            <?php if (!isset($this->columns['activity'])): ?><th><?php echo $kga['dict']['activity'] ?></th>    <?php endif; ?>
            <?php if (!isset($this->columns['description'])): ?><th><?php echo $kga['dict']['description'] ?></th> <?php endif; ?>
            <?php if (!isset($this->columns['comment'])): ?><th><?php echo $kga['dict']['comment'] ?></th>     <?php endif; ?>
            <?php if (!isset($this->columns['location'])): ?><th><?php echo $kga['dict']['location'] ?></th>   <?php endif; ?>
            <?php if (!isset($this->columns['ref_code'])): ?><th><?php echo $kga['dict']['xpe_ref_code'] ?></th>  <?php endif; ?>
            <?php if (!isset($this->columns['user'])): ?><th><?php echo $kga['dict']['username'] ?></th>    <?php endif; ?>
            <?php if (!isset($this->columns['cleared'])): ?><th><?php echo $kga['dict']['cleared'] ?></th>     <?php endif; ?>
        </tr>

        <?php foreach ($this->exportData as $row): ?>
            <tr>

                <?php if (!isset($this->columns['date'])): ?><td><?php if ($this->custom_dateformat) {echo $this->escape(strftime($this->custom_dateformat, $row['time_in']));}
                        else {echo $this->escape(strftime($kga['conf']['date_format_1'], $row['time_in']));}?></td><?php endif; ?>

                <?php if (!isset($this->columns['from'])): ?><td><?php if ($this->custom_timeformat) { echo $this->escape(strftime($this->custom_timeformat, $row['time_in'])); }
                        else { echo $this->escape(strftime('%H:%M', $row['time_in'])); } ?></td><?php endif; ?>

                <?php if (!isset($this->columns['to'])): ?><td><?php if ($row['time_out']): ?><?php if ($this->custom_timeformat) { echo $this->escape(strftime($this->custom_timeformat, $row['time_out'])); }
                        else { echo $this->escape(strftime('%H:%M', $row['time_in'])); }
                            ?><?php else: ?>&ndash;&ndash;:&ndash;&ndash;<?php endif; ?></td><?php endif; ?>


                <?php if (!isset($this->columns['time'])): ?><td><?php echo $row['duration'] ? $row['formatted_duration'] : '&ndash;:&ndash;&ndash;' ?></td><?php endif; ?>
                <?php if (!isset($this->columns['dec_time'])): ?><td><?php echo $row['decimal_duration'] ? $this->escape(str_replace('.', $kga['conf']['decimal_separator'], $row['decimal_duration'])) : '&ndash;:&ndash;&ndash;' ?></td><?php endif; ?>
                <?php if (!isset($this->columns['rate'])): ?><td><?php echo $this->escape(str_replace('.', $kga['conf']['decimal_separator'], $row['rate'])) ?></td><?php endif; ?>
                <?php if (!isset($this->columns['wage'])): ?><td><?php echo $row['wage'] ? $this->escape(str_replace('.', $kga['conf']['decimal_separator'], $row['wage'])) : '&ndash;' ?></td><?php endif; ?>
                <?php if (!isset($this->columns['budget'])): ?><td><?php echo $this->escape($row['budget']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['approved'])): ?><td><?php echo $this->escape($row['approved']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['status'])): ?><td><?php echo $this->escape($row['status']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['billable'])): ?><td><?php if (!empty($row['billable'])): echo $this->escape($row['billable']), '%'; endif; ?></td><?php endif; ?>
                <?php if (!isset($this->columns['customer'])): ?><td><?php echo $this->escape($row['customer_name']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['project'])): ?><td><?php echo $this->escape($row['project_name']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['activity'])): ?><td><?php echo $this->escape($row['activity_name']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['description'])): ?><td><?php echo $this->escape($row['description']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['comment'])): ?><td><?php echo nl2br($this->escape($row['comment'])) ?></td><?php endif; ?>
                <?php if (!isset($this->columns['location'])): ?><td><?php echo $this->escape($row['location']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['ref_code'])): ?><td><?php echo $this->escape($row['ref_code']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['user'])): ?><td><?php echo $this->escape($row['username']); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['cleared'])): ?><td><?php if ($row['cleared']): echo $kga['dict']['cleared']; endif; ?></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>

        <?php if ($this->timeSum > 0 || $this->wageSum > 0): ?>
            <tr>
                <td class="total_header" colspan="<?php echo count($this->columns) ?>">
                    <?php echo $kga['dict']['export_extension']['finalamount'] ?>
                </td>
            </tr>
            <tr>
                <?php if (!isset($this->columns['date'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['from'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['to'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['time'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['dec_time'])): ?><td><?php echo $this->escape($this->timeSum); ?></td> <?php endif; ?>
                <?php if (!isset($this->columns['rate'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['wage'])): ?><td><?php echo $this->escape($this->wageSum); ?></td><?php endif; ?>
                <?php if (!isset($this->columns['budget'])): ?><td><?php echo $this->escape($this->budgetSum); ?></td> <?php endif; ?>
                <?php if (!isset($this->columns['approved'])): ?><td><?php echo $this->escape($this->approvedSum); ?></td> <?php endif; ?>
                <?php if (!isset($this->columns['status'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['billable'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['customer'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['project'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['activity'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['description'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['comment'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['location'])): ?> <td></td> <?php endif; ?>
                <?php if (!isset($this->columns['ref_code'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['user'])): ?><td></td> <?php endif; ?>
                <?php if (!isset($this->columns['cleared'])): ?><td></td> <?php endif; ?>
            </tr><?php endif; ?>
    </tbody>
</table>
<?php // @formatter:on ?>
</body>
</html>
