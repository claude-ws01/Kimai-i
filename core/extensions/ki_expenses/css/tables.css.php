<?php
	header('Content-type: text/css');
	$table_header = "../../../skins/standard/grfx/g3_table_header.png";
	$add =          "../../../skins/standard/grfx/add.png";
?>

div.ki_expenses table {
    border-collapse: collapse;
    font-size: 11px;
    color: #363636;
    border-bottom: 1px solid #888;
}

div.ki_expenses table a.preselect_lnk {
    color: #363636;
    text-decoration: none;
    border-bottom: 1px dotted #bbb;
}

div.ki_expenses table a:hover {
    color: #0F9E00;
    border-bottom: none;
}

div.ki_expenses table thead {
    height: 25px;
    text-align: left;
    color: #FFF;
}

div.ki_expenses table thead th {
    background-image: url('<?php echo $table_header; ?>');
}

div.ki_expenses tr.even td,
div.ki_expenses tr.odd td {
    border-bottom: none;
    border-left: none;
    border-right: 1px dotted #CCC;
}

div#expenses_head td,
div.ki_expenses tr.even td,
div.ki_expenses tr.odd td
{
    padding: 4px 0;
    text-align: center;
}

div.ki_expenses tr.hover td {
    background: #FFC !important;
}

div.ki_expenses tr.even td {
    background: #FFF;
}

div.ki_expenses tr.odd td {
    background: #EEE;
}

div#expensestable tr td.time {
    border-bottom: 1px dotted white;;
}

div#expensestable tr.even td.value {
    background: #A4E7A5;
}

div#expensestable tr.odd td.value {
    background: #64BF61;
}

div.ki_expenses tr td.option,
div.ki_expenses tr td.date,
div.ki_expenses tr td.time {
    text-align: center;
}

div.ki_expenses a {
    margin: 0 3px;
}

div.ki_expenses > div#expenses > div#expensestable > table > tbody > tr > td.activity {
    border-right: none;
}

#expenses_head td {
    white-space: nowrap;
}

/* column width */
#expenses_head td.option,
#expenses td.option {
    width: 70px;
}

#expenses_head td.date,
#expenses td.date {
    width: 50px;
}

#expenses_head td.time,
#expenses td.time {
    width: 40px;
}

#expenses_head td.value,
#expenses td.value {
    width: 40px;
}

#expenses_head td.refundable,
#expenses td.refundable {
    width: 50px;
}
/* column width end */

#expenses_head td.project,
#expenses_head td.designation,
#expenses_head td.username,
#expenses td.project,
#expenses td.designation,
#expenses td.username
{
    padding-left: 2px;
    padding-right: 2px;
}


div#expenses_head div.left {
    position: absolute;
    overflow: hidden;
    width: 32px;
    height: 20px;
    top: 3px;
    left: 3px;
}

div#expenses_head div.left a {
    background-image: url('<?php echo $add; ?>');
    overflow: hidden;
    display: block;
    width: 22px;
    height: 16px;
    text-indent: -500px;
}
