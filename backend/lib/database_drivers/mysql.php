<?php
include 'base.php';

// FIXME: convert to array
function modelToSQL($type) {
  $sql = '';
  switch($type) {
    case 'string':
    case 'str': // official
      $sql = ' VARCHAR(255) NOT NULL DEFAULT "", ';
    break;
    case 'integer':
    case 'int': // official
      $sql = ' BIGINT NOT NULL DEFAULT 0, ';
    break;
    // we might need to do some cast on mysql...
    case 'boolean':
    case 'bool': // official
      $sql = ' TINYINT UNSIGNED NOT NULL DEFAULT 0, ';
    break;
    case 'text': // official
      $sql = ' MEDIUMTEXT NOT NULL, '; // 16mb
    break;
    case 'bigtext':
      $sql = ' LONGTEXT NOT NULL, '; // 4GB
    break;
    /*
    case 'datetime':
      $sql = ' DATETIME NOT NULL DEFAULT \'0001-01-01\', ';
    break;
    */
  }
  return $sql;
}

// FIXME: convert to array
function sqlToType($sqlType) {
  //echo "sqlToType[$sqlType]<br>\n";
  $type = 'true';
  switch($sqlType) {
    case 'varchar(255)':
      $type = 'str';
    break;
    case 'bigint(20)':
      $type = 'int';
    break;
    case 'tinyint(3) unsigned':
      $type = 'bool';
    break;
    case 'mediumtext':
      $type = 'text';
    break;
    case 'datetime':
      $type = 'datetime';
    break;
  }
  return $type;
}

class mysql_driver extends database_driver_base_class implements database_driver_base {
  function __construct() {
    parent::__construct();
    $this->modelToSQL = array();
    $this->sqlToModel = array();
    $this->joinCount = 0;
    $this->btTables = true;
    $this->forceUnsetIdOnUpdate = false;
  }
  // direct
  private function connect($host, $user, $pass, $port = 0) {
    if ($this->conn !== null) {
      // throw exception?
      echo "Already connected<br>\n";
      return false;
    }
    $this->conn = mysqli_connect($host, $user, $pass);
    if (!$this->conn) {
      echo "Failed to connect [$user@$host]<br>\n";
      echo "Connect debugging errno: " . mysqli_connect_errno() . "<br>\n";
      echo "Connect debugging error: " . mysqli_connect_error() . "<br>\n";
      return false;
    }
    $this->hostname = $host;
    $this->username = $user;
    $this->password = $pass;
    return true;
  }
  // direct
  private function switch_db($db) {
    // FIXME check result code
    mysqli_select_db($this->conn, $db);
    $this->sql_current_db = $db;
    return true;
  }
  // easy
  public function connect_db($host, $user, $pass, $db, $port = 3306) {
    if (!$this->connect($host, $user, $pass, $port)) {
      return false;
    }
    return $this->switch_db($db);
  }

  // build table if it doesn't exist...
  public function autoupdate($model) {
    // force json field in all models
    $model['fields']['json'] = array('type'=>'text');
    // get name
    $tableName = modelToTableName($model);
    $this->registeredTables[] = $tableName;
    //echo "Checking [$tableName]<br>\n";
    $res = mysqli_query($this->conn, 'describe `' . $tableName. '`');
    $err = mysqli_error($this->conn);
    // do we need to create table?
    if ($err && strpos($err, 'doesn\'t exist') !== false) {
      // don't need to clean failed query in mysql
      //mysqli_free_result($res);
      // create table
      //echo "creating table ", $tableName, "\n";
      $sql = 'create table `' . $tableName. '` (';
      $idf = modelToId($model);
      $sql .= $idf.' BIGINT AUTO_INCREMENT PRIMARY KEY, ';
      //$sql .= 'json MEDIUMTEXT NOT NULL, ';
      foreach($model['fields'] as $fieldName => $f) {
        $sql .= $fieldName . ' ' .modelToSQL($f['type']);
      }
      $sql .= 'created_at INTEGER UNSIGNED NOT NULL, ';
      $sql .= 'updated_at INTEGER UNSIGNED NOT NULL';
      $sql .= ')';
      // echo "$sql\n";
      $res = mysqli_query($this->conn, $sql);
      $err = mysqli_error($this->conn);
      if ($err) {
        echo "mysql::autoupdate - create err[$err]<br>\n";
        return false;
      }
      if (isset($model['seed']) && is_array($model['seed'])) {
        $this->insert($model, $model['seed']);
      }
      return true;
    } else {
      // describle table name failed...
      if ($err) echo "mysql::autoupdate - describe err[$err]<br>\n";
      // get fields
      //echo "getting fields ", $tableName, "\n";
      $haveFields = array();
      if (is_bool($res)) {
        echo "mysql::autoupdate - existing table, didnt like describe?!<br>\n";
        return;
      }
      while($row = mysqli_fetch_assoc($res)) {
        // Field, Type, Null, Key, Default, Extra
        //print_r($row);
        $haveFields[ $row['Field'] ] = sqlToType($row['Type']);
      }
      mysqli_free_result($res);
      $haveAll = true;
      $missing = array();
      $noChanges = true;
      $changes = array();
      //echo "<pre>sql", print_r($haveFields, 1), "</pre>\n";
      //echo "<pre>want", print_r($model['fields'], 1), "</pre>\n";
      foreach($model['fields'] as $fieldName => $f) {
        //echo "Checking [$fieldName]<Br>\n";
        if (empty($haveFields[$fieldName])) {
          $haveAll = false;
          $missing[$fieldName] = $f;
        } else {
          // change type...
          //echo "Checking sql[", $haveFields[$fieldName], "!=", $f['type'], "]<br>\n";
          if ($haveFields[$fieldName] !== $f['type']) {
            $noChanges = false;
            $changes[$fieldName] = $f;
          }
        }
      }
      // FIXME: delete scan
      //echo "<pre>$tableName: Changes", print_r($changes, 1), "</pre>\n";
      if (isset($model['seed']) && is_array($model['seed'])) {
        $inserts = array();
        foreach($model['seed'] as $row) {
          $cnt = $this->count($model, array('criteria'=>$row));
          if (!$cnt) {
            //echo "need to insert: ", print_r($row, 1), "<br>\n";
            $inserts[] = $row;
          }
        }
        if (count($inserts)) {
          $this->insert($model, $inserts);
        }
      }

      // everything in sync?
      if ($haveAll && $noChanges) {
        return true;
      }
      $sql = 'alter table ' . $tableName . ' ';
      if (!$haveAll) {
        foreach($missing as $fieldName => $f) {
          $sql .= 'ADD ' . $fieldName . modelToSQL($f['type']);
        }
        if ($noChanges) {
          $sql = substr($sql, 0, -2); // strip last ", "
        }
      }
      if (!$noChanges) {
        foreach($changes as $fieldName => $f) {
          $sql .= 'MODIFY ' . $fieldName . modelToSQL($f['type']);
        }
        $sql = substr($sql, 0, -2); // strip last ", "
      }
      $sql .= '';
      //echo "mysql::autoupdate - sql[$sql]<br>\n";
      $res = mysqli_query($this->conn, $sql);
      $err = mysqli_error($this->conn);
      if ($err) {
        echo "<pre>mysql::autoupdate - update err[$err]<br>\nSQL[$sql]</pre>\n";
        return false;
      }
      return true;
    }
  }
  public function insert($rootModel, $recs) {
    $sql = $this->makeInsertQuery($rootModel, $recs);
    $res = $this->query($sql);
    if (!$res) return false;
    /*
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "<pre>mysql::insert - err[$err]\nSQL[$sql]</pre>\n";
      return false;
    }
    */
    $id = mysqli_insert_id($this->conn);
    // how does this handle multiple?
    $this->markWriten($rootModel);
    return $id;
  }

  public function update($rootModel, $urow, $options) {
    $sql = $this->makeUpdateQuery($rootModel, $urow, $options);
    //echo "sql[$sql]<br>\n";
    if ($rootModel['name'] !== 'table_tracker') {
      $this->markWriten($rootModel);
    }
    return $this->query($sql);
    /*
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "<pre>mysql::update - err[$err]\nSQL[$sql]</pre>\n";
      return false;
    }
    return true;
    */
  }

  public function delete($rootModel, $options) {
    $sql = $this->makeDeleteQuery($rootModel, $options);
    //echo "sql[$sql]<br>\n";
    $this->markWriten($rootModel);
    return $this->query($sql) ? true : false;
    /*
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "<pre>mysql::delete - err[$err]\nSQL[$sql]</pre>\n";
      return false;
    }
    return true;
    */
  }

  private function query($sql) {
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if (!$err) return $res;
    // handle gone away
    if ($err === 'MySQL server has gone away') {
      // clear cache
      if ($this->conn) {
        mysqli_close($this->conn);
        $this->conn = false;
      }
      //echo "MySQL:Reconnecting<br>\n"; flush();
      $reconnect_retries = 0;
      while(!$this->conn) {
        $this->conn = mysqli_connect($this->hostname, $this->username, $this->password);
        $reconnect_retries++;
        if ($reconnect_retries > 3) {
          echo "Could not reconnect, aborting<br>\n";
          exit(1);
          break;
        }
        // only delay retry if we didn't connect
        if (!$this->conn) {
          // 5, 20, 45
          sleep($reconnect_retries * $reconnect_retries * 5);
        }
      }
      // check connection
      mysqli_select_db($this->conn, $this->sql_current_db);
      return $this->query($sql);
    } else
    if ($err) {
      echo "<pre>mysql::query - err[$err]\nSQL[$sql]</ore>\n";
      return false;
    }
  }

  // options
  //   fields = if not set, give all fields, else expect an array
  //   criteria = if set, an array
  //              array(field, comparison, field/constant)
  public function find($rootModel, $options = false, $fields = '*') {
    $sql = $this->makeSelectQuery($rootModel, $options, $fields);
    //echo "sql[$sql]<br>\n";
    return $this->query($sql);
    /*
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "<pre>mysql::find - err[$err]\nSQL[$sql]</ore>\n";
      return false;
    }
    return $res;
    */
  }
  public function count($rootModel, $options = false) {
    $res = $this->find($rootModel, $options, 'count(*)');
    if (!$res) return -1;
    list($cnt) = mysqli_fetch_row($res);
    mysqli_free_result($res);
    return $cnt;
  }
  public function findById($rootModel, $id, $options = false) {
    $res = parent::findById($rootModel, $id, $options);
    if (!$res) return $res;
    $row = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return $row;
  }
  public function num_rows($res) {
    return mysqli_num_rows($res);
  }
  public function get_row($res) {
    return mysqli_fetch_assoc($res);
  }
  // a bit more optimized
  public function toArray($res) {
    if (!is_object($res) || !$res) {
      echo "<pre>non-resultSet passed into toArray [", gettype($res), "](",print_r($res, 1), ")</pre>\n";
      return array();
    }
    $arr = array();
    while($row = mysqli_fetch_assoc($res)) {
      $arr[] = $row;
    }
    return $arr;
  }
  public function free($res) {
    if (!is_object($res) || !$res) {
      echo "<pre>non-resultSet passed into free [", gettype($res), "](",print_r($res, 1), ")</pre>\n";
      return array();
    }
    return mysqli_free_result($res);
  }
  public function make_constant($value) {
    if ($value === true) return '1';
    if ($value === false) return '0';
    return '"'. addslashes($value) . '"';
  }
  public function groupAgg($field) {
    return 'group_concat(' . $field . ')';
  }
  public function unixtime($val = '') {
    return 'UNIX_TIMESTAMP(' . $val . ')';
  }
  public function randOrder() {
    return 'rand()';
  }
  /*
  public function unixtimeTs($val = '') {
    return 'FROM_TIMESTAMP(' . $val . ')';
  }
  */
}

?>