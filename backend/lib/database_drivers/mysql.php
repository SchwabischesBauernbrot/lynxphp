<?php
include 'base.php';

// FIXME: convert to array
function modelToSQL($type) {
  $sql = '';
  switch($type) {
    case 'string':
    case 'str': // official
      $sql = ' VARCHAR(255) NOT NULL, ';
    break;
    case 'integer':
    case 'int': // official
      $sql = ' BIGINT NOT NULL, ';
    break;
    case 'boolean':
    case 'bool': // official
      $sql = ' TINYINT UNSIGNED NOT NULL, ';
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
  }
  // direct
  public function connect($host, $user, $pass, $port = 0) {
    if ($this->conn !== null) {
      // throw exception?
      echo "Already connected<br>\n";
      return false;
    }
    $this->conn = mysqli_connect($host, $user, $pass);
    if (!$this->conn) {
      echo "Failed to connect<br>\n";
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
      return false;
    }
    return true;
  }
  // direct
  public function switch_db($db) {
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
  public function autoupdate($model) {
    // force json field in all models
    $model['fields']['json'] = array('type'=>'text');
    // get name
    $tableName = modelToTableName($model);
    $res = mysqli_query($this->conn, 'describe ' . $tableName);
    $err = mysqli_error($this->conn);
    if ($err && strpos($err, 'doesn\'t exist') !== false) {
      // create table
      //echo "creating table ", $tableName, "\n";
      $sql = 'create table ' . $tableName. ' (';
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
        echo "err[$err]<br>\n";
        return false;
      }
      return true;
    } else {
      if ($err) echo "err[$err]<br>\n";
      // get fields
      //echo "getting fields ", $tableName, "\n";
      $haveFields = array();
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
      //echo "<pre>Changes", print_r($changes, 1), "</pre>\n";
      // everything in sync?
      if ($haveAll && $noChanges) {
        return true;
      }
      $sql = 'alter table ' . $tableName . ' ';
      if (!$haveAll) {
        echo "Need to create<br>\n";
        // ALTER TABLE
        foreach($missing as $fieldName => $f) {
          // ADD
          echo "field[$fieldName]<br>\n";
          $sql .= 'ADD ' . $fieldName . ' ' .modelToSQL($f['type']);
        }
        $sql = substr($sql, 0, -2);
      }
      if (!$noChanges) {
        echo "Need to change<br>\n";
        foreach($changes as $fieldName => $f) {
          echo "field[$fieldName] wantType[", $f['type'], "]<br>\n";
          //$sql .= 'MODIFY ' . $fieldName . ' ' .modelToSQL($f['type']);
        }
      }
      $sql .= '';
      echo "sql[$sql]<br>\n";
      $res = mysqli_query($this->conn, $sql);
      $err = mysqli_error($this->conn);
      if ($err) {
        echo "err[$err]<br>\n";
        return false;
      }
      return true;
    }
  }
  public function make_constant($value) {
    return '"'. addslashes($value) . '"';
  }
  public function insert($rootModel, $recs) {
    $tableName = modelToTableName($rootModel);
    $date = time();
    $recs[0]['json'] = '{}';
    $recs[0]['created_at'] = $date;
    $recs[0]['updated_at'] = $date;
    $fields = join(',', array_keys($recs[0]));
    $sql = 'insert into ' . $tableName . ' (' . $fields . ') values';
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
      echo "err[$err]<br>\n";
      return false;
    }
    // how does this handle multiple?
    return mysqli_insert_id($this->conn);

  }
  public function update($rootModel, $urow, $options) {
    $tableName = modelToTableName($rootModel);
    $date = time();
    $urow['updated_at'] = $date;
    $sets = array();
    foreach($urow as $f=>$v) {
      if (is_array($v)) {
        $val = $v;
      } else {
        $val = $this->make_constant($v);
      }
      $sets[] = $f . '=' . $val;
    }
    $sql = 'update ' .$tableName . ' set '. join(', ', $sets);
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria']);
    }
    //echo "sql[$sql]<br>\n";
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "err[$err]<br>\n";
      return false;
    }
    return true;
  }
  public function delete($rootModel, $options) {
    $tableName = modelToTableName($rootModel);

    $sql = 'delete from ' .$tableName;
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria']);
    //} else {
      // a warning? or something to prevent total table loss if typo...
    }
    //echo "sql[$sql]<br>\n";
    $res = mysqli_query($this->conn, $sql);
    $err = mysqli_error($this->conn);
    if ($err) {
      echo "err[$err]<br>\n";
      return false;
    }
    return true;
  }
  // options
  //   fields = if not set, give all fields, else expect an array
  //   criteria = if set, an array
  //              array(field, comparison, field/constant)
  public function find($rootModel, $options = false, $fields = '*') {
    $tableName = modelToTableName($rootModel);
    $sql = 'select '. $fields . ' from ' . $tableName;
    $joins = array();
    if (!empty($rootModel['children']) && is_array($rootModel['children'])) {
      foreach($rootModel['children'] as $join) {
        $field = modelToId($rootModel);
        $joinTable = modelToTableName($join['model']);
        $joins[] = (empty($join['type']) ? '' : $join['type'] . ' ' ) . ' join ' .
          $joinTable . ' on ' .
          $joinTable . '.' . $field . '=' .
          $tableName . '.' . $field;
      }
      if (count($joins)) {
        $sql .= ' ' . join(' ', $joins);
      }
    }
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria'], count($joins) ? $tableName : '');
    }
    if (isset($options['order'])) {
      $defAlias = count($joins) ? $tableName : '';
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
      echo "err[$err]<br>\n";
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
    return mysqli_fetch_assoc(parent::findById($rootModel, $id, $options));
  }
  public function num_rows($res) {
    return mysqli_num_rows($res);
  }
  public function get_row($res) {
    return mysqli_fetch_assoc($res);
  }
  // a bit more optimized
  public function toArray($res) {
    $arr = array();
    while($row = mysqli_fetch_assoc($res)) {
      $arr[] = $row;
    }
    return $arr;
  }
}

?>
