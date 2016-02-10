<?php echo $GLOBALS['kga']['dict']['dbName']?>: <?php echo $this->escape($GLOBALS['kga']['server_database']);?>
<br />
<br />
<a href="../db_restore.php">Database Backup Utility</a>

<?php /*
<br /><br />

<?php echo $GLOBALS['kga']['dict']['lastdbbackup']?>:

<?php if ($GLOBALS['kga']['conf']['lastdbbackup']): ?>
    <?php echo strftime("%c", $GLOBALS['kga']['conf']['lastdbbackup']); ?>
<?php else: ?>
    none
<?php endif; ?>

<br />

<input class='btn_ok' type='submit' value='<?php echo $GLOBALS['kga']['dict']['runbackup']?>' onClick='backupAll(); return false;' />

*/ ?>
