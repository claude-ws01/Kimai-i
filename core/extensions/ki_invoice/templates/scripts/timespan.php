<label><?php echo $this->kga['lang']['ext_invoice']['invoiceTimePeriod'] ?></label>
<b><?php echo $this->escape(strftime($this->kga['date_format'][2], $this->in)), ' - ', $this->escape(strftime($this->kga['date_format'][2], $this->out)) ?></b>
