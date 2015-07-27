<?php echo $GLOBALS['kga']['lang']['DBname']?>: <?php echo $this->escape($GLOBALS['kga']['server_database']);?>
<br />
<br />
<a href="../db_restore.php">Database Backup Utility</a>

<?php /*
<br /><br />

<?php echo $GLOBALS['kga']['lang']['lastdbbackup']?>:

<?php if ($GLOBALS['kga']['conf']['lastdbbackup']): ?>
    <?php echo strftime("%c", $GLOBALS['kga']['conf']['lastdbbackup']); ?>
<?php else: ?>
    none
<?php endif; ?>

<br />

<input class='btn_ok' type='submit' value='<?php echo $GLOBALS['kga']['lang']['runbackup']?>' onClick='backupAll(); return false;' />

*/ ?>
