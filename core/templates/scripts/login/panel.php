<?php global $kga; ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <link rel="SHORTCUT ICON" href="favicon.ico">
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="robots" content="noindex,nofollow"/>
    <title>Kimai-i <?php echo $kga['dict']['login'] ?></title>
    <link rel="stylesheet" type="text/css" media="screen" href="css/login.css"/>
    <script type="text/javascript" src="libraries/jQuery/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="libraries/jQuery/jquery.cookie<?php echo DEBUG_JS ?>.js"></script>

    <?php if ($kga['conf']['check_at_startup']): ?>
        <script type="text/javascript" src="js/main<?php echo DEBUG_JS ?>.js"></script>
    <?php endif; ?>

    <script type='text/javascript'>
        $(function () {
            var kimaiCookietest,
                warning = $("#warning");

            //$("#JSwarning").remove();
            $.cookie('KimaiCookietest', 'jes',";path=/");
            kimaiCookietest = $.cookie('KimaiCookietest');
            if (kimaiCookietest == 'jes') {
                $("#cookiewarning").remove();
                $.cookie('KimaiCookietest', '', {expires: -1},";path=/");
            }
            if (!warning.find("p").size()) warning.remove();

            $("#forgotPasswordLink").click(function (event) {
                event.preventDefault();
                $("#login").fadeOut();
                $("#forgotPasswordUsername").val("");
                $("#forgotPassword").fadeIn();
                return false;
            });

            $("#resetPassword").click(function () {
                var forgot = $("#forgotPasswordUsername"),
                    confirm = $("#forgotPasswordConfirmation");

                event.preventDefault();
                forgot.blur();
                $.ajax({
                    type: "POST",
                    url: "processor.php?a=forgotPassword",
                    data: {
                        name: forgot.val()
                    },
                    dataType: "json",
                    success: function (data) {
                        $("#forgotPassword").fadeOut();
                        confirm.find("p").html(data.message);
                        confirm.fadeIn();
                    }
                });
                return false;
            });

            $(".returnToLogin").click(function (event) {
                event.preventDefault();
                $("#login").fadeIn();
                $("#forgotPassword").fadeOut();
                $("#forgotPasswordConfirmation").fadeOut();
                return false;
            });

            $("#login_name").focus();
        });

        function chgLanguage(sel) {
            var httpHost =
                '<?php echo $kga['https'] ? 'https://' : 'http://',
                    $_SERVER['SERVER_NAME'], '/'; ?>';

            window.location.href = httpHost + 'index.php?a=chg_lang&language=' + sel.value;
        }
    </script>

    <?php if ($kga['conf']['check_at_startup']) { ?>
        <script type='text/javascript'>
            $(function () {
                checkupdate("core/");
            });
        </script>
    <?php } ?>

    <?php
    $lang_options = '';
    foreach (Translations::langs() as $lang) {
        $lang_options .= '<option value="' . $lang . '" label="' . $lang . '"';
        if ($lang === $kga['language']) {
            $lang_options .= ' selected="selected';
        }
        $lang_options .= '">' . $lang . '</option>';
    }
    ?>
</head>
<body>
<div id="content">
    <div id="box">
        <div id="login" style="display:block;">
            <form id="form0" action="index.php?a=chg_lang" method="post">
                <div>
                    <label for="login_language"><?php echo $kga['dict']['lang'] ?>:</label>
                    <select id="login_language" onchange="chgLanguage(this)" name="language">
                        <?php echo $lang_options; ?>
                    </select>
                </div>
            </form>
            <form action="index.php?a=checklogin" id='form1' method='post'>
                <fieldset>
                    <div>
                        <label for="login_name"><?php echo $kga['dict']['username'] ?>:</label>
                        <input id="login_name" type='text' name="name"/>
                    </div>
                    <div>
                        <label for="login_password"><?php echo $kga['dict']['password'] ?>:</label>
                        <input id="login_password" type='password' name="password"/>
                    </div>
                    <button id="loginButton" type='submit'></button>
                </fieldset>
            </form>
            <a id="forgotPasswordLink" href=""><?php echo $kga['dict']['forgotPassword'] ?></a>
            <?php if (defined('DEMO_MODE') && DEMO_MODE) { ?>
                <span class="demo"><?php echo $kga['dict']['demo_login']; ?></span>
            <?php } ?>
        </div>
        <div id="forgotPassword">
            <?php echo $kga['dict']['passwordReset']['instructions']; ?>
            <form action="">
                <fieldset>
                    <div style="width:100%;margin:5px 0;">
                        <label for="forgot"><?php echo $kga['dict']['username'] ?>:</label>
                        <input id="forgot" type='text' name="name"/>
                    </div>
                    <div style="width:100%;margin:5px 0;">
                        <button id="resetPassword" type='submit'><?php echo $kga['dict']['reset_password'] ?></button>
                    </div>
                </fieldset>
            </form>
            <a class="returnToLogin" href=""><?php echo $kga['dict']['passwordReset']['returnToLogin'] ?></a>
        </div>
        <div id="forgotPasswordConfirmation">
            <p></p>
            <a class="returnToLogin" href=""><?php echo $kga['dict']['passwordReset']['returnToLogin'] ?></a>
        </div>
        <div id="warning">
            <p id="JSwarning"><strong style="color:red;"><?php $kga['dict']['JSwarning'] ?></strong></p>

            <p id="cookiewarning"><strong style="color:red;"><?php $kga['dict']['cookiewarning'] ?></strong></p>
        </div>
    </div>
    <?php echo $this->partial('misc/copyrightnotes.php', array('kga' => $kga, 'devtimespan' => devTimeSpan())); ?>
</div>
</body>
</html>
