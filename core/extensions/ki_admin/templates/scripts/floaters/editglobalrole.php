<?php
global $kga;
$extensions   = array();
$keyHierarchy = array();

$this->getHelper('ParseHierarchy')->parseHierarchy($this->permissions, $extensions, $keyHierarchy);
?>
<script type="text/javascript">
    $(document).ready(function () {

        $('#adm_ext_form_editRole').ajaxForm({

            'beforeSubmit': function () {
                var editRole = $('#adm_ext_form_editRole');

                clearFloaterErrorMessages();
                if (editRole.attr('submitting')) {
                    return false;
                }
                else {
                    $("#ajax_wait").show();
                    editRole.attr('submitting', true);
                    return true;
                }
            },

            'success': function (result) {

                $('#adm_ext_form_editRole').removeAttr('submitting');
                $("#ajax_wait").hide();

                for (var fieldName in result.errors)
                    setFloaterErrorMessage(fieldName, result.errors[fieldName]);

                if (result.errors.length == 0) {
                    floaterClose();
                    adm_ext_refreshSubtab('<?php echo $this->jsEscape($this->reloadSubtab); ?>');
                }
            },

            'error': function () {
                $('#adm_ext_form_editRole').removeAttr('submitting');
                $("#ajax_wait").hide();
            }
        });
        $('#floater_innerwrap').tabs({selected: 0});
    });
</script>

<div id="floater_innerwrap">

    <div id="floater_handle">
        <span id="floater_title"><?php echo $this->title ?></span>

        <div class="right">
            <a href="#" class="close" onClick="floaterClose();"><?php echo $kga['lang']['close'] ?></a>
        </div>
    </div>

    <div class="menuBackground">

        <ul class="menu tabSelection">

            <li class="tab norm"><a href="#general">
                    <span class="aa">&nbsp;</span>
                    <span class="bb"><?php echo $kga['lang']['general'] ?></span>
                    <span class="cc">&nbsp;</span>
                </a></li><?php
            foreach ($keyHierarchy as $key => $subKeys) {
                if (array_key_exists('access', $subKeys) && count($subKeys) === 1) {
                    continue;
                }

                $name = $key;
                if (isset($kga['lang']['extensions'][$name])) {
                    $name = $kga['lang']['extensions'][$name];
                }
                ?>
                <li class="tab norm"><a href="#<?php echo $key ?>">
                        <span class="aa">&nbsp;</span>
                        <span class="bb"><?php echo $name ?></span>
                        <span class="cc">&nbsp;</span>
                    </a></li>

            <?php } ?>
        </ul>
    </div>

    <form id="adm_ext_form_editRole" action="../extensions/ki_admin/processor.php" method="post">
        <input name="id" type="hidden" value="<?php echo $this->id ?>"/>
        <input name="axAction" type="hidden" value="<?php echo $this->action; ?>"/>

        <div id="floater_tabs" class="floater_content">
            <fieldset id="general">
                <ul>
                    <li>
                        <label for="name"><?php echo $kga['lang']['rolename'] ?>:</label>
                        <input class="formfield" type="text" name="name"
                               value="<?php echo $this->escape($this->name) ?>"/>
                    </li>
                </ul>

                <fieldset class="floatingTabLayout">
                    <?php if (count($extensions) > 0): ?>
                        <legend><?php echo $kga['lang']['extensionsTitle']; ?></legend><?php
                    endif;
                    foreach ($extensions as $key => $value) {
                        $name = $key;
                        if (isset($kga['lang']['extensions'][$name])) {
                            $name = $kga['lang']['extensions'][$name];
                        } ?>
                        <span class="permission">
                        <input type="checkbox" value="1" name="<?php echo $key ?>__access"
                        <?php if ($value === 1) { ?>checked="checked" <?php } ?> /><?php echo $name ?>
                        </span>
                    <?php } ?>
                </fieldset>
            </fieldset>

            <?php $this->echoHierarchy($kga, $keyHierarchy); ?>
        </div>
        <div id="formbuttons">
            <input class='btn_norm' type='button' value='<?php echo $kga['lang']['cancel'] ?>'
                   onClick='floaterClose(); return false;'/>
            <input class='btn_ok' type='submit' value='<?php echo $kga['lang']['submit'] ?>'/>
        </div>
    </form>
</div>
