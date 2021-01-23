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
  }
  return $type;
}

class mysql_driver extends database_driver_base_class implements database_driver_base {
  function __construct() {
    $this->conn = null;
    $this->modelToSQL = array();
    $this->sqlToModel = array();
    $this->joinCount = 0;
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
    return true;
  }
  // direct
  private function switch_db($db) {
    // FIXME check result code
    mysqli_select_db($this->conn, $db);
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
    //echo "Checking [$tableName]<br>\n";
    $res = mysqli_query($this->conn, 'describe `' . $tableName. '`');
    $err = mysqli_error($this->conn);
    // do we need to create table?
    if ($err && strpos($err, 'doesn\'t exist') !== false) {
      // don't need to clean failed query in mysql
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
        //echo "Need to create<br>\n";
        // ALTER TABLE
        foreach($missing as $fieldName => $f) {
          // ADD
          //echo "field[$fieldName]<br>\n";
          $sql .= 'ADD ' . $fieldName . modelToSQL($f['type']);
        }
        $sql = substr($sql, 0, -2);
      }
      if (!$noChanges) {
        //echo "mysql::autoupdate - Need to change[$tablename]<br>\n";
        foreach($changes as $fieldName => $f) {
          echo "field[$fieldName] wantType[", $f['type'], "]<br>\n";
          $sql .= 'MODIFY ' . $fieldName . modelToSQL($f['type']);
        }
        $sql = substr($sql, 0, -2);
      }
      $sql .= '';
      //echo "sql[$sql]<br>\n";
      $res = mysqli_query($this->conn, $sql);
      $err = mysqli_error($this->conn);
      if ($err) {
        echo "mysql::autoupdate - update err[$err]<br>\nSQL[$sql]\n";
        return false;
      }
      return true;
    }
  }
  public function insert($rootModel, $recs) {
    $tableName = modelToTableName($rootModel);
    $date = time();
    $recs[0]['json'] = '{}';
    $recs[0]['created_at'] = $date;
    $recs[0]['updated_at'] = $date;
    $fields = join(',', array_keys($recs[0]));
    $sql = 'insert into `' . $tableName . '` (' . $fields . ') values';
    $sets = array();
    foreach($recs as $rec) {
      $cleanArr = array();
      $rec['json'] = '{}';
      $rec['created_at'] = $date;
      $rec['updated_at'] = $date;
      foreach($rec as $val) {
        if (is_array($val)) {
          $cleanArr[] = $val;
        } else {
          $cleanArr[] = $this->make_constant($val);
        }
      }
      $sets[] = '(' . join(',', $cleanArr) . ')';
    }
    $sql .= join(',', $sets);
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "mysql::insert - err[$err]<br>\n";
      return false;
    }
    // how does this handle multiple?
    return mysqli_insert_id($this->conn);

  }
  public function update($rootModel, $urow, $options) {
    global $now;
    $tableName = modelToTableName($rootModel);
    $sets = array(
      'updated_at' => 'updated_at = ' . $now,
    );
    if (!empty($urow['json'])) $urow['json'] = json_encode($urow['json']);
    foreach($urow as $f=>$v) {
      // updates are always assignments (=, never </>=)
      if (is_array($v)) {
        $val = $v[0];
      } else {
        $val = $this->make_constant($v);
      }
      $sets[] = $f . '=' . $val;
    }
    $sql = 'update `' .$tableName . '` set '. join(', ', $sets);
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria']);
    }
    //echo "sql[$sql]<br>\n";
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "mysql::update - err[$err]<br>\n";
      return false;
    }
    return true;
  }
  public function delete($rootModel, $options) {
    $tableName = modelToTableName($rootModel);

    $sql = 'delete from `' .$tableName . '`';
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria']);
    //} else {
      // a warning? or something to prevent total table loss if typo...
    }
    //echo "sql[$sql]<br>\n";
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "mysql::delete - err[$err]<br>\n";
      return false;
    }
    return true;
  }

  private function handleJoin($models, $data, $tableName, $useField = '') {
    foreach($models as $join) {
      // same field in both tables
      if ($useField) {
        // use from root table
        $field = $useField;
      } else {
        // calculate off joined table
        $field = modelToId($join['model']);
      }
      // should be the same field
      $rootField = $field;
      $joinField = $field;
      // unles...
      if (!empty($join['srcField'])) $rootField = $join['srcField'];
      if (!empty($join['useField'])) $joinField = $join['useField'];
      // set up join table/alias
      $joinTable = modelToTableName($join['model']);
      $joinAlias = $joinTable;
      if ($joinTable === $tableName) {
        $this->joinCount++;
        $joinAlias = 'jt' . $this->joinCount;
        $joinTable .= ' as ' . $joinAlias;
      }
      $joinStr = (empty($join['type']) ? '' : $join['type'] . ' ' ) . ' join ' .
        $joinTable . ' on (' .
        $joinAlias . '.' . $joinField . '=' .
        $tableName . '.' . $rootField;
      if (!empty($join['where'])) {
        $joinStr .= ' and ' . $this->build_where($join['where'], $joinAlias);
      }
      $data['joins'][] = $joinStr . ')';
      // support an empty array
      if (isset($join['pluck']) && is_array($join['pluck'])) {
        // probably integrate the alias...
        $clean = str_replace('ALIAS', $joinAlias, $join['pluck']);
        $data['fields'] = array_merge($data['fields'], $clean);
      } else {
        // if no pluck, then grab all
        $data['fields'][] = '`' . $joinAlias . '`.*';
      }
      if (!empty($join['groupby'])) {
        $data['groupbys'] = array_merge($data['groupbys'], explode(',', $join['groupby']));
      }
      if (!empty($join['having'])) {
        $data['having'] .= ' ' . str_replace('ALIAS', $joinAlias, $join['having']);
      }
      $data = $this->expandJoin($join['model'], $data);
    }
    return $data;
  }

  private function expandJoin($rootModel, $data) {
    $tableName = modelToTableName($rootModel);
    if (!empty($rootModel['children']) && is_array($rootModel['children'])) {
      $data = $this->handleJoin($rootModel['children'], $data, $tableName, modelToId($rootModel));
    }
    // use id field from parent table (groupid instead of usergroupid)
    if (!empty($rootModel['parents']) && is_array($rootModel['parents'])) {
      $data = $this->handleJoin($rootModel['parents'], $data, $tableName);
    }
    return $data;
  }

  // options
  //   fields = if not set, give all fields, else expect an array
  //   criteria = if set, an array
  //              array(field, comparison, field/constant)
  public function find($rootModel, $options = false, $fields = '*') {
    $tableName = modelToTableName($rootModel);
    if (!$tableName) {
      echo "<pre>model[", print_r($rootModel, 1), "] is missing a name</pre>\n";
      return;
    }
    $data = array(
      'joins'    => array(),
      'groupbys' => array(),
      'having'   => '',
      'fields'   => array(),
    );
    $data = $this->expandJoin($rootModel, $data);
    // FIXME: renaming support
    $useFields = array_merge(array_map(function($f) use ($data, $tableName) {
      return (count($data['joins']) ? '`'. $tableName . '`.' : '') . $f;
    }, explode(',', $fields)), $data['fields']);
    $sql = 'select '. join(',', $useFields) . ' from `' . $tableName . '`';
    $useAlias = '';
    if (count($data['joins'])) {
      $sql .= ' ' . join(' ', $data['joins']);
      $useAlias = $tableName;
    }
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria'], $useAlias);
    }
    if (count($data['groupbys'])) {
      $sql .= ' group by ' . join(',', $data['groupbys']);
    }
    if (!empty($data['having'])) {
      $sql .= ' having ' . $data['having'];
    }
    if (isset($options['order'])) {
      $defAlias = count($data['joins']) ? $tableName : '';
      $alias = $defAlias ? $defAlias . '.' : '';
      $sql .= ' order by ' . $alias . $options['order'];
    }
    if (isset($options['limit'])) {
      $sql .= ' limit ' . $options['limit'];
    }
    //echo "sql[$sql]<br>\n";
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "mysql::find - err[$err]<br>\nSQL[$sql]<br>\n";
      return false;
    }
    return $res;
  }
  public function count($rootModel, $options = false) {
    $res = $this->find($rootModel, $options, 'count(*)');
    list($cnt) = mysqli_fetch_row($res);
    return $cnt;
  }
  public function findById($rootModel, $id, $options = false) {
    $res = parent::findById($rootModel, $id, $options);
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
    return '"'. addslashes($value) . '"';
  }
  public function groupAgg($field) {
    return 'group_concat(' . $field . ')';
  }
  public function unixtime() {
    return 'UNIX_TIMESTAMP()';
  }
}

?>
