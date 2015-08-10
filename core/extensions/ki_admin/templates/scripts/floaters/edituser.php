<?php
/** @var $this Zend_View_Helper_FormSelect */

global $kga; ?>
<script type="text/javascript">
    $(document).ready(function () {

        var options = {
            beforeSubmit: function () {
                var oldGlobalRoleID = '',
                    password = $('#password'),
                    retypePassword = $('#retypePassword'),
                    message = "<?php echo $this->pureJsEscape($kga['dict']['ownGlobalRoleChange']); ?>",
                    form = $('#adm_ext_form_editUser');

                oldGlobalRoleID = <?php echo $this->user_details['global_role_id']; ?>;


                if ($('#global_role_id').val() != oldGlobalRoleID && $('input[name="id"]').val() == user_id) {
                    message = message.replace(/%OLD%/, $("#global_role_id>option[value='" + oldGlobalRoleID + "']").text());
                    message = message.replace(/%NEW%/, $("#global_role_id>option:selected").text());
                    var accepted = confirm(message);

                    if (!accepted)
                        return false;
                }

                //workaround - some browser automaticaly fill the password
                if (password.val() == '' || retypePassword.val() == '') {
                    password.value = '';
                    retypePassword.value = '';
                }
                else if (password.val() != '' && !validatePassword(password.val(), retypePassword.val())) {
                    return false;
                }

                clearFloaterErrorMessages();

                if (form.attr('submitting')) {
                    return false;
                }
                else {
                    form.attr('submitting', true);
                    return true;
                }
            },
            success: function (result) {
                $('#adm_ext_form_editUser').removeAttr('submitting');

                for (var fieldName in result.errors)
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);

                if (result.errors.length == 0) {
                    hook_users_changed();
                    adm_ext_refreshSubtab('groups');
                    floaterClose();
                }

                return false;
            },
            'error': function () {
                $('#adm_ext_form_editUser').removeAttr('submitting');
            }
        };

        var memberships = <?php echo json_encode($this->membershipRoles); ?>;

        $('#adm_ext_form_editUser').ajaxForm(options);


        function deleteButtonClicked() {
            var row = $(this).parent().parent()[0];
            var id = $('#groupsTable', row).val();
            var text = $('td', row).first().text().trim();
            $('#newGroup').append('<option label = "' + text + '" value = "' + id + '">' + text + '</option>');
            $(row).remove();

            if ($('#newGroup option').length > 1)
                $('#groupstab .addRow').show();
        }

        $('#groupstab .deleteButton').click(deleteButtonClicked);

        $('#newGroup').change(function () {
            if ($(this).val() == -1) return;

            var membershipRoleSelect = '<select name="membershipRoles[]">';
            $.each(memberships, function (key, value) {
                membershipRoleSelect += '<option value="' + key + '">' + value + '</option>';
            });
            membershipRoleSelect += '</select>';

            var row = $('<tr>' +
                '<td>' + $('option:selected', this).text() + '<input type="hidden" name="assignedGroups[]" value="' + $(this).val() + '"/></td>' +
                '<td>' + membershipRoleSelect + '</td>' +
                '<td> <a class="deleteButton">' +
                '<img src="../skins/' + skin + '/grfx/close.png" width="22" height="16" />' +
                '</a> </td>' +
                '</tr>');
            $('#groupstab .groupsTable tr.addRow').before(row);
            $('.deleteButton', row).click(deleteButtonClicked);

            $('option:selected', this).remove();

            $(this).val(-1);

            if ($('option', this).length <= 1)
                $('#groupstab .addRow').hide();
        });

        $('#floater_innerwrap').tabs({selected: 0});

        // uniform will mess up cloning select elements, which already are "uniformed"
        // maybe the issue is the same? https://github.com/pixelmatrix/uniform/pull/138
//               $("select, input:checkbox, input:radio, input:file").uniform();
        var optionsToRemove = [], len;
        $('select.groups').each(function (index) {
            if ($(this).val() != '') {
                $(this).children('[value=""]').remove();
                optionsToRemove.push($(this).val());
            }
        });
        for (var i = 0, len = optionsToRemove.length; i < len; i++) {
            $('.groups option[value="' + optionsToRemove[i] + '"]').not(':selected').remove();
        }
        var previousValue;
        var previousText;
        $('.groups').on('focus', function () {
            previousValue = this.value;
            previousText = $(this).children('[value="' + previousValue + '"]').text();
        }).on('change', function () {
            if (previousValue != '') {
                // the value we "deselected" has to be added to all other dropdowns to select it again
                $('.groups').each(function (index) {
                    if ($(this).children('[value="' + previousValue + '"]').length == 0) {
                        $(this).append('<option label="' + previousText + '" value="' + previousValue + '">' + previousText + '</option>');
                    }
                });
            }
            // add a new one if the value is in the last field, the value is not empty and there are more options to choose from
            if ($(this).val() != '' && $(this).closest('tr').next().length <= 0 && $(this).children().length > 2) {
                var label = $(this).val();
                $(this).children('[value=""]').remove();
                var tr = $(this).closest('tr');
                var newSelect = tr.clone();
                newSelect.find('select').prepend('<option value=""></option>');
                newSelect.find('select').val('');
                newSelect.find('option[value="' + label + '"]').remove();
                tr.after(newSelect);
            }
            return true;
        });

    });
</script>

<div id="floater_innerwrap">

    <div id="floater_handle">
        <span id="floater_title"><?php echo $kga['dict']['edit'], '&nbsp;&nbsp;'; ?><?php echo $this->escape($this->user_details['name']) ?></span>

        <div class="right">
            <a href="#" class="close" onClick="floaterClose();"><?php echo $kga['dict']['close'] ?></a>
        </div>
    </div>

    <div class="menuBackground">

        <ul class="menu tabSelection">
            <li class="tab norm"><a href="#general">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['general'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#groupstab">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['groups'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
        </ul>
    </div>

    <div class="floater_content">

        <form id="adm_ext_form_editUser" action="../extensions/ki_admin/processor.php" method="post">
            <fieldset id="general">

                <ul>

                    <li>
                        <label for="name"><?php echo $kga['dict']['username'] ?>:</label>
                        <input class="formfield"
                               type="text"
                               id="name"
                               name="name"
                               value="<?php echo $this->escape($this->user_details['name']) ?>"
                               maxlength="20"
                               size="20"/>
                    </li>

                    <li>
                        <label for="global_role_id"><?php echo $kga['dict']['globalRole'] ?>
                            :</label><?php echo $this->formSelect('global_role_id', $this->user_details['global_role_id'], array(
                            'class' => 'formfield', 'style' => 'min-width:12em;'), $this->globalRoles); ?>
                    </li>

                    <li>
                        <label for="password"><?php echo $kga['dict']['new_password'] ?>:</label>
                        <input class="formfield" type="text" id="password" name="password" size="9"
                               value=""/> <?php echo sprintf($kga['dict']['minLength'], $kga['pwdMinLength']) ?>
                        <?php if ($this->user_details['password'] === ''): ?>

                            <br/><img
                                src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/caution_mini.png"
                                alt="Caution" style="vertical-align:middle;"/><strong
                                style="color:red;"><?php echo $kga['dict']['nopasswordset'] ?></strong><?php endif; ?>
                    </li>

                    <li>
                        <label for="retypePassword"><?php echo $kga['dict']['retypePassword'] ?>:</label>
                        <input class="formfield" type="text" id="retypePassword" name="retypePassword" size="9"/>
                    </li>

                    <li>
                        <label for="rate"><?php echo $kga['dict']['rate_hourly'] ?>:</label>
                        <input class="formfield" type="text" id="rate" name="rate" size="5"
                               style="text-align:right;padding-right:5px;"
                               value="<?php
                               echo $this->escape(str_replace('.', $kga['conf']['decimal_separator'],
                                                              $this->user_details['rate']));
                               ?>"/><?php echo ' ', $kga['conf']['currency_sign']; ?>
                    </li>

                    <li>
                        <label for="mail"><?php echo $kga['dict']['mail'] ?>:</label>
                        <input class="formfield" type="text" id="mail" name="mail"
                               value="<?php echo $this->escape($this->user_details['mail']) ?>"/>
                    </li>

                    <li>
                        <label for="alias"><?php echo $kga['dict']['alias'] ?>:</label>
                        <input class="formfield" type="text" id="alias" name="alias"
                               value="<?php echo $this->escape($this->user_details['alias']) ?>"/>
                    </li>

                </ul>
            </fieldset>
            <fieldset id="groupstab">

                <table class="groupsTable">
                    <tr>
                        <td><label style="text-align:left;"><?php echo $kga['dict']['in_group'] ?>:</label></td>
                        <td><label style="text-align:left;"><?php echo $kga['dict']['membershipRole'] ?>:</label></td>
                    </tr><?php

                    $selectArray    = array(-1 => '');
                    $assignedGroups = array();
                    foreach ($this->groups as $group) {
                        if (array_key_exists($group['group_id'], $this->memberships)) {
                            $group['membership_role_id'] = $this->memberships[$group['group_id']];
                            $assignedGroups[]            = $group;
                        }
                        else {
                            $selectArray[$group['group_id']] = $group['name'];
                        }
                    }

                    foreach ($assignedGroups as $assignedGroup) {
                        ?>
                        <tr>
                        <td>
                            <?php echo $this->escape($assignedGroup['name']), $this->formHidden('assignedGroups[]', $assignedGroup['group_id']); ?>
                        </td>
                        <td>
                            <?php echo $this->formSelect('membershipRoles[]', $assignedGroup['membership_role_id'], array('size' => 1, 'multiple' => false), $this->membershipRoles); ?>
                        </td>
                        <td>
                            <a class="deleteButton">
                                <img
                                    src="../skins/<?php echo $this->escape($kga['pref']['skin']) ?>/grfx/close.png"
                                    width="22" height="16"/>
                            </a>
                        </td>
                        </tr><?php
                    }
                    ?>
                    <tr class="addRow" <?php if (count($selectArray) <= 1): ?> style="display:none" <?php endif; ?> >
                        <td> <?php
                            echo $this->formSelect('newGroup', null, null, $selectArray); ?> </td>
                    </tr>
                </table>
            </fieldset>

            <input name="id" type="hidden" value="<?php echo $this->user_details['user_id'] ?>"/>
            <input name="axAction" type="hidden" value="sendEditUser"/>

            <div id="formbuttons">
                <input class='btn_norm' type='button' value='<?php echo $kga['dict']['cancel'] ?>'
                       onClick='floaterClose(); return false;'/>
                <input class='btn_ok' type='submit' value='<?php echo $kga['dict']['submit'] ?>'/>
            </div>
        </form>
    </div>
</div>
