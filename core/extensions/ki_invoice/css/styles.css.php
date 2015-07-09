<?php
	header('Content-type: text/css');
	$table_header = "../../../skins/standard/grfx/g3_table_header.png";
?>
#invoice_extension_wrap {
    border: 1px solid #000;
    border-top: none;
    position: absolute;
    overflow: hidden;
    margin: 0;
}

#invoice_extension {
    padding: 10px;
    background-color: #eee;
    color: #000;
    overflow: auto;
}

#invoice_extension_header {
    background-image: url('<?php echo $table_header; ?>');
    border: 1px solid #000;
    color: #fff;
    padding: 5px 10px;
    height: 20px;
    overflow: hidden;
    position: absolute;
}

#invoice_extension_advanced {
    padding: 10px;
}

#invoice_extension_advanced div {
    border-bottom: 1px dotted #666;
    padding: 5px 0;
}

#invoice_extension_advanced div label {
    width: 200px;
    vertical-align: top;
    display: inline-block;
}

#invoice_extension_advanced div select {
    min-width: 200px;
}
#invoice_extension_advanced div select#invoice_round_ID {
    min-width: 20px;
}

#invoice_screenshot {
    float: left;
    margin-right: 10px;
}

#invoice_button {
    width: 200px;
    font-weight: bold;
}

a { cursor: pointer }
