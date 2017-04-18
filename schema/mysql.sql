--
-- MySQL schema
-- ============
--
-- You should normally not be required to care about schema handling.
-- IcingaDB does all the migrations for you and guides you either in
-- the frontend or provides everything you need for automated migration
-- handling. Please find more related information in our documentation.

-- Often used special columns:
-- * name:     object name, unique per environment
-- * name_checksum: SHA1(name)
-- * env_checksum: SHA1(environment name)
-- * global_checksum: SHA1(env_checksum || name_checksum), unique
-- * <some>_checksum: reference to another checksum

-- All checksums are binary

SET sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION,PIPES_AS_CONCAT,ANSI_QUOTES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER';

SET innodb_strict_mode = 1;
SET @old_innodb_file_format = @@innodb_file_format;
SET GLOBAL innodb_file_format = BARRACUDA;
SET GLOBAL innodb_file_per_table = 1;
SET GLOBAL innodb_large_prefix = 1;

CREATE TABLE icingadb_schema_migration (
  schema_version SMALLINT UNSIGNED NOT NULL,
  migration_time DATETIME NOT NULL,
  PRIMARY KEY(schema_version)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE icinga_environment (
  name_checksum BINARY(20) NOT NULL,
  name VARCHAR(64) NOT NULL,
  director_db VARCHAR(64) NOT NULL,
  PRIMARY KEY (name_checksum),
  UNIQUE KEY idx_name (name)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE icinga_config_package (
    global_checksum BINARY(20) NOT NULL,
    name_checksum BINARY(20) NOT NULL,
    env_checksum BINARY(20) NOT NULL,
    name VARCHAR(64) NOT NULL,
    -- TODO: persist the name of the node dumping the package?
    PRIMARY KEY (global_checksum),
    UNIQUE KEY idx_name (name_checksum, env_checksum),
    CONSTRAINT config_package_environment
      FOREIGN KEY env (env_checksum)
      REFERENCES icinga_environment (name_checksum)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE icinga_config_file (
  global_checksum BINARY(20) NOT NULL, -- SHA1(package_checksum || name_checksum || content_checksum)
  package_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL, -- SHA1(filename)
  content_checksum BINARY(20) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  PRIMARY KEY (global_checksum),
  UNIQUE KEY idx_name (package_checksum, name_checksum),
  CONSTRAINT config_file_package
    FOREIGN KEY package (package_checksum)
    REFERENCES icinga_config_package (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE icinga_zone (
  global_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL,
  is_global ENUM('y', 'n') NOT NULL,
  PRIMARY KEY (global_checksum),
  INDEX idx_env_checksum (env_checksum)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_state (
  global_checksum BINARY(20) NOT NULL,
  -- name_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL, -- TODO: remove?

  severity INT UNSIGNED NOT NULL,
  state TINYINT NOT NULL,
  hard_state TINYINT DEFAULT NULL,
  state_type ENUM('hard', 'soft') NOT NULL,
  reachable ENUM('y', 'n') NOT NULL,
  attempt TINYINT DEFAULT NULL,
  problem ENUM('y', 'n') NOT NULL,
  acknowledged ENUM('y', 'n') NOT NULL,
  in_downtime ENUM('y', 'n') NOT NULL,
  handled ENUM('y', 'n') NOT NULL,
  ack_comment_checksum BINARY(20) DEFAULT NULL,
  last_update BIGINT NOT NULL,
  last_state_change BIGINT NOT NULL,
  check_source_checksum BINARY(20) DEFAULT NULL,

  PRIMARY KEY (global_checksum),
  INDEX idx_env_checksum (env_checksum)
  -- INDEX idx_name_checksum (name_checksum)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_state_volatile (
  global_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  command TEXT,
  execution_start BIGINT NOT NULL,
  execution_end BIGINT NOT NULL,
  schedule_start BIGINT NOT NULL,
  schedule_end BIGINT NOT NULL,
  exit_status TINYINT NOT NULL,
  output TEXT,
  performance_data TEXT,
  PRIMARY KEY (global_checksum),
  INDEX idx_env_checksum (env_checksum)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE service_state (
  global_checksum BINARY(20) NOT NULL,
  -- name_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,

  severity INT UNSIGNED NOT NULL,
  state TINYINT NOT NULL,
  hard_state TINYINT DEFAULT NULL,
  state_type ENUM('hard', 'soft') NOT NULL,
  reachable ENUM('y', 'n') NOT NULL,
  attempt TINYINT NOT NULL,
  problem ENUM('y', 'n') NOT NULL,
  acknowledged ENUM('y', 'n') NOT NULL,
  in_downtime ENUM('y', 'n') NOT NULL,
  handled ENUM('y', 'n') NOT NULL,
  ack_comment_checksum BINARY(20) DEFAULT NULL,
  last_update BIGINT NOT NULL,
  last_state_change BIGINT NOT NULL,
  check_source_checksum BINARY(20) DEFAULT NULL,

  PRIMARY KEY (global_checksum),
  INDEX idx_env_checksum (env_checksum)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE service_state_volatile (
  global_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  command TEXT,
  execution_start BIGINT NOT NULL,
  execution_end BIGINT NOT NULL,
  schedule_start BIGINT NOT NULL,
  schedule_end BIGINT NOT NULL,
  exit_status TINYINT NOT NULL,
  output TEXT,
  performance_data TEXT,
  PRIMARY KEY (global_checksum),
  INDEX idx_env_checksum (env_checksum)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_config (
  global_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  zone_checksum BINARY(20) NOT NULL,
  package_checksum BINARY(20) NOT NULL,

  properties_checksum BINARY(20) NOT NULL COMMENT 'sha1(all properties)',
  groups_checksum BINARY(20) NOT NULL COMMENT 'sha1(group sums)',
  vars_checksum BINARY(20) NOT NULL COMMENT 'sha1(var sums)',

  check_command_checksum BINARY(20) DEFAULT NULL,
  event_command_checksum BINARY(20) DEFAULT NULL,
  check_period_checksum BINARY(20) DEFAULT NULL,
  check_interval INT(10) UNSIGNED DEFAULT NULL,
  check_retry_interval INT(10) UNSIGNED DEFAULT NULL,

  action_url_checksum BINARY(20) DEFAULT NULL,
  notes_url_checksum BINARY(20) DEFAULT NULL,
  last_comment_checksum BINARY(20) DEFAULT NULL,

  name VARCHAR(255) NOT NULL,
  name_ci VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  label VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,

  address VARCHAR(255) DEFAULT NULL,
  address6 VARCHAR(255) DEFAULT NULL,
  address_bin BINARY(4) DEFAULT NULL,
  address6_bin BINARY(16) DEFAULT NULL,

  active_checks_enabled ENUM('y', 'n') NOT NULL,
  event_handler_enabled ENUM('y', 'n') NOT NULL,
  flapping_enabled ENUM('y', 'n') NOT NULL,
  notifications_enabled ENUM('y', 'n') NOT NULL,
  passive_checks_enabled ENUM('y', 'n') NOT NULL,
  perfdata_enabled ENUM('y', 'n') NOT NULL,
  volatile ENUM('y', 'n') NOT NULL,

  --  ctime BIGINT NOT NULL,
  --  mtime BIGINT NOT NULL,

  PRIMARY KEY (global_checksum),
  INDEX idx_env_checksum (env_checksum),
  INDEX idx_name_checksum (name_checksum),

  INDEX idx_name (name),
  INDEX idx_name_ci (name_ci),
  INDEX idx_label (label)

) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE service_config (
  global_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL, -- does this make sense?
  env_checksum BINARY(20) NOT NULL,
  zone_checksum BINARY(20) NOT NULL,
  package_checksum BINARY(20) NOT NULL,
  host_checksum BINARY(20) NOT NULL,

  properties_checksum BINARY(20) NOT NULL COMMENT 'sha1(all properties)',
  groups_checksum BINARY(20) NOT NULL COMMENT 'sha1(group sums)',
  vars_checksum BINARY(20) NOT NULL COMMENT 'sha1(var sums)',

  check_command_checksum BINARY(20) DEFAULT NULL,
  event_command_checksum BINARY(20) DEFAULT NULL,
  check_period_checksum BINARY(20) DEFAULT NULL,
  check_interval INT(10) UNSIGNED DEFAULT NULL,
  check_retry_interval INT(10) UNSIGNED DEFAULT NULL,

  action_url_checksum BINARY(20) DEFAULT NULL,
  notes_url_checksum BINARY(20) DEFAULT NULL,
  last_comment_checksum BINARY(20) DEFAULT NULL,

  name VARCHAR(255) NOT NULL,
  name_ci VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  label VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,

  active_checks_enabled ENUM('y', 'n') NOT NULL,
  event_handler_enabled ENUM('y', 'n') NOT NULL,
  flapping_enabled ENUM('y', 'n') NOT NULL,
  notifications_enabled ENUM('y', 'n') NOT NULL,
  passive_checks_enabled ENUM('y', 'n') NOT NULL,
  perfdata_enabled ENUM('y', 'n') NOT NULL,
  volatile ENUM('y', 'n') NOT NULL,

  --  ctime BIGINT NOT NULL,
  --  mtime BIGINT NOT NULL,

  PRIMARY KEY (global_checksum),
  INDEX idx_env_checksum (env_checksum),
  INDEX idx_name_checksum (name_checksum),

  INDEX idx_name (name),
  INDEX idx_name_ci (name_ci),
  INDEX idx_label (label)

) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE user_config (
  global_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL, -- does this make sense?
  env_checksum BINARY(20) NOT NULL,
  zone_checksum BINARY(20) NOT NULL,
  package_checksum BINARY(20) NOT NULL,

  properties_checksum BINARY(20) NOT NULL COMMENT 'sha1(all properties)',
  groups_checksum BINARY(20) NOT NULL COMMENT 'sha1(group sums)',
  vars_checksum BINARY(20) NOT NULL COMMENT 'sha1(var sums)',

  period_checksum BINARY(20) DEFAULT NULL,

  name VARCHAR(255) NOT NULL,
  name_ci VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  label VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  email VARCHAR(255) DEFAULT NULL,
  pager VARCHAR(255) DEFAULT NULL,

  enable_notifications ENUM('y', 'n') NOT NULL,
  states VARCHAR(60) DEFAULT NULL COMMENT 'JSON-encoded set, OK, Warning...',
  types VARCHAR(140) DEFAULT NULL COMMENT 'JSON-encoded set: Problem, Revovery...',

  PRIMARY KEY (global_checksum),
  INDEX idx_env_checksum (env_checksum),
  INDEX idx_name_checksum (name_checksum),

  INDEX idx_name (name),
  INDEX idx_name_ci (name_ci),
  INDEX idx_label (label)
  -- missing: package, templates, zone

) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE hostgroup (
  global_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,

  name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  name_ci VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  label VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,

  PRIMARY KEY (global_checksum),
  UNIQUE KEY idx_hostgroup_name (env_checksum, name),
  INDEX env_checksum (env_checksum),
  INDEX idx_hostgroup_name_ci (name_ci)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

-- cache table, TODO:
-- CREATE TABLE hostgroup_name_member (
--   hostgroup_checksum BINARY(20) NOT NULL,
--   host_checksum BINARY(20) NOT NULL,
--
--   PRIMARY KEY (hostgroup_checksum, host_checksum),
--
--   CONSTRAINT fk_hostgroup
--   FOREIGN KEY (hostgroup_checksum)
--   REFERENCES hostgroup (name_checksum)
--    ON DELETE CASCADE
--    ON UPDATE CASCADE,
--
--  CONSTRAINT fk_host
--  FOREIGN KEY (host_checksum)
--  REFERENCES host_config (name_checksum)
--    ON DELETE CASCADE
--    ON UPDATE CASCADE
-- ) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE hostgroup_member (
  global_hostgroup_checksum BINARY(20) NOT NULL,
  global_host_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,

  PRIMARY KEY (global_hostgroup_checksum, global_host_checksum),
  INDEX (env_checksum, global_hostgroup_checksum, global_host_checksum),

  CONSTRAINT hostgroup_member_hostgroup
    FOREIGN KEY hostgroup (global_hostgroup_checksum)
    REFERENCES hostgroup (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT hostgroup_member_host
    FOREIGN KEY host (global_host_checksum)
    REFERENCES host_config (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE servicegroup (
  global_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,

  name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  name_ci VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  label VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,

  PRIMARY KEY (global_checksum),
  UNIQUE KEY idx_servicegroup_name (env_checksum, name),
  INDEX env_checksum (env_checksum),
  INDEX idx_servicegroup_name_ci (name_ci)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;


CREATE TABLE servicegroup_member (
  global_servicegroup_checksum BINARY(20) NOT NULL,
  global_service_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,

  PRIMARY KEY (global_servicegroup_checksum, global_service_checksum),
  INDEX (env_checksum, global_servicegroup_checksum, global_service_checksum),

  CONSTRAINT servicegroup_member_servicegroup
    FOREIGN KEY servicegroup (global_servicegroup_checksum)
    REFERENCES servicegroup (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT servicegroup_member_service
    FOREIGN KEY service (global_service_checksum)
    REFERENCES service_config (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;


CREATE TABLE usergroup (
  global_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,

  name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  name_ci VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  label VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,

  PRIMARY KEY (global_checksum),
  UNIQUE KEY idx_usergroup_name (env_checksum, name),
  INDEX env_checksum (env_checksum),
  INDEX idx_usergroup_name_ci (name_ci)
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;


CREATE TABLE usergroup_member (
  global_usergroup_checksum BINARY(20) NOT NULL,
  global_user_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,

  PRIMARY KEY (global_usergroup_checksum, global_user_checksum),
  INDEX (env_checksum, global_usergroup_checksum, global_user_checksum),

  CONSTRAINT usergroup_member_usergroup
    FOREIGN KEY usergroup (global_usergroup_checksum)
    REFERENCES usergroup (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT usergroup_member_user
    FOREIGN KEY user (global_user_checksum)
    REFERENCES user_config (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

-- CREATE TABLE dependency (
--   checksum BINARY(20) NOT NULL,
--   package_checksum BINARY(20) NOT NULL,
--   config_file_name VARCHAR(255) NOT NULL,
--   config_file_checksum BINARY(20) NOT NULL,
--   config_file_position VARCHAR(32) NOT NULL,
--
--   .. properties.
-- )

-- CREATE TABLE host_dependency (
--
-- )

CREATE TABLE comment (
  checksum BINARY(20) NOT NULL COMMENT 'sha1(env_checksum;timestamp;author;content)',
  env_checksum BINARY(20) NOT NULL,
  author VARCHAR(255) NOT NULL,
  timestamp BIGINT(20) NOT NULL,
  content text NOT NULL,
  PRIMARY KEY (checksum)
) ENGINE=InnoDb ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_comment (
  global_host_checksum BINARY(20) NOT NULL,
  comment_checksum BINARY(20) NOT NULL,
  PRIMARY KEY (global_host_checksum, comment_checksum),

  CONSTRAINT host_comment_comment
    FOREIGN KEY comment (comment_checksum)
    REFERENCES comment (checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT host_comment_host
    FOREIGN KEY host (global_host_checksum)
    REFERENCES host_config (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE

) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE custom_var (
  checksum BINARY(20) NOT NULL, -- sha1(varname=varvalue)
  varname VARCHAR(255) NOT NULL COLLATE utf8_bin,
  varvalue TEXT NOT NULL,
  PRIMARY KEY (checksum),
  INDEX search_idx (varname)
) ENGINE=InnoDb ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE custom_var_flat (
  var_checksum BINARY(20) NOT NULL,
  flatname_checksum BINARY(20) NOT NULL,
  flatname VARCHAR(512) NOT NULL COLLATE utf8_bin,
  flatvalue TEXT NOT NULL,
  PRIMARY KEY (var_checksum, flatname_checksum),
  INDEX search_varname (flatname (191)), -- TODO: full size?
  INDEX search_varvalue (flatvalue (128)),
  CONSTRAINT flat_var_var
  FOREIGN KEY checksum (var_checksum)
  REFERENCES custom_var (checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDb ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_var (
  host_checksum BINARY(20) NOT NULL,
  var_checksum BINARY(20) NOT NULL,
  PRIMARY KEY (host_checksum, var_checksum),
  FOREIGN KEY host_var_host (host_checksum)
    REFERENCES host_config (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  FOREIGN KEY host_var_var_checksum (var_checksum)
    REFERENCES custom_var (checksum)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE service_var (
  service_checksum BINARY(20) NOT NULL,
  var_checksum BINARY(20) NOT NULL,
  PRIMARY KEY (service_checksum, var_checksum),
  FOREIGN KEY service_var_service (service_checksum)
  REFERENCES service_config (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  FOREIGN KEY service_var_checksum (var_checksum)
  REFERENCES custom_var (checksum)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE user_var (
  user_checksum BINARY(20) NOT NULL,
  var_checksum BINARY(20) NOT NULL,
  PRIMARY KEY (user_checksum, var_checksum),
  FOREIGN KEY user_var_user (user_checksum)
  REFERENCES user_config (global_checksum)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  FOREIGN KEY user_var_checksum (var_checksum)
  REFERENCES custom_var (checksum)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE custom_var_set (
  set_checksum BINARY(20) NOT NULL,
  var_checksum BINARY(20) NOT NULL,
  PRIMARY KEY (set_checksum, var_checksum),
  FOREIGN KEY custom_var_set_var_checksum (var_checksum)
  REFERENCES custom_var (checksum)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT
) ENGINE=InnoDb ROW_FORMAT=DYNAMIC DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

-- TODO: Partitioned -> event_history_<(ts / 1000000) / (30 * 24 * 60 * 60)>
-- About one table per month, timezone-independent
-- Might be delegated to the database.
-- Implement procedures for duration-calculation and eventual fake states as a
-- 'boundary' in partitions for better performance
CREATE TABLE host_state_history (
  host_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  timestamp BIGINT(20) UNSIGNED NOT NULL,
  -- event_type,
  state TINYINT NOT NULL,
  state_type ENUM('hard', 'soft') NOT NULL,
  duration BIGINT DEFAULT NULL,
  attempt TINYINT DEFAULT NULL,
  max_attempts TINYINT DEFAULT NULL,
  severity INT UNSIGNED NOT NULL,
  output VARCHAR(255), -- status line only
  check_source_checksum BINARY(20), -- sha1(endpoint name)

  PRIMARY KEY (host_checksum, timestamp),
  INDEX sort_all (timestamp, host_checksum)
) ENGINE=InnoDb ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE service_state_history (
  service_checksum BINARY(20) NOT NULL,
  host_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  timestamp BIGINT(20) UNSIGNED NOT NULL,
  -- event_type,
  state TINYINT NOT NULL,
  state_type ENUM('hard', 'soft') NOT NULL,
  duration BIGINT DEFAULT NULL,
  attempt TINYINT DEFAULT NULL,
  max_attempts TINYINT DEFAULT NULL,
  severity INT UNSIGNED NOT NULL,
  output VARCHAR(255), -- status line only
  check_source_checksum BINARY(20), -- sha1(endpoint name)

  PRIMARY KEY (service_checksum, timestamp),
  INDEX sort_all (timestamp, service_checksum),
  INDEX sort_host (timestamp, host_checksum, service_checksum)
) ENGINE=InnoDb ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE host_name_history (
  global_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  host_name VARCHAR(255) NOT NULL,
  env_name VARCHAR(64) NOT NULL,
  first_seen BIGINT(20) NOT NULL,
  last_seen BIGINT(20) NOT NULL,
  PRIMARY KEY (global_checksum),
  INDEX search_idx (host_name)
) ENGINE=InnoDb ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE service_name_history (
  global_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  host_name VARCHAR(255) NOT NULL,
  service_name VARCHAR(255) NOT NULL,
  env_name VARCHAR(64) NOT NULL,
  first_seen BIGINT(20) NOT NULL,
  last_seen BIGINT(20) NOT NULL,
  PRIMARY KEY (global_checksum),
  INDEX search_idx (service_name),
  INDEX search_host_idx (host_name(120), service_name(120))
) ENGINE=InnoDb ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE user_name_history (
  global_checksum BINARY(20) NOT NULL,
  name_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  user_name VARCHAR(255) NOT NULL,
  env_name VARCHAR(64) NOT NULL,
  first_seen BIGINT(20) NOT NULL,
  last_seen BIGINT(20) NOT NULL,
  PRIMARY KEY (global_checksum),
  UNIQUE INDEX search_idx (user_name)
) ENGINE=InnoDb ROW_FORMAT=COMPRESSED DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;

CREATE TABLE url (
  checksum BINARY(20) NOT NULL COMMENT 'SHA1(url_checksum || env_checksum)',
  url_checksum BINARY(20) NOT NULL,
  env_checksum BINARY(20) NOT NULL,
  url VARCHAR(2083),
  PRIMARY KEY (checksum)
) ENGINE=InnoDb DEFAULT CHARSET=utf8mb4;

DELIMITER //
CREATE PROCEDURE populate_pending_hosts_for_environment (
  IN environment_checksum BINARY(20)
)
  BEGIN
    SET @bigNow = FLOOR(UNIX_TIMESTAMP(CONCAT(DATE(NOW()), ' ', CURTIME(6))) * 1000000);
    INSERT INTO host_state (
      global_checksum,
      env_checksum,
      severity,
      state,
      hard_state,
      reachable,
      attempt,
      problem,
      acknowledged,
      in_downtime,
      handled,
      state_type,
      last_state_change,
      last_update
    ) SELECT
        hc.global_checksum,
        environment_checksum,
        24, -- pending(1) << 4 & no_flag(8)
        99,
        99,
        'y',
        1,
        'n',
        'n',
        'n',
        'n',
        'hard',
        @bigNow,
        @bigNow
      FROM host_config hc
        LEFT JOIN host_state hs
          ON hc.global_checksum = hs.global_checksum
      WHERE hs.global_checksum IS NULL
        AND hc.env_checksum = environment_checksum;

  END
//

DELIMITER ;

DELIMITER //
CREATE PROCEDURE populate_pending_services_for_environment (
  IN environment_checksum BINARY(20)
)
  BEGIN
    SET @bigNow = FLOOR(UNIX_TIMESTAMP(CONCAT(DATE(NOW()), ' ', CURTIME(6))) * 1000000);
    INSERT INTO service_state (
      global_checksum,
      env_checksum,
      severity,
      state,
      hard_state,
      reachable,
      attempt,
      problem,
      acknowledged,
      in_downtime,
      handled,
      state_type,
      last_state_change,
      last_update
    ) SELECT
        sc.global_checksum,
        environment_checksum,
        24, -- pending(1) << 4 & no_flag(8)
        99,
        99,
        'y',
        1,
        'n',
        'n',
        'n',
        'n',
        'hard',
        @bigNow,
        @bigNow
      FROM service_config sc
        LEFT JOIN service_state ss
          ON sc.global_checksum = ss.global_checksum
      WHERE ss.global_checksum IS NULL
            AND sc.env_checksum = environment_checksum;

  END
//

DELIMITER ;

DELIMITER //
CREATE PROCEDURE drop_obsolete_host_states_for_environment (
  IN environment_checksum BINARY(20)
)
  BEGIN
    DELETE hs.* FROM host_state hs
      LEFT JOIN host_config hc
        ON hs.global_checksum = hc.global_checksum
         WHERE hc.global_checksum IS NULL
           AND hs.env_checksum = environment_checksum;
  END
//

DELIMITER ;


DELIMITER //
CREATE PROCEDURE drop_obsolete_service_states_for_environment (
  IN environment_checksum BINARY(20)
)
  BEGIN
    DELETE ss.* FROM service_state ss
      LEFT JOIN service_config sc
        ON ss.global_checksum = sc.global_checksum
    WHERE sc.global_checksum IS NULL
          AND ss.env_checksum = environment_checksum;
  END
//

DELIMITER ;


DELIMITER //
CREATE PROCEDURE drop_environment (
  IN environment_checksum BINARY(20)
)
  BEGIN
    DELETE FROM comment WHERE env_checksum = environment_checksum;
    DELETE FROM url WHERE env_checksum = environment_checksum;
    DELETE FROM usergroup_member WHERE env_checksum = environment_checksum;
    DELETE FROM usergroup WHERE env_checksum = environment_checksum;
    DELETE FROM user_config WHERE env_checksum = environment_checksum;
    DELETE FROM servicegroup_member WHERE env_checksum = environment_checksum;
    DELETE FROM servicegroup WHERE env_checksum = environment_checksum;
    DELETE FROM service_state_volatile WHERE env_checksum = environment_checksum;
    DELETE FROM service_state WHERE env_checksum = environment_checksum;
    DELETE FROM service_config WHERE env_checksum = environment_checksum;
    DELETE FROM hostgroup_member WHERE env_checksum = environment_checksum;
    DELETE FROM hostgroup WHERE env_checksum = environment_checksum;
    DELETE FROM host_state_volatile WHERE env_checksum = environment_checksum;
    DELETE FROM host_state WHERE env_checksum = environment_checksum;
    DELETE FROM host_config WHERE env_checksum = environment_checksum;
    DELETE FROM icinga_config_package WHERE env_checksum = environment_checksum;
    DELETE FROM icinga_environment WHERE name_checksum = environment_checksum;
  END
//

DELIMITER ;

INSERT INTO icingadb_schema_migration
  (schema_version, migration_time)
  VALUES (1, NOW());

SET GLOBAL innodb_file_format = @old_innodb_file_format;
