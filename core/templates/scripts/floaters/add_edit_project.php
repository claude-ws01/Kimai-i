<?php global $kga ?>
<script type="text/javascript">

    $(document).ready(function () {

        $('#addProject').ajaxForm({
            'beforeSubmit': function () {

                var addProject = $('#addProject');
                clearFloaterErrorMessages();

                if (addProject.attr('submitting')) {
                    return false;
                }
                else {
                    $("#ajax_wait").show();
                    addProject.attr('submitting', true);
                    return true;
                }
            },

            'success': function (result) {
                $('#addProject').removeAttr('submitting');
                $("#ajax_wait").hide();

                for (var fieldName in result.errors)
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);

                if (result.errors.length == 0) {
                    floaterClose();
                    hook_projects_changed();
                    hook_activities_changed();
                }
            },

            'error': function () {
                $('#addProject').removeAttr('submitting');
                $("#ajax_wait").hide();
            }
        });


        function deleteButtonClicked() {

            var row = $(this).parent().parent()[0],
                id = $('#assignedActivities', row).val(),
                text = $('td', row).text().trim(),
                aOption = $('#newActivity option'),
                addRow = $('#activitiestab .addRow');

            $('#newActivity').append('<option label = "' + text + '" value = "' + id + '">' + text + '</option>');
            $(row).remove();

            if (aOption.length > 1)
                addRow.show();
        }

        $('#activitiestab .deleteButton').click(deleteButtonClicked);

        $('#newActivity').change(function () {

            if ($(this).val() == -1) return;

            var row = $('<tr>' +
                '<td>' + $('option:selected', this).text() + '<input type="hidden" name="assignedActivities[]" value="' + $(this).val() + '"/></td>' +
                '<td><input type="text" name="budget[]"/></td>' +
                '<td><input type="text" name="effort[]"/></td>' +
                '<td><input type="text" name="approved[]"/></td>' +
                '<td> <a class="deleteButton">' +
                '<img src="../skins/' + skin + '/grfx/close.png" width="22" height="16" />' +
                '</a> </td>' +
                '</tr>');
            $('#activitiestab .activitiesTable tr.addRow').before(row);
            $('.deleteButton', row).click(deleteButtonClicked);

            $('option:selected', this).remove();

            $(this).val(-1);

            if ($('option', this).length <= 1)
                $('#activitiestab .addRow').hide();
        });

        $('#floater_innerwrap').tabs({selected: 0});

        var optionsToRemove = [];
        $('select.activities').each(function (index) {
            if ($(this).val() != '') {
                $(this).children('[value=""]').remove();
                optionsToRemove.push($(this).val());
            }
        });
        var len, i;
        for (i = 0, len = optionsToRemove.length; i < len; i++) {
            $('.activities option[value="' + optionsToRemove[i] + '"]').not(':selected').remove();
        }
        var previousValue;
        var previousText;
        $('.activities').on('focus', function () {

            previousValue = this.value;
            previousText = $(this).children('[value="' + previousValue + '"]').text();

        }).on('change', function () {

            if (previousValue != '') {

                // the value we "deselected" has to be added to all other dropdowns to select it again
                $('.activities').each(function (index) {
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
        <span id="floater_title">
            <?php if (isset($this->id) && (int)$this->id > 0) {
                echo $kga['dict']['edit'], ' ', $this->name;
            }
            else {
                echo $kga['dict']['new_project'];
            }
            ?>
        </span>

        <div class="right"><a href="#" class="close"
                              onclick="floaterClose();"><?php echo $kga['dict']['close'] ?></a>
        </div>
    </div>

    <div class="menuBackground">

        <ul class="menu tabSelection">
            <li class="tab norm"><a href="#general"> <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['general'] ?></span> <span
                        class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#money"> <span class="aa">&nbsp;</span> <span
                        class="bb"><?php echo $kga['dict']['budget'] ?></span> <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#activitiestab"> <span class="aa">&nbsp;</span> <span
                        class="bb"><?php echo $kga['dict']['activities'] ?></span> <span
                        class="cc">&nbsp;</span> </a></li>
            <li class="tab norm"><a href="#groups"> <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['groups'] ?></span> <span
                        class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#comment"> <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['dict']['comment'] ?></span> <span
                        class="cc">&nbsp;</span>
                </a></li>
        </ul>
    </div>

    <form id="addProject" action="processor.php" method="post">
        <input name="project_filter" type="hidden" value="0"/>
        <input name="axAction" type="hidden" value="add_edit_CustomerProjectActivity"/>
        <input name="axValue" type="hidden" value="project"/>
        <input name="id" type="hidden" value="<?php echo $this->id ?>"/>

        <div id="floater_tabs" class="floater_content">

            <fieldset id="general">

                <ul>

                    <li><label for="name"><?php echo $kga['dict']['project'] ?>:</label><?php
                        echo $this->formText('name', $this->name, array('style' => 'width:250px;',)); ?> </li>

                    <li><label for="customer_id"><?php echo $kga['dict']['customer'] ?>:</label> <?php
                        echo $this->formSelect('customer_id', $this->selectedCustomer, array('class' => 'formfield'), $this->customers); ?>
                    </li>

                    <li><label for="visible" title="<?php echo $kga['dict']['tip']['pe_visibility'] ?>">
                            <?php echo $kga['dict']['visibility'] ?>:</label><?php
                        echo $this->formCheckbox('visible', '1', array('checked' => $this->visible || !$this->id,
                                                                       'title'   => $kga['dict']['tip']['pe_visibility'])); ?>
                    </li>

                    <li><label for="internal" title="<?php echo $kga['dict']['tip']['pe_internal'] ?>">
                            <?php echo $kga['dict']['internalProject'] ?>:</label><?php
                        echo $this->formCheckbox('internal', '1', array('checked' => $this->internal,
                                                                        'title'   => $kga['dict']['tip']['pe_internal'])); ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="money">

                <ul>
                    <li><label for="default_rate"><?php echo $kga['dict']['default_rate'] ?>:</label><?php
                        echo $this->formText('default_rate', str_replace('.', $kga['conf']['decimal_separator'], $this->default_rate));
                        echo ' ', $kga['conf']['currency_sign'] ?>
                    </li>

                    <li><label for="my_rate"><?php echo $kga['dict']['my_rate'] ?>:</label><?php
                        echo $this->formText('my_rate', str_replace('.', $kga['conf']['decimal_separator'], $this->my_rate));
                        echo ' ', $kga['conf']['currency_sign'] ?>
                    </li>

                    <li><label for="fixed_rate"><?php echo $kga['dict']['fixed_rate'] ?>:</label><?php
                        echo $this->formText('fixed_rate', str_replace('.', $kga['conf']['decimal_separator'], $this->fixed_rate));
                        echo ' ', $kga['conf']['currency_sign'] ?>
                    </li>

                    <li><label for="project_budget"><?php echo $kga['dict']['budget'] ?>:</label><?php
                        echo $this->formText('project_budget', str_replace('.', $kga['conf']['decimal_separator'], $this->budget));
                        echo ' ', $kga['conf']['currency_sign'] ?>
                    </li>

                    <li><label for="project_effort"><?php echo $kga['dict']['effort'] ?>:</label><?php
                        echo $this->formText('project_effort', str_replace('.', $kga['conf']['decimal_separator'], $this->effort));
                        echo ' ', $kga['conf']['currency_sign'] ?>
                    </li>

                    <li><label for="project_approved"><?php echo $kga['dict']['approved'] ?>:</label><?php
                        echo $this->formText('project_approved', str_replace('.', $kga['conf']['decimal_separator'], $this->approved));
                        echo ' ', $kga['conf']['currency_sign'] ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="activitiestab">
                <table class="activitiesTable">
                    <tr>
                        <td><label for="assignedActivities"
                                   style="text-align: left; float:left; margin:0;"><?php echo $kga['dict']['activities'] ?></label>
                        </td>
                        <td>
                            <label for="budget"><?php echo $kga['dict']['budget'], ' ', $kga['conf']['currency_sign'] ?></label>
                        </td>
                        <td>
                            <label for="effort"><?php echo $kga['dict']['effort'], ' ', $kga['conf']['currency_sign'] ?></label>
                        </td>
                        <td>
                            <label for="approved"><?php echo $kga['dict']['approved'], ' ', $kga['conf']['currency_sign'] ?></label>
                        </td>
                    </tr><?php
                    $assignedActivities = array();
                    if (isset($this->selectedActivities) && is_array($this->selectedActivities)) {
                        foreach ($this->selectedActivities as $selectedActivity) {
                            $assignedActivities[] = $selectedActivity['activity_id'];
                            ?>
                            <tr>
                            <td>
                                <?php echo $this->escape($selectedActivity['name']), $this->formHidden('assignedActivities[]', $selectedActivity['activity_id']); ?>
                            </td>
                            <td>
                                <?php echo $this->formText('budget[]', $selectedActivity['budget']) ?>
                            </td>
                            <td>
                                <?php echo $this->formText('effort[]', $selectedActivity['effort']); ?>
                            </td>
                            <td>
                                <?php echo $this->formText('approved[]', $selectedActivity['approved']); ?>
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
                    }

                    $selectArray = array(-1 => '');
                    foreach ($this->allActivities as $activity) {
                        if (in_array($activity['activity_id'], $assignedActivities, false) === false) {
                            $selectArray[$activity['activity_id']] = $activity['name'];
                        }
                    }
                    ?>
                    <tr class="addRow" <?php if (count($selectArray) <= 1): ?> style="display:none;" <?php endif; ?> >
                        <td> <?php
                            echo $this->formSelect('newActivity', null, null, $selectArray); ?> </td>
                    </tr>
                </table>
            </fieldset>

            <fieldset id="groups">
                <ul>
                    <li>
                        <label for="projectGroups"><?php echo $kga['dict']['groups'] ?>:</label>
                        <?php echo $this->formSelect('projectGroups[]', $this->selectedGroups, array(
                            'class'    => 'formfield',
                            'id'       => 'projectGroups',
                            'multiple' => 'multiple',
                            'size'     => 3,
                            'style'    => 'width:255px;height:100px'), $this->groups); ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="comment">
                <ul>
                    <li>
                        <label for="project_comment"><?php echo $kga['dict']['comment'] ?>:</label>
                        <?php echo $this->formTextarea('project_comment', $this->comment, array(
                            'cols'  => 30,
                            'rows'  => 5,
                            'class' => 'comment',
                        )); ?>
                    </li>
                </ul>
            </fieldset>

        </div>

        <div id="formbuttons">
            <input class='btn_norm' type='button'
                   value='<?php echo $kga['dict']['cancel'] ?>'
                   onclick='floaterClose(); return false;'/> <input class='btn_ok' type='submit'
                                                                    value='<?php echo $kga['dict']['submit'] ?>'/>
        </div>
    </form>
</div>
