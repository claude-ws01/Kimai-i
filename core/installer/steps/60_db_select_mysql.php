<?php
echo '<script type="text/javascript" charset="utf-8">current=60;</script>';

$hostname        = isset($_REQUEST['hostname']) ? $_REQUEST['hostname'] : 'localhost';
$db_name        = isset($_REQUEST['database']) ? $_REQUEST['database'] : '';
$username        = isset($_REQUEST['username']) ? $_REQUEST['username'] : '???';
$password        = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
$prefix          = isset($_REQUEST['prefix']) ? $_REQUEST['prefix'] : "kimai_";
$language        = isset($_REQUEST['language']) ? $_REQUEST['language'] : 'en';
$create_database = isset($_REQUEST['create_database']) ? $_REQUEST['create_database'] : '';

$con = @mysqli_connect($hostname, $username, $password, $db_name);

// we could not connect to the database, show error and leave the script
if (!$con) {
    if ($language === 'de') {
        echo "Datenbank hat Zugriff verweigert. Gehen Sie bitte zurück.<br /><button onClick=\"step_back(); return false;\">Zurück</button>";
    }
    else {
        echo "The database refused access. Please go back.<br /><button onClick=\"step_back(); return false;\">Back</button>";
    }

    return;
}

// ====================================================================================================================
// if there is any error we have to show this page again, otherwise redirect to the next step
$errors = false;
ob_start();

// get permissions
$showDatabasesAllowed  = false;
$createDatabaseAllowed = false;
$result                = mysqli_query($con, 'SHOW GRANTS;');
while ($row = mysqli_fetch_row($result)) {
    if (strpos($row[0], 'SHOW DATABASES') !== false) {
        $showDatabasesAllowed = true;
    }
    else {
        if (strpos($row[0], 'CREATE,') !== false) {
            $createDatabaseAllowed = true;
        }
        else {
            if (strpos($row[0], 'ALL PRIVILEGES') !== false) {
                $showDatabasesAllowed  = true;
                $createDatabaseAllowed = true;
            }
        }
    }
}

if (!$showDatabasesAllowed) {
    if ($language === 'de') {
        echo "Kein Berechtigung um Datenbanken aufzulisten. Name der zu verwendenden Datenbank:<br/>";
    }
    else {
        echo "No permission to list databases. Name of the database to use:<br/>";
    }

    echo '<input type="text" id="db_names" value="' . $db_name . '"/>';

    if (($db_name !== '' && $create_database === '') && !mysqli_select_db($con, $db_name)) {
        $errors = true;
        if ($language === 'de') {
            echo '<strong id="db_select_label" class="arrow">Diese Datenbank konnte nicht geöffnet werden.</strong>';
        }
        else {
            echo '<strong id="db_select_label" class="arrow">Unable to open that database.</strong>';
        }
    }
    else {
        echo '<strong id="db_select_label"></strong>';
    }

    echo '<br/><br/>';

}
else {

    // read existing databases
    $result        = mysqli_query($con, 'SHOW DATABASES');
    $db_connection = array();
    while ($row = mysqli_fetch_row($result)) {
        if (($row[0] !== 'information_schema') && ($row[0] !== 'mysql')) {
            $db_connection[] = $row[0];
        }
    }

    if (!is_array($db_connection) || count($db_connection) === 0) {
        if ($language === 'de') {
            echo 'Keine Datenbank(en) vorhanden.<br/><br/>';
        }
        else {
            echo 'No database(s) found.<br/><br/>';
        }
    }
    else {
        // if there are databases build selectbox

        if ($language === 'de') {
            echo 'Bitte wählen Sie eine Datenbank:';
        }
        else {
            echo 'Please choose a database:';
        }

        echo '<br/><select id="db_names">';
        echo '<option value=""></option>';

        foreach ($db_connection as $dbname) {
            if ($db_name === $dbname) {
                echo "<option selected='selected' value='$dbname'>$dbname</option>";
            }
            else {
                echo "<option value='$dbname'>$dbname</option>";
            }
        }

        echo "</select> <strong id='db_select_label'></strong><br/><br/>";
    }
}

if ($createDatabaseAllowed) {

    if ($db_name === '' && $create_database !== '') {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $create_database)) {
            $databaseErrorMessage = ($language === 'de') ? 'Nur Buchstaben, Zahlen und Unterstriche.'
                : 'Only letters, numbers and underscores.';
        }
        elseif (strlen($create_database) > 64) {
            $databaseErrorMessage = ($language === 'de') ? 'Maximal 64 Zeichen.' : 'At most 64 characters.';
        }
        elseif (mysqli_select_db($con, $create_database)) {
            $databaseErrorMessage = ($language === 'de') ? 'Datenbank existiert bereits.' : 'Database already exists.';
        }
    }

    if ($language === 'de') {
        echo "Neue Datenbank anlegen: (der angegebene DB-Nutzer muss die entspr. Rechte besitzen!)<br/><input id='db_create' type='text' value='$create_database'/>";
    }
    else {
        echo "Create a blank database: (the db-user you entered must have appropriate rights!)<br/><input id='db_create' type='text' value='$create_database'/>";
    }

    if (isset($databaseErrorMessage)) {
        $errors = true;
        echo "<strong id='db_create_label' class='arrow'>$databaseErrorMessage</strong><br/><br/>";
    }
    else {
        echo '<strong id="db_create_label"></strong><br/><br/>';

    }

}
else {
    echo '<input id="db_create" type="hidden" value=""/>';
}

if ($db_name !== '' && $create_database !== '') {
    $errors = true;
    if ($language = 'de') {
        echo '<strong class="fail">Wählen sie entweder eine Datenbank aus oder geben sie eine Neue an, aber nicht beides.</strong><br/><br/>';
    }
    else {
        echo '<strong class="fail">Either choose a database or give a new one, but not both.</strong><br/><br/>';
    }
}

// Table prefix
if ($prefix !== 'kimai' && strlen($prefix) > 0 && !preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
    $errors             = true;
    $prefixErrorMessage = ($language == 'de') ? 'Nur Buchstaben, Zahlen und Unterstriche.'
        : 'Only letters, numbers and underscores.';
}
if ($prefix !== 'kimai' && strlen($prefix) > 64) {
    $errors             = true;
    $prefixErrorMessage = ($language === 'de') ? 'Maximal 64 Zeichen.' : 'At most 64 characters.';
}

if ($language === 'de') {
    echo "Möchten Sie einen Tabellen-Prefix vergeben?<br/>(Wenn Sie nicht wissen was das ist, lassen Sie einfach 'kimai_' stehen...)<br/><input id='prefix' type='text' value='$prefix'/>";
}
else {
    echo "Would you like to assign a table-prefix?<br/>(If you don't know what this is - leave it as 'kimai_'...)<br/><input id='prefix' type='text' value='$prefix'/>";
}

if (isset($prefixErrorMessage)) {
    echo "<strong id='prefix_label' class='arrow'>$prefixErrorMessage</strong><br/><br/>";
}
else {
    echo "<strong id='prefix_label'></strong><br/><br/>";
}

echo '<br/><br/>';

if ($language === 'de') {
    echo '<button onClick="step_back(); return false;">Zurück</button>
            <button style="float:right;" onClick="db_check(); return false;" class="proceed">Fortfahren</button>';
}
else {
    echo '<button onClick=\"step_back(); return false;\">Back</button>
            <button style="float:right;" onClick="db_check(); return false;" class="proceed"">Proceed</button>';
}

if (($db_name === '' && $create_database === '') || $errors || !isset($_REQUEST['redirect'])) {
    echo ob_get_clean();
}
else {
    echo '<script type="text/javascript" charset="utf-8">db_proceed();</script>';
}

mysqli_close($con);
