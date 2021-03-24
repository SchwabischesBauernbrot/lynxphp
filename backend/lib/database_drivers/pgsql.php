<?php
include 'base.php';

/*
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
*/

class pgsql_driver extends database_driver_base_class implements database_driver_base {
  function __construct() {
    $this->conn = null;
    $this->modelToSQL = array(
      'str'     => 'VARCHAR NOT NULL DEFAULT \'\',',
      'string'  => 'VARCHAR NOT NULL DEFAULT \'\,',
      'int'     => 'BIGINT NOT NULL DEFAULT 0,',
      'integer' => 'BIGINT NOT NULL DEFAULT 0,',
      'boolean' => 'Boolean DEFAULT false,',
      'bool'    => 'Boolean DEFAULT false,',
      'text'    => 'TEXT NOT NULL,', // maybe it should be null
      //'bigtext' => 'TEXT NOT NULL,',
    );
    $this->changeModelToSQL = array(
      'str'     => array('TYPE VARCHAR', 'SET NOT NULL DEFAULT \'\''),
      'string'  => array('TYPE VARCHAR', 'SET NOT NULL DEFAULT \'\''),
      'int'     => array('TYPE BIGINT', 'SET NOT NULL DEFAULT 0'),
      'integer' => array('TYPE BIGINT', 'SET NOT NULL DEFAULT 0'),
      'boolean' => array('TYPE Boolean DEFAULT false'),
      'bool'    => array('TYPE Boolean DEFAULT false'),
      'text'    => array('TYPE TEXT', 'SET NOT NULL'), // maybe it should be null
      //'bigtext' => 'TEXT NOT NULL,',
    );
    $this->sqlToModel = array(
      'bigint' => 'int',
      'integer' => 'int',
      'character varying' => 'str',
      'text' => 'text',
      'boolean' => 'bool',
    );
    $this->joinCount = 0;
    $this->btTables = false;
    $this->forceUnsetIdOnUpdate = true;
  }
  // easy
  public function connect_db($host, $user, $pass, $db, $port = 5432) {
    if ($this->conn !== null) {
      // throw exception?
      echo "Already connected<br>\n";
      return false;
    }
    $this->conn = pg_connect("host=$host user=$user password=$pass dbname=$db port=$port options='--client_encoding=UTF8' application_name=lynxphp connect_timeout=5");
    if (!$this->conn) {
      echo "Failed to connect [$user@$host]<br>\n";
      //echo "Connect debugging errno: " . pg_connect_errno() . "<br>\n";
      //echo "Connect debugging error: " . pg_connect_error() . "<br>\n";
      return false;
    }
    return true;
  }

  // build table if it doesn't exist...
  public function autoupdate($model) {
    // force json field in all models
    $model['fields']['json'] = array('type'=>'text');
    // get name
    $tableName = modelToTableName($model);
    $sql = 'SELECT column_name, data_type
      FROM information_schema.columns
      WHERE table_name = $1';
    $res = pg_query_params($this->conn, $sql, array($tableName));
    $err = pg_result_error($res);
    $rows = pg_num_rows($res);
    // do we need to create table?
    if (!$rows) {
      pg_free_result($res);
      // create sequence
      // generated always as identity seems to create a sequence
      /*
      $res = pg_query($this->conn, 'CREATE SEQUENCE ' . $tableName . '_seq');
      $err = pg_result_error($res);
      */
      //echo "create seq err[$err]<br>\n";
      // create table
      //echo "creating table ", $tableName, "\n";
      $sql = 'create table ' . $tableName. ' (';
      $idf = modelToId($model);
      $sql .= $idf.' BIGINT generated always as identity PRIMARY KEY, ';
      //$sql .= 'json MEDIUMTEXT NOT NULL, ';
      foreach($model['fields'] as $fieldName => $f) {
        $sql .= $fieldName . ' ' . $this->modelToSQL[strtolower($f['type'])] . ' ';
      }
      $sql .= 'created_at INTEGER NOT NULL, ';
      $sql .= 'updated_at INTEGER NOT NULL';
      $sql .= ')';
      // echo "$sql\n";
      $res = pg_query($this->conn, $sql);
      $err = pg_last_error($this->conn);
      if ($err) {
        echo "pgsql::autoupdate - create err[$err]<br>\nSQL[$sql]<br>\n";
        return false;
      }
      if (isset($model['seed']) && is_array($model['seed'])) {
        $this->insert($model, $model['seed']);
      }
      return true;
    } else {
      // describle table name failed...
      if ($err) echo "pgsql::autoupdate - describe err[$err]<br>\n";
      // get fields
      //echo "getting fields ", $tableName, "\n";
      $haveFields = array();
      if (is_bool($res)) {
        echo "pgsql::autoupdate - existing table, didnt like describe?!<br>\n";
        return;
      }
      while($row = pg_fetch_assoc($res)) {
        // Field, Type, Null, Key, Default, Extra
        //print_r($row);
        if (!isset($this->sqlToModel[$row['data_type']])) {
          echo "pgsql::autoupdate - sql type[", $row['data_type'], "] is missing<br>\n";
        }
        $haveFields[ $row['column_name'] ] = $this->sqlToModel[$row['data_type']];
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
          $sql .= 'ADD COLUMN ' . $fieldName . ' ' . $this->modelToSQL[$f['type']]. ' ';
        }
        $sql = substr($sql, 0, -2);
      }
      if (!$noChanges) {
        //echo "pgsql::autoupdate - Need to change<br>\n";
        foreach($changes as $fieldName => $f) {
          //echo "field[$fieldName] wantType[", $f['type'], "]<br>\n";
          if (is_array($this->changeModelToSQL[$f['type']])) {
            foreach($this->changeModelToSQL[$f['type']] as $f) {
              $sql .= 'ALTER COLUMN ' . $fieldName . ' ' . $f . ', ';
            }
          } else {
            $sql .= 'ALTER COLUMN ' . $fieldName . ' TYPE ' . $this->changeModelToSQL[$f['type']] . ' ';
          }
        }
        $sql = substr($sql, 0, -2);
      }
      $sql .= '';
      //echo "sql[$sql]<br>\n";
      $res = pg_query($this->conn, $sql);
      $err = pg_last_error($this->conn);
      if ($err) {
        echo "pgsql::autoupdate - change err[$err]<br>\nSQL[$sql]<br>\n";
        return false;
      }
      return true;
    }
  }

  public function insert($rootModel, $recs) {
    $sql = $this->makeInsertQuery($rootModel, $recs);
    //echo "sql[$sql]<br>\n";
    $res = pg_query($this->conn, $sql . ' returning ' . modelToId($rootModel));
    $err = pg_last_error($this->conn);
    if ($err) {
      echo "pgsql::insert - err[$err] [$sql]<br>\n";
      return false;
    }
    list($id) = pg_fetch_row($res);
    pg_free_result($res);

    //echo "res[$res]<br>\n";
    // how does this handle multiple?
    return $id;
  }

  public function update($rootModel, $urow, $options) {
    $sql = $this->makeUpdateQuery($rootModel, $urow, $options);
    //echo "sql[$sql]<br>\n";
    $res = pg_query($this->conn, $sql);
    $err = pg_last_error($this->conn);
    if ($err) {
      echo "pgsql::update - err[$err]<br>\n";
      return false;
    }
    return true;
  }

  public function delete($rootModel, $options) {
    $sql = $this->makeDeleteQuery($rootModel, $options);
    //echo "sql[$sql]<br>\n";
    $res = pg_query($this->conn, $sql);
    $err = pg_last_error($this->conn);
    if ($err) {
      echo "pgsql::delete - err[$err]<br>\n";
      return false;
    }
    return true;
  }

  private function typeCriteria($model, &$crit) {
    $fields = $model['fields']; // $fieldname => $f['type']
    foreach($crit as $k => $set) {
      if (is_numeric($k)) {
        $f = $set[0];
        //echo "[$k][", $fields[$f]['type'], "][", print_r($set, 1), "]<br>\n";
        if (isset($fields[$f]['type']) && $fields[$f]['type'] === 'bool') {
          //echo "Maybe fix up [$k][", print_r($set, 1), "]<br>\n";
          $crit[$k][2] = $crit[$k][2] ? 'true' : 'false';
        }
      } else {
        //echo "[$k][", $fields[$k]['type'], "]<br>\n";
        if (isset($fields[$k]['type']) && $fields[$k]['type'] === 'bool') {
          //echo "Maybe fix up [$k][", print_r($set, 1), "]<br>\n";
        }
      }
    }
  }

  // options
  //   fields = if not set, give all fields, else expect an array
  //   criteria = if set, an array
  //              array(field, comparison, field/constant)
  public function find($rootModel, $options = false, $fields = '*') {
    $sql = $this->makeSelectQuery($rootModel, $options, $fields);
    //echo "<pre>sql[$sql]</pre>\n";
    $res = pg_query($this->conn, $sql);
    //$err = pg_result_error($res);
    $err = pg_last_error($this->conn);
    if ($err) {
      echo "pgsql::find - err[$err]<br>\nSQL[<pre>$sql</pre>]<br>\n";
      return false;
    }
    return $res;
  }
  public function count($rootModel, $options = false) {
    $res = $this->find($rootModel, $options, 'count(*)');
    list($cnt) = pg_fetch_row($res);
    pg_free_result($res);
    return $cnt;
  }
  public function findById($rootModel, $id, $options = false) {
    $res = parent::findById($rootModel, $id, $options);
    $row = pg_fetch_assoc($res);
    pg_free_result($res);
    return $row;
  }
  public function num_rows($res) {
    return pg_num_rows($res);
  }
  // we could decode json here but most interactions don't use it
  // and json_decode / json_encode has some serious overhead..
  public function get_row($res) {
    return pg_fetch_assoc($res);
  }
  // a bit more optimized
  public function toArray($res) {
    if (!$res) {
      $trace = gettrace();
      echo "<pre>non-resultSet passed into toArray [", gettype($res), "](",print_r($res, 1), ") $trace</pre>\n";
      return array();
    }
    $arr = array();
    while($row = pg_fetch_assoc($res)) {
      $arr[] = $row;
    }
    return $arr;
  }
  public function free($res) {
    if (!$res) {
      $trace = gettrace();
      echo "<pre>non-resultSet passed into free [", gettype($res), "](",print_r($res, 1), ") $trace</pre>\n";
      return array();
    }
    return pg_free_result($res);
  }
  public function make_constant($value) {
    return '\''. pg_escape_string($value) . '\'';
  }
  public function groupAgg($field) {
    return 'string_agg(' . $field . ', \',\')';
  }
  public function unixtime($val = '') {
    if ($val) {
      // attempt to see if this works...
      return 'cast(extract(epoch from ' . $val . ') as integer)';
    }
    return 'cast(extract(epoch from CURRENT_TIMESTAMP) as integer)';
  }
}

?>
