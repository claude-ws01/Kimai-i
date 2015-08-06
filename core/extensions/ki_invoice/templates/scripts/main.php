<script type="text/javascript">
    $(document).ready(function () {
        inv_ext_onload();
        $('#editVatLink').click(function () {
            this.blur();
            floaterShow(inv_ext_path + "floaters.php", "editVatRate", 0, 0, 250);
        });
        $('#invoice_customerID').change(function () {
            $("#ajax_wait").show();
            $.ajax({
                url: inv_ext_path + 'processor.php',
                data: {
                    'axAction': 'projects',
                    'customer_id': $(this).val()
                }
            }).done(function (data) {
                var pr = $('#invoice_projectID');
                pr.empty();
                for (var project_id in data)
                    pr.append($('<option>', {
                        value: project_id,
                        text: data[project_id]
                    }));
                $("#ajax_wait").hide();
            });
        });
    });
</script>

<div id="inv_head">
    <strong style="padding: 5px 10px;display: block;"><?php echo $GLOBALS['kga']['dict']['ext_invoice']['invoiceTitle'] ?></strong>
</div>

<div id="inv_main">
    <div id="invoice_extension">
        <form id="inv_ext_form" method="post" action="../extensions/ki_invoice/print.php" target="_blank">
            <div id="inv_ext_advanced">
                <div>
                    <label for="invoice_customerID"><?php echo $GLOBALS['kga']['dict']['customer'] ?>:</label>

                    <?php echo $this->formSelect('customer_id', $this->preselected_customer,
                                                 array('id' => 'invoice_customerID', 'class' => 'formfield'),
                                                 $this->customers); ?>
                </div>
                <div>
                    <label for="invoice_projectID"><?php echo $GLOBALS['kga']['dict']['project'] ?>:</label>
                    <?php echo $this->formSelect('project_id[]', $this->preselected_project,
                                                 array('id' => 'invoice_projectID', 'class' => 'formfield', 'multiple' => 'multiple'),
                                                 $this->projects); ?>
                </div>
                <div id="invoice_timespan">
                    <?php echo $this->timespan_display ?>
                </div>

                <!--Work in Progress: Select box for form type-->
                <div>
                    <label><?php echo $GLOBALS['kga']['dict']['ext_invoice']['invoiceTemplate'] ?></label>
                    <?php echo $this->formSelect('ivform_file', null, array('id' => 'invoice_form_docs', 'class' => 'formfield'), $this->sel_form_files); ?>
                </div>

                <!-- Some boxes below are checked by default. Delete "checked" to set default to unchecked condition -->

                <div>
                    <label for="defaultVat"><?php echo $GLOBALS['kga']['dict']['ext_invoice']['defaultVat'] ?>:</label>
                    <span
                        id="defaultVat"><?php echo $this->escape(
                            str_replace('.',
                                        $GLOBALS['kga']['conf']['decimal_separator'],
                                        $GLOBALS['kga']['conf']['vat_rate'])
                        ) ?></span> % <a id="editVatLink"
                                          href="#">(<?php echo $GLOBALS['kga']['dict']['change'] ?>)</a>
                </div>
                <div>
                    <label for="short"><?php echo $GLOBALS['kga']['dict']['ext_invoice']['invoiceOptionShort'] ?></label>
                    <input type="checkbox" name="short" id="short" checked="checked">
                </div>
                <div>
                    <label for="round"><?php echo $GLOBALS['kga']['dict']['ext_invoice']['invoiceOptionRound'] ?></label>
                    <input type="checkbox" name="round" id="round" checked="checked">
                    <?php echo $this->formSelect('roundValue', null, array('id' => 'invoice_round_ID', 'class' => 'formfield'), $this->roundingOptions); ?>
                </div>
                <div>
                    <label for="filter_cleared"><?php echo $GLOBALS['kga']['dict']['ext_invoice']['entryStatus'] ?></label>
                    <select name="filter_cleared" id="filter_cleared">
                        <option
                            value="-1" <?php if (!$GLOBALS['kga']['pref']['hide_cleared_entries']): ?> selected="selected" <?php endif; ?>><?php echo $GLOBALS['kga']['dict']['export_extension']['cleared_all'] ?></option>
                        <option
                            value="1"><?php echo $GLOBALS['kga']['dict']['export_extension']['cleared_cleared'] ?></option>
                        <option
                            value="0" <?php if ($GLOBALS['kga']['pref']['hide_cleared_entries']): ?> selected="selected" <?php endif; ?>><?php echo $GLOBALS['kga']['dict']['export_extension']['cleared_open'] ?></option>
                    </select>
                </div>
                <div style="border-bottom:none;">
                    <label for="invoice_button"></label>
                    <input type="submit" class="btn_ok" id="invoice_button"
                           value="<?php echo $GLOBALS['kga']['dict']['ext_invoice']['invoiceButton'] ?>">
                </div>
            </div>
        </form>
    </div>
</div>
