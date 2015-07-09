<?php
	header('Content-type: text/css');
	$table_header = "../../../skins/standard/grfx/g3_table_header.png";
	$schraff3 = "../../../skins/standard/grfx/schraff3.png";
?>

div.ki_export table {
    border-collapse: collapse;
    font-size: 11px;
    color: #363636;
    border-bottom: 1px solid #888;
    text-align: center;
}

div.ki_export table a.preselect_lnk {
    color: #363636;
    text-decoration: none;
    border-bottom: 1px dotted #bbb;
}

div.ki_export table a:hover {
    color: #0F9E00;
    border-bottom: none;
}

div.ki_export table thead {
    height: 25px;
    text-align: left;
    color: #FFF;
}

div.ki_export table thead th {
    background-image: url('<?php echo $table_header; ?>');
}

div.ki_export tr.even td,
div.ki_export tr.odd td {
    border-bottom: none;
    border-left: none;
    border-right: 1px dotted #CCC;
    padding: 4px 0;
}

#export_head td {
    overflow: hidden;
    padding: 4px 0;
}

div.ki_export tr.hover td {
    background: #FFC !important;
    border-bottom: 1px solid #666 !important;*/
}

div.ki_export tr.even td {
    background: #FFF;
}

div.ki_export tr.odd td {
    background: #EEE;
}

div#xptable tr.odd td.comment,
div#xptable tr.odd td.trackingNumber,
div#xptable tr.odd td.location {
    background: #CFE3F5;
}

div#xptable tr.even td.comment,
div#xptable tr.even td.trackingNumber,
div#xptable tr.even td.location {
    background: #ECF7FD;
}

div#xptable tr.even.expense td {
    background: #E5FFBF;
}

div#xptable tr.odd.expense td {
    background: #DCF4B7;
}

div#xptable tr.odd.expense td.comment,
div#xptable tr.odd.expense td.trackingNumber,
div#xptable tr.odd.expense td.location {
    background: #A3F1A6;
}

div#xptable tr.even.expense td.comment,
div#xptable tr.even.expense td.trackingNumber,
div#xptable tr.even.expense td.location {
    background: #C8F1CC;
}

div#xptable tr.odd td.cleared {
    background: #6E7882;
}

div#xptable tr.even td.cleared {
    background: #86939F;
}

div#xptable tr td.time {
    border-bottom: 1px dotted white;;
}

div#xptable tr.even td.time {
    background: #A4E7A5;
}

div#xptable tr.odd td.time {
    background: #64BF61;
}

div#xptable tr.active td.time {
    background: #F00;
}

div#xptable tr.expense td.time {
    background-image: url('<?php echo $schraff3; ?>');
}

div.ki_export > div#xp > div#xptable > table > tbody > tr > td.cleared {
    border-right: none;
}

#export_head table {
    width: 100%;
}

#export_head td {
    white-space: nowrap;
}

/* columns width */
#export_head td.option,
#xp td.option {
    width: 70px;
}

#export_head td.date,
#xp td.date {
    width: 50px;
}

#export_head td.from,
#export_head td.to,
#xp td.from,
#xp td.to {
    width: 50px;
}

#export_head td.time,
#xp td.time {
    width: 40px;
}

#export_head td.dec_time,
#xp td.dec_time {
    width: 40px;
}

#xp td.rate {
    width: 50px;
}

#xp td.wage {
    width: 50px;
}

#xp td.budget {
    width: 50px;
}

#xp td.approved {
    width: 50px;
}

#xp td.status {
    width: 50px;
}

#xp td.billable {
    width: 40px;
}

#xp td.customer {
    min-width: 50px;
    padding-left: 2px;
    padding-right: 2px;
}

#xp td.project {
    min-width: 50px;
}

#xp td.activity {
    min-width: 60px;
}

#xp td.description {
    width: 120px;
}

#xp td.comment {
    width: 40px;
}

#xp td.location {
    width: 30px;
}

#xp td.trackingNumber {
    width: 40px;
}

#export_head td.user,
#xp td.user {
    width: 50px;
}

#export_head td.cleared,
#xp td.cleared {
    width: 25px;
}
/* columns width end */

#export_head td.customer,
#export_head td.project,
#export_head td.description,
#export_head td.comment,
#export_head td.location,
#export_head td.user,
#xp td.customer,
#xp td.project,
#xp td.description,
#xp td.comment,
#xp td.location,
#xp td.user
{
    padding-left: 2px;
    padding-right: 2px;
}

div.ki_export table tr.odd td.disabled {
    background-color: #FFC7BB !important;
}

div.ki_export table tr.even td.disabled {
    background-color: #FFE1DB !important;
}

div.ki_export table td.cleared a {
    width: 13px;
    height: 13px;
    display: block;
    margin: 0 auto;
}

div.ki_export table td.cleared a.is_cleared {
    background: url('../grfx/cleared.png') 0 -13px no-repeat;
}

div.ki_export table td.cleared a.isnt_cleared {
    background: url('../grfx/cleared.png') no-repeat;
}

#export_head td.cleared a,
div#export_head div.right a {
    background-image: url('../grfx/invert.png');
    overflow: hidden;
    display: block;
    width: 22px;
    height: 16px;
    text-indent: -500px;

}
