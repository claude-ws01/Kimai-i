<?php global $kga ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="SHORTCUT ICON" href="favicon.ico">
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="robots" content="noindex,nofollow" />
    <title>Kimai-i Installer</title>
    <link rel="stylesheet" type="text/css" media="screen" href="css/login.css" />
</head>
<body>
    <div id='install'>
            <form action="installer/install.php" method="post">
                <fieldset>
                    <div class="installtext">
                        <div class="welcome">Welcome!</div>
                        <div class="txt">We need to set up the database first.</div>
                        <div class="txt">Due to security reasons you should erase the "install.php" file after installation!</div>   
                        <div class="txt">
                            <input type="checkbox" name="accept" value="1" style="width:15px;height:15px;display:inline;">
                            <?php if ($this->disagreedGPL):?><strong style="color:red"><?php endif; ?> I accept the terms of the GNU GPL Version 3<?php if ($this->disagreedGPL):?></strong><?php endif; ?>
                            <?php if ($this->disagreedGPL): ?><br /><img src="grfx/caution_small.png" alt="Caution" /> <span style="color:red">You have to accept the the terms!</span><?php endif; ?>
                        </div>
                    </div>
                    <button type="submit">Install</button>
                </fieldset>
            </form>
            <?php echo $this->partial('misc/copyrightnotes.php', array('kga' => &$kga, 'devtimespan' => devTimeSpan())); ?>
    </div>
</body>
</html>
