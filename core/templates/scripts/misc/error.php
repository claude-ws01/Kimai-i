<!DOCTYPE html >
<html lang="en">
<head>
    <link rel="SHORTCUT ICON" href="favicon.ico">
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="robots" content="noindex,nofollow" />
    <title>Kimai-i Error</title>
    <link rel="stylesheet" type="text/css" media="screen" href="css/error.css" />
    <script src="libraries/jQuery/jquery-1.9.1.min.js" type="text/javascript" charset="utf-8"></script>
        <script type="text/javascript"> 
            $(document).ready(function() {
                $('#ok').focus();
            }); 
        </script>
</head>
<body>
<div id="error_wrapper">
    <div id="error_txt">
        <h3><?php echo $this->headline?></h3>
        <?php echo $this->message?>
    </div>
    <div id="error_button">
        <form action="index.php" method="post"><input type="submit" value="OK" id="ok"/></form>
    </div>
</div>
</body>
</html>
