<?php

function roleTable_createQuery($tableName, $idColumnName, $permissions)
{
    global $kga;

    $p = $kga['server_prefix'];

    $query =
        "create table `${p}${tableName}` (
          `${idColumnName}` INT(10) UNSIGNED not null auto_increment primary key,
          `name` VARCHAR(40) not null,";

    $permissionColumnDefinitions = array();
    foreach ($permissions as $permission) {
        $permissionColumnDefinitions[] = "`{$permission}` TINYINT(1) UNSIGNED DEFAULT 0";
    }
    $query .= implode(', ', $permissionColumnDefinitions);

    $query .= ') ENGINE = InnoDB auto_increment = 1';

    return $query;
}

function roleData_insertQuery($tableName, $roleName, $allowedPermissions)
{
    global $kga;

    $p = $kga['server_prefix'];

    foreach ($allowedPermissions as &$permission) {
        $permission = "`{$permission}`";
    }
    unset($permission);

    if (!is_array($allowedPermissions) || count($allowedPermissions) === 0) {
        $query =
            "insert into `{$p}{$tableName}` (`name`)  values ('{$roleName}');";
    }

    else {
        $perm_names  = implode(', ', $allowedPermissions);
        $perm_values = implode(', ', array_fill(0, count($allowedPermissions), 1));
        $query       =
            "insert into `{$p}{$tableName}` (`name`, {$perm_names})  values ('{$roleName}', {$perm_values});";
    }

    return $query;
}


//  MAIN    MAIN    MAIN    MAIN    MAIN  MAIN    MAIN    MAIN    MAIN    MAIN  //
//  MAIN    MAIN    MAIN    MAIN    MAIN  MAIN    MAIN    MAIN    MAIN    MAIN  //
global $database;

// Global roles table
$globalPermissions = array();

$membershipPermissions = array();

// extension permissions
foreach (array(
             'ki_debug',
             'ki_admin',
             'ki_budget',
             'ki_expense',
             'ki_export',
             'ki_invoice',
             'ki_timesheet',
             'ki_demo') as $extension) {
    $globalPermissions[] = $extension . '__access';
}

// domain object permissions
foreach (array('customer', 'project', 'activity', 'user') as $object) {

    foreach (array('add', 'edit', 'delete', 'assign', 'unassign') as $action) {

        $globalPermissions[]     = 'core__' . $object . '__other_group__' . $action;
        $membershipPermissions[] = 'core__' . $object . '__' . $action;
    }
}

// status permissions
foreach (array('add', 'edit', 'delete') as $action) {
    $globalPermissions[] = 'core__status__' . $action;
}

// group permissions
$globalPermissions[]     = 'core__group__add';
$globalPermissions[]     = 'core__group__other_group__edit';
$globalPermissions[]     = 'core__group__other_group__delete';
$membershipPermissions[] = 'core__user__view';
$membershipPermissions[] = 'core__group__edit';
$membershipPermissions[] = 'core__group__delete';

// adminpanel permissions
$globalPermissions[] = 'ki_admin__edit_advanced';

// timesheet permissions
$globalPermissions[]     = 'ki_timesheet__own_entry__add';
$membershipPermissions[] = 'ki_timesheet__other_entry__own_group__add';
$globalPermissions[]     = 'ki_timesheet__other_entry__other_group__add';
$globalPermissions[]     = 'ki_timesheet__own_entry__edit';
$membershipPermissions[] = 'ki_timesheet__other_entry__own_group__edit';
$globalPermissions[]     = 'ki_timesheet__other_entry__other_group__edit';
$globalPermissions[]     = 'ki_timesheet__own_entry__delete';
$membershipPermissions[] = 'ki_timesheet__other_entry__own_group__delete';
$globalPermissions[]     = 'ki_timesheet__other_entry__other_group__delete';

$globalPermissions[] = 'ki_timesheet__show_rates';
$globalPermissions[] = 'ki_timesheet__edit_rates';

// expenses permissions
$globalPermissions[]     = 'ki_expense__own_entry__add';
$membershipPermissions[] = 'ki_expense__other_entry__own_group__add';
$globalPermissions[]     = 'ki_expense__other_entry__other_group__add';
$globalPermissions[]     = 'ki_expense__own_entry__edit';
$membershipPermissions[] = 'ki_expense__other_entry__own_group__edit';
$globalPermissions[]     = 'ki_expense__other_entry__other_group__edit';
$globalPermissions[]     = 'ki_expense__own_entry__delete';
$membershipPermissions[] = 'ki_expense__other_entry__own_group__delete';
$globalPermissions[]     = 'ki_expense__other_entry__other_group__delete';


$query = roleTable_createQuery('global_role', 'global_role_id', $globalPermissions);
exec_query($query);

// global admin role
$query = roleData_insertQuery('global_role', 'Admin', $globalPermissions);
exec_query($query);
$globalAdminRoleID = mysqli_insert_id($database->link);

// global user role
$allowedPermissions = array(
    'ki_budget__access',
    'ki_expense__access',
    'ki_export__access',
    'ki_invoice__access',
    'ki_timesheet__access',
    'ki_timesheet__show_rates',
    'ki_timesheet__own_entry__add',
    'ki_timesheet__own_entry__edit',
    'ki_timesheet__own_entry__delete',
    'ki_expense__own_entry__add',
    'ki_expense__own_entry__edit',
    'ki_expense__own_entry__delete',
);
$query              = roleData_insertQuery('global_role', 'User', $allowedPermissions);
exec_query($query);
$globalUserRoleID = mysqli_insert_id($database->link);


$query = roleTable_createQuery('membership_role', 'membership_role_id', $membershipPermissions);
exec_query($query);


// membership admin role
$query = roleData_insertQuery('membership_role', 'Admin', $membershipPermissions);
exec_query($query);
$membershipAdminRoleID = mysqli_insert_id($database->link);


// membership user role
$allowedPermissions = array();
$query              = roleData_insertQuery('membership_role', 'User', $allowedPermissions);
exec_query($query);
$membershipUserRoleID = mysqli_insert_id($database->link);


// membership groupleader role
$allowedPermissions = array_merge($allowedPermissions, array(
    'ki_timesheet__other_entry__own_group__add',
    'ki_timesheet__other_entry__own_group__edit',
    'ki_timesheet__other_entry__own_group__delete',
    'ki_expense__other_entry__own_group__add',
    'ki_expense__other_entry__own_group__edit',
    'ki_expense__other_entry__own_group__delete',
));

$query = roleData_insertQuery('membership_role', 'Groupleader', $allowedPermissions);

exec_query($query);
$membershipGroupleaderRoleID = mysqli_insert_id($database->link);

