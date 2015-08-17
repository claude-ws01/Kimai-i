create table if not exists `kimaii__activity` (
    `activity_id` INT(10) UNSIGNED    not null auto_increment,
    `visible`     TINYINT(1) UNSIGNED not null default '1',
    `filter`      TINYINT(1) UNSIGNED not null default '0',
    `trash`       TINYINT(1) UNSIGNED not null default '0',
    `name`        VARCHAR(80)         not null,
    `comment`     TEXT                not null,
    primary key (`activity_id`)
)
    engine = InnoDB
    auto_increment = 1
;

create table if not exists `kimaii__configuration` (
    `option` VARCHAR(255) not null,
    `value`  VARCHAR(255) not null,
    primary key (`option`)
)
    engine = InnoDB
;

create table if not exists `kimaii__customer` (
    `customer_id`         INT(10) UNSIGNED    not null,
    `visible`             TINYINT(1) UNSIGNED not null default '1',
    `filter`              TINYINT(1) UNSIGNED not null default '0',
    `trash`               TINYINT(1) UNSIGNED not null default '0',
    `password_reset_hash` CHAR(32)                     default null,
    `name`                VARCHAR(80)         not null,
    `password`            VARCHAR(64)                  default null,
    `secure`              VARCHAR(60)         not null default '0',
    `company`             VARCHAR(80)         not null,
    `vat_rate`            VARCHAR(10)                  default '0',
    `contact`             VARCHAR(80)         not null,
    `street`              VARCHAR(120)        not null,
    `zipcode`             VARCHAR(16)         not null,
    `city`                VARCHAR(80)         not null,
    `phone`               VARCHAR(16)         not null,
    `fax`                 VARCHAR(16)         not null,
    `mobile`              VARCHAR(16)         not null,
    `mail`                VARCHAR(80)         not null,
    `homepage`            VARCHAR(255)        not null,
    `timezone`            VARCHAR(32)         not null,
    `timeframe_begin`     VARCHAR(60)         not null default '0',
    `timeframe_end`       VARCHAR(60)         not null default '0',
    `comment`             TEXT                not null,
    primary key (`customer_id`)
)
    engine = InnoDB
;

create table if not exists `kimaii__expense` (
    `expense_id`   INT(10) UNSIGNED    not null auto_increment,
    `timestamp`    INT(10) UNSIGNED    not null default '0',
    `user_id`      INT(10) UNSIGNED    not null,
    `project_id`   INT(10) UNSIGNED    not null,
    `comment_type` TINYINT(1) UNSIGNED not null default '0',
    `refundable`   TINYINT(1) UNSIGNED not null default '0'
    comment 'expense refundable to employee (0 = no, 1 = yes)',
    `cleared`      TINYINT(1) UNSIGNED not null default '0',
    `multiplier`   DECIMAL(10, 2)      not null default '1.00',
    `value`        DECIMAL(10, 2)      not null default '0.00',
    `comment`      TEXT                not null,
    `description`  TEXT                not null,
    primary key (`expense_id`),
    key `user_id` (`user_id`),
    key `project_id` (`project_id`)
)
    engine = InnoDB
    auto_increment = 1
;

create table if not exists `kimaii__fixed_rate` (
    `project_id`  INT(10) UNSIGNED        default null,
    `activity_id` INT(10) UNSIGNED        default null,
    `rate`        DECIMAL(10, 2) not null default '0.00',
    unique key `project_id` (`project_id`, `activity_id`)
)
    engine = InnoDB
;

create table if not exists `kimaii__group` (
    `group_id` INT(10) UNSIGNED    not null auto_increment,
    `trash`    TINYINT(1) UNSIGNED not null default '0',
    `name`     VARCHAR(40)         not null,
    primary key (`group_id`)
)
    engine = InnoDB
    auto_increment = 1
;

create table if not exists `kimaii__group_activity` (
    `group_id`    INT(10) UNSIGNED not null,
    `activity_id` INT(10) UNSIGNED not null,
    unique key `group_id` (`group_id`, `activity_id`)
)
    engine = InnoDB
;

create table if not exists `kimaii__group_customer` (
    `group_id`    INT(10) UNSIGNED not null,
    `customer_id` INT(10) UNSIGNED not null,
    unique key `group_id` (`group_id`, `customer_id`)
)
    engine = InnoDB
;

create table if not exists `kimaii__group_project` (
    `group_id`   INT(10) UNSIGNED not null,
    `project_id` INT(10) UNSIGNED not null,
    unique key `group_id` (`group_id`, `project_id`)
)
    engine = InnoDB
;

create table if not exists `kimaii__group_user` (
    `group_id`           INT(10) UNSIGNED not null,
    `user_id`            INT(10) UNSIGNED not null,
    `membership_role_id` INT(10) UNSIGNED not null,
    primary key (`group_id`, `user_id`)
)
    engine = InnoDB
;

create table if not exists `kimaii__preference` (
    `user_id` INT(10) UNSIGNED not null,
    `option`  VARCHAR(255)     not null,
    `value`   VARCHAR(255)     not null,
    primary key (`user_id`, `option`)
)
    engine = InnoDB
;

create table if not exists `kimaii__project` (
    `project_id`  INT(10) UNSIGNED    not null auto_increment,
    `customer_id` INT(10)   UNSIGNED  not null,
    `visible`     TINYINT(1) UNSIGNED not null default '1',
    `filter`      TINYINT(1) UNSIGNED not null default '0',
    `trash`       TINYINT(1) UNSIGNED not null default '0',
    `internal`    TINYINT(1) UNSIGNED not null default '0',
    `budget`      DECIMAL(10, 2)      not null default '0.00',
    `effort`      DECIMAL(10, 2)               default '0.00',
    `approved`    DECIMAL(10, 2)               default '0.00',
    `name`        VARCHAR(40)         not null,
    `comment`     TEXT                not null,
    primary key (`project_id`),
    key `customer_id` (`customer_id`)
)
    engine = InnoDB
    auto_increment = 1
;

create table if not exists `kimaii__project_activity` (
    `project_id`  INT(10) UNSIGNED not null,
    `activity_id` INT(10) UNSIGNED not null,
    `budget`      DECIMAL(10, 2) default '0.00',
    `effort`      DECIMAL(10, 2) default '0.00',
    `approved`    DECIMAL(10, 2) default '0.00',
    unique key `project_id` (`project_id`, `activity_id`)
)
    engine = InnoDB
;

create table if not exists `kimaii__rate` (
    `user_id`     INT(10) UNSIGNED        default null,
    `project_id`  INT(10) UNSIGNED        default null,
    `activity_id` INT(10) UNSIGNED        default null,
    `rate`        DECIMAL(10, 2) not null default '0.00',
    unique key `user_id` (`user_id`, `project_id`, `activity_id`)
)
    engine = InnoDB
;

create table if not exists `kimaii__status` (
    `status_id` TINYINT(4)  UNSIGNED not null auto_increment,
    `status`    VARCHAR(200)         not null,
    primary key (`status_id`)
)
    engine = InnoDB
    auto_increment = 1
;

create table if not exists `kimaii__timesheet` (
    `time_entry_id` INT(10) UNSIGNED     not null auto_increment,
    `start`         INT(10) UNSIGNED     not null default '0',
    `end`           INT(10) UNSIGNED     not null default '0',
    `duration`      INT(6)               not null default '0',
    `user_id`       INT(10) UNSIGNED     not null,
    `project_id`    INT(10) UNSIGNED     not null,
    `activity_id`   INT(10) UNSIGNED     not null,
    `comment_type`  TINYINT(1) UNSIGNED  not null default '0',
    `cleared`       TINYINT(1) UNSIGNED  not null default '0',
    `billable`      TINYINT(1) UNSIGNED           default '1',
    `status_id`     SMALLINT(4) UNSIGNED not null,
    `rate`          DECIMAL(10, 2)       not null default '0.00',
    `fixed_rate`    DECIMAL(10, 2)       not null default '0.00',
    `budget`        DECIMAL(10, 2)                default '0.00',
    `approved`      DECIMAL(10, 2)                default '0.00',
    `location`      VARCHAR(50)                   default null,
    `ref_code`      VARCHAR(30)                   default null,
    `comment`       TEXT,
    `description`   TEXT,
    primary key (`time_entry_id`),
    key `user_id` (`user_id`),
    key `project_id` (`project_id`),
    key `activity_id` (`activity_id`)
)
    engine = InnoDB
    auto_increment = 1
;

create table if not exists `kimaii__user` (
    `user_id`             INT(10) UNSIGNED    not null,
    `trash`               TINYINT(1) UNSIGNED not null default '0',
    `active`              TINYINT(1) UNSIGNED not null default '1',
    `ban`                 INT(1) UNSIGNED     not null default '0',
    `ban_time`            INT(10) UNSIGNED    not null default '0',
    `last_project`        INT(10) UNSIGNED    not null default '1',
    `last_activity`       INT(10) UNSIGNED    not null default '1',
    `last_record`         INT(10) UNSIGNED    not null default '0',
    `global_role_id`      INT(10) UNSIGNED    not null default '0',
    `password_reset_hash` CHAR(32)                     default null,
    `name`                VARCHAR(80)         not null,
    `alias`               VARCHAR(80)                  default null,
    `mail`                VARCHAR(80)         not null default '',
    `password`            VARCHAR(64)                  default null,
    `secure`              VARCHAR(60)         not null default '0',
    `timeframe_begin`     VARCHAR(60)         not null default '0',
    `timeframe_end`       VARCHAR(60)         not null default '0',
    `apikey`              VARCHAR(30)                  default null,
    primary key (`user_id`),
    unique key `name` (`name`),
    unique key `apikey` (`apikey`)
)
    engine = InnoDB
;
