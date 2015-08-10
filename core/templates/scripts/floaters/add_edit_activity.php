<script type="text/javascript">
    $(document).ready(function () {

        $('#add_edit_activity').ajaxForm({
            'beforeSubmit': function () {
                clearFloaterErrorMessages();

                if ($('#add_edit_activity').attr('submitting')) {
                    return false;
                }
                else {
                    $('#add_edit_activity').attr('submitting', true);
                    return true;
                }
            },
            'success': function (result) {
                $('#add_edit_activity').removeAttr('submitting');

                for (var fieldName in result.errors)
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);

                if (result.errors.length == 0) {
                    floaterClose();
                    hook_activities_changed();
                }
            },
            'error': function () {
                $('#add_edit_activity').removeAttr('submitting');
            }
        });

        $('#floater_innerwrap').tabs({selected: 0});
    });
</script>

<div id="floater_innerwrap">

    <div id="floater_handle">
        <span id="floater_title">
            <?php if (isset($this->id) && $this->id > 0) {
                echo $GLOBALS['kga']['dict']['edit'], ' ', $this->name;
            }
            else {
                echo $GLOBALS['kga']['dict']['add_activity'];
            }
            ?>
        </span>

        <div class="right">
            <a href="#" class="close" onclick="floaterClose();"><?php echo $GLOBALS['kga']['dict']['close'] ?></a>
        </div>
    </div>

    <div class="menuBackground">

        <ul class="menu tabSelection">
            <li class="tab norm"><a href="#general">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $GLOBALS['kga']['dict']['general'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#projectstab">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $GLOBALS['kga']['dict']['projects'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#groups">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $GLOBALS['kga']['dict']['groups'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
            <li class="tab norm"><a href="#commenttab">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $GLOBALS['kga']['dict']['comment'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li>
        </ul>
    </div>

    <form id="add_edit_activity" action="processor.php" method="post">

        <input name="activityFilter" type="hidden" value="0"/>

        <input name="axAction" type="hidden" value="add_edit_activity"/>
        <input name="id" type="hidden" value="<?php echo $this->id; ?>"/>

        <div id="floater_tabs" class="floater_content">
            <fieldset id="general">
                <ul>
                    <li>
                        <label for="name"><?php echo $GLOBALS['kga']['dict']['activity'] ?>:</label>
                        <?php echo $this->formText('name', $this->name); ?>
                    </li>
                    <li>
                        <label for="default_rate"><?php echo $GLOBALS['kga']['dict']['default_rate'] ?>:</label>
                        <?php echo $this->formText('default_rate', str_replace('.', $GLOBALS['kga']['conf']['decimal_separator'], $this->default_rate)); ?>
                    </li>
                    <li>
                        <label for="my_rate"><?php echo $GLOBALS['kga']['dict']['my_rate'] ?>:</label>
                        <?php echo $this->formText('my_rate', str_replace('.', $GLOBALS['kga']['conf']['decimal_separator'], $this->my_rate)); ?>
                    </li>
                    <li>
                        <label for="fixed_rate"><?php echo $GLOBALS['kga']['dict']['fixed_rate'] ?>:</label>
                        <?php echo $this->formText('fixed_rate', str_replace('.', $GLOBALS['kga']['conf']['decimal_separator'], $this->fixed_rate)); ?>
                    </li>
                    <li>
                        <label for="visible"><?php echo $GLOBALS['kga']['dict']['visibility'] ?>:</label>
                        <?php echo $this->formCheckbox('visible', '1', array('checked' => $this->visible || !$this->id)); ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="commenttab">
                <ul>
                    <li>
                        <label for="comment"><?php echo $GLOBALS['kga']['dict']['comment'] ?>:</label>
                        <?php echo $this->formTextarea('comment', $this->comment, array(
                            'cols'  => 30,
                            'rows'  => 5,
                            'class' => 'comment',
                        )); ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="groups">
                <ul>
                    <li>
                        <label for="activityGroups"><?php echo $GLOBALS['kga']['dict']['groups'] ?>:</label>
                        <?php echo $this->formSelect('activityGroups[]', $this->selectedGroups, array(
                            'class'    => 'formfield',
                            'id'       => 'activityGroups',
                            'multiple' => 'multiple',
                            'size'     => 3,
                            'style'    => 'width:255px;height:100px'), $this->groups); ?>
                    </li>
                </ul>
            </fieldset>

            <fieldset id="projectstab">
                <ul>
                    <li>
                        <label for="activityProjects"><?php echo $GLOBALS['kga']['dict']['projects'] ?>:</label>
                        <?php echo $this->formSelect('projects[]', $this->selectedProjects, array(
                            'class'    => 'formfield',
                            'id'       => 'activityProjects',
                            'multiple' => 'multiple',
                            'size'     => 5,
                            'style'    => 'width:255px'), $this->projects); ?>
                    </li>
                </ul>
            </fieldset>

        </div>

        <div id="formbuttons">
            <input class='btn_norm'
                   type='button'
                   value='<?php echo $GLOBALS['kga']['dict']['cancel'] ?>'
                   onclick='floaterClose(); return false;'/>
            <input class='btn_ok' type='submit' value='<?php echo $GLOBALS['kga']['dict']['submit'] ?>'/>
        </div>
    </form>
</div>
