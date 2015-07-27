<label><?php echo $GLOBALS['kga']['lang']['ext_invoice']['invoiceTimePeriod'] ?></label>
<b><?php
    echo $this->escape(strftime($GLOBALS['kga']['conf']['date_format_2'], $this->in))
        . ' - '
        . $this->escape(strftime($GLOBALS['kga']['conf']['date_format_2'], $this->out)) ?></b>
