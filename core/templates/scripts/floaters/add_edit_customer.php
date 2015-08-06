<?php global $kga ?>
<script type="text/javascript">
    $(document).ready(function () {

        $('.disableInput').click(function () {
            var input = $(this);
            if (input.is(':checked'))
                input.siblings().prop("disabled", "disabled");
            else
                input.siblings().prop("disabled", "");
        });

        $('#add_edit_customer').ajaxForm({
            'beforeSubmit': function () {
                clearFloaterErrorMessages();

                if ($('#add_edit_customer').attr('submitting')) {
                    return false;
                }
                else {
                    $("#ajax_wait").show();
                    $('#add_edit_customer').attr('submitting', true);
                    return true;
                }
            },
            'success': function (result) {
                $('#add_edit_customer').removeAttr('submitting');
                $("#ajax_wait").hide();

                for (var fieldName in result.errors)
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);


                if (result.errors.length == 0) {
                    floaterClose();
                    hook_customers_changed();
                }
            },
            'error': function () {
                $('#add_edit_customer').removeAttr('submitting');
                $("#ajax_wait").hide();
            }
        });

        $('#floater_innerwrap').tabs({selected: 0});
    });
</script>

<div id="floater_innerwrap">

    <div id="floater_handle">
        <span id="floater_title">
            <?php if (isset($this->id) && $this->id > 0) {
                echo $kga['dict']['edit'], ' ', $this->name;
            }
            else {
                echo $kga['dict']['new_customer'];
            } ?>
        </span>

        <div class="right">
            <a href="#" class="close" onclick="floaterClose();"><?php echo $kga['dict']['close'] ?></a>
        </div>
    </div>

    <div class="menuBackground">

        <ul class="menu tabSelection">
            <li class="tab norm"><a href="#general">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['general'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#address">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['address'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#contact">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['contact'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#groups">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['groups'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#commenttab">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['comment'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
        </ul>
    </div>
    <form id="add_edit_customer" action="processor.php" method="post">
        <input name="customerFilter" type="hidden" value="0"/>
        <input name="axAction" type="hidden" value="add_edit_CustomerProjectActivity"/>
        <input name="axValue" type="hidden" value="customer"/>
        <input name="id" type="hidden" value="<?php echo $this->id ?>"/>

        <div id="floater_tabs" class="floater_content">
            <!--    GENERAL    -->
            <fieldset id="general">
                <ul>
                    <li>
                        <label for="name"><?php echo $kga['dict']['customer'] ?>:</label>
                        <?php echo $this->formText('name', $this->name, array('style' => 'width:250px;')); ?>
                    </li>
                    <li>
                        <label for="vat_rate"><?php echo $kga['dict']['vat'] ?>:</label>
                        <?php echo $this->formText('vat_rate', $this->vat_rate, array('style' => 'width:30px; text-align:right;padding-right:5px')), ' %' ?>
                    </li>
                    <li>
                        <label for="visible"><?php echo $kga['dict']['visibility'] ?>:</label>
                        <?php echo $this->formCheckbox('visible', '1', array('checked' => $this->visible || !$this->id)); ?>
                    </li>
                    <li>
                        <label for="password"><?php echo $kga['dict']['password'] ?>:</label>

                        <?php
                        if (!$this->password) {
                            echo $this->formText('password', '', array('cols' => 30, 'rows' => 3, 'disabled' => 'disabled'));
                        }
                        else {
                            echo $this->formText('password', '', array('cols' => 30, 'rows' => 3));
                        } ?>
                        <br/>
                        <label for="no_password"><?php echo $kga['dict']['no_web_access'] ?>:</label>
                        <?php echo $this->formCheckbox('no_password', '1', array('class' => 'disableInput', 'checked' => !$this->password)); ?>
                    </li>
                    <li>
                        <label for="timezone"><?php echo $kga['dict']['timezone'] ?>
                            :</label><?php echo $this->timeZoneSelect('timezone', $this->timezone); ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="commenttab" style="padding-bottom:0;">
                <ul>
                    <li style="border:none;padding-bottom:0;">
                        <label for="comment"><?php echo $kga['dict']['comment'] ?>
                            :</label><?php echo $this->formTextarea('comment', $this->comment, array(
                            'cols'  => 35,
                            'rows'  => 8,
                            'class' => 'comment',
                        )); ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="groups">
                <ul>
                    <li>
                        <label for="customer_groups"><?php echo $kga['dict']['groups'] ?>
                            :</label><?php echo $this->formSelect('customer_groups[]', $this->selectedGroups, array(
                            'class'    => 'formfield',
                            'id'       => 'customer_groups',
                            'multiple' => 'multiple',
                            'size'     => 3,
                            'style'    => 'width:255px;height:100px;'), $this->groups); ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="address">
                <ul>
                    <li>
                        <label for="company"><?php echo $kga['dict']['company'] ?>
                            :</label><?php echo $this->formText('company', $this->company, array('style' => 'width:250px;')); ?>
                    </li>
                    <li>
                        <label for="contactPerson"><?php echo $kga['dict']['contactPerson'] ?>
                            :</label><?php echo $this->formText('contactPerson', $this->contact, array('style' => 'width:250px;')); ?>
                    </li>
                    <li>
                        <label for="street"><?php echo $kga['dict']['street'] ?>
                            :</label><?php echo $this->formText('street', $this->street, array('style' => 'width:250px;')); ?>
                    </li>
                    <li>
                        <label for="zipcode"><?php echo $kga['dict']['zipcode'] ?>
                            :</label><?php echo $this->formText('zipcode', $this->zipcode, array('style' => 'width:250px;')); ?>
                    </li>
                    <li>
                        <label for="city"><?php echo $kga['dict']['city'] ?>
                            :</label><?php echo $this->formText('city', $this->city, array('style' => 'width:250px;')); ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="contact">
                <ul>
                    <li>
                        <label for="phone"><?php echo $kga['dict']['telephon'] ?>
                            :</label><?php echo $this->formText('phone', $this->phone, array('style' => 'width:250px;')); ?>
                    </li>

                    <li>
                        <label for="fax"><?php echo $kga['dict']['fax'] ?>
                            :</label><?php echo $this->formText('fax', $this->fax, array('style' => 'width:250px;')); ?>
                    </li>
                    <li>
                        <label for="mobile"><?php echo $kga['dict']['mobilephone'] ?>
                            :</label><?php echo $this->formText('mobile', $this->mobile, array('style' => 'width:250px;')); ?>
                    </li>
                    <li>
                        <label for="mail"><?php echo $kga['dict']['mail'] ?>
                            :</label><?php echo $this->formText('mail', $this->mail, array('style' => 'width:250px;')); ?>
                    </li>
                    <li>
                        <label for="homepage"><?php echo $kga['dict']['homepage'] ?>
                            :</label><?php echo $this->formText('homepage', $this->homepage, array('style' => 'width:250px;')); ?>
                    </li>
                </ul>

            </fieldset>

        </div>

        <div id="formbuttons">
            <input class='btn_norm' type='button' value='<?php echo $kga['dict']['cancel'] ?>'
                   onclick='floaterClose(); return false;'/>
            <input class='btn_ok' type='submit' value='<?php echo $kga['dict']['submit'] ?>'/>
        </div>
    </form>

</div>
