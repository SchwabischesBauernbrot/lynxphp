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
  /*
  var $modelToSQL;
  var $changeModelToSQL;
  var $sqlToModel;
  var $joinCount;
  var $btTables;
  var $forceUnsetIdOnUpdate;
  */
  var $changeModelToSQL;
  function __construct() {
    parent::__construct();
    // datetime would have been nice to have to make database easier to work with
    // why don't we have datetime?
    $this->modelToSQL = array(
      'str'     => 'VARCHAR NOT NULL DEFAULT \'\',',
      'string'  => 'VARCHAR NOT NULL DEFAULT \'\',',
      'int'     => 'BIGINT DEFAULT 0 NOT NULL,',
      'integer' => 'BIGINT DEFAULT 0 NOT NULL,',
      'boolean' => 'Boolean DEFAULT false,',
      'bool'    => 'Boolean DEFAULT false,',
      'text'    => 'TEXT NOT NULL,', // maybe it should be null
      // take more processing power but easier for humans to read in the tables
      //'datetime' => 'timestamp NOT NULL DEFAULT \'0001-01-01\',',  // NOW() is also available
      //'bigtext' => 'TEXT NOT NULL,',
    );
    $this->changeModelToSQL = array(
      'str'     => array('TYPE VARCHAR', 'SET NOT NULL', 'SET DEFAULT \'\''),
      'string'  => array('TYPE VARCHAR', 'SET NOT NULL', 'SET DEFAULT \'\''),
      // Using EXTRACT(EPOCH FROM {{field}})
      'int'     => array('DROP DEFAULT', 'TYPE BIGINT', 'SET NOT NULL', 'SET DEFAULT 0'),
      'integer' => array('DROP DEFAULT', 'TYPE BIGINT', 'SET NOT NULL', 'SET DEFAULT 0'),
      'boolean' => array('TYPE Boolean DEFAULT false'),
      'bool'    => array('TYPE Boolean DEFAULT false'),
      'text'    => array('TYPE TEXT', 'SET NOT NULL'), // maybe it should be null
      //'datetime' => array('TYPE timestamp', 'SET NOT NULL DEFAULT \'0001-01-01\''),
      //'bigtext' => 'TEXT NOT NULL,',
    );
    $this->sqlToModel = array(
      'bigint' => 'int',
      'integer' => 'int',
      'character varying' => 'str',
      'text' => 'text',
      'boolean' => 'bool',
      //'timestamp without time zone' => 'datetime',
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
    $this->registeredTables[] = $tableName;
    //echo "auto[", $model['name'] ,"]\n";
    if (!empty($model['disableTracker'])) {
      if (!in_array($model['name'], $this->dontTrackTables)) {
        $this->dontTrackTables[] = $model['name'];
      }
    }
    // DEV_MODE is only a fe concept atm...
    if (0) {
      // just make things slower...
      $sql = 'SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = $1';
      //echo "sql[$sql]<br>\n";
      $res = pg_query_params($this->conn, $sql, array($tableName));
    } else {
      // much faster? is it tho? 20ms faster
      $sql = 'SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = \'' . $tableName . '\'';
      //echo "sql[$sql]<br>\n";
      $res = pg_query($this->conn, $sql);
    }
    $err = pg_result_error($res);
    if ($err) echo "pgsql::autoupdate - err[$err]\n";
    $rows = pg_num_rows($res);
    //echo "rows[$rows] for [$tableName]<br>\n";
    // do we need to create table?
    $idf = modelToId($model);
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
        echo "pgsql::autoupdate - existing table, didn't like describe?!<br>\n";
        return;
      }
      $removed = array();
      $hasRemoves = false;
      while($row = pg_fetch_assoc($res)) {
        // column_name, data_type
        //echo '<pre>', print_r($row, 1), "</pre>\n";
        if (!isset($this->sqlToModel[$row['data_type']])) {
          echo "pgsql::autoupdate - sql type[", $row['data_type'], "] is missing<br>\n";
        }
        $haveFields[ $row['column_name'] ] = $this->sqlToModel[$row['data_type']];
        if (!isset($model['fields'][$row['column_name']])) {
          if ($row['column_name'] !== 'created_at' && $row['column_name'] !== 'updated_at' &&  $row['column_name'] !== $idf) {
            $removed[] = $row['column_name'];
            $hasRemoves = true;
          }
        }
      }
      pg_free_result($res);
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
      //echo "<pre>$tableName: Changes", print_r($changes, 1), "</pre>\n";

      // everything in sync?
      if ($haveAll && $noChanges && !$hasRemoves) {
        //echo "done - no changes\n";
        return true;
      }
      $sql = 'alter table ' . $tableName . ' ';
      if (!$haveAll) {
        //echo "Need to create<br>\n";
        // ALTER TABLE
        foreach($missing as $fieldName => $f) {
          // ADD
          //echo "field[$fieldName]<br>\n";
          $sql .= 'ADD COLUMN ' . $fieldName . ' ' . $this->modelToSQL[$f['type']]. ' '; // . ', ';
        }
      }
      if (!$noChanges) {
        //echo "pgsql::autoupdate - Need to change<br>\n";
        foreach($changes as $fieldName => $f) {
          //echo "field[$fieldName] wantType[", $f['type'], "]<br>\n";
          if (is_array($this->changeModelToSQL[$f['type']])) {
            foreach($this->changeModelToSQL[$f['type']] as $f) {
              //$f = str_replace('{{field}}', $fieldName, $f);
              $sql .= 'ALTER COLUMN ' . $fieldName . ' ' . $f . ', ';
            }
          } else {
            $sql .= 'ALTER COLUMN ' . $fieldName . ' TYPE ' . $this->changeModelToSQL[$f['type']] . ', ';
          }
        }
      }
      if ($hasRemoves) {
        //echo "<pre>$tableName: removed columns", print_r($removed, 1), "</pre>\n";
        foreach($removed as $f) {
          // DROP
          //echo "field[$fieldName]<br>\n";
          $sql .= 'DROP COLUMN ' . $f . ', ';
        }
      }
      $sql = substr($sql, 0, -2);
      //$sql .= '';
      //echo "sql[$sql]<br>\n";
      $res = pg_query($this->conn, $sql);
      $err = pg_last_error($this->conn);
      if ($err) {
        echo "pgsql::autoupdate - change err[$err]<br>\nSQL[$sql]<br>\n";
        return false;
      }
      // now sync seeds
      if (isset($model['seed']) && is_array($model['seed'])) {
        $inserts = array();
        // FIXME: optimize to IN query ...
        foreach($model['seed'] as $row) {
          $cnt = $this->count($model, array('criteria' => $row));
          if (!$cnt) {
            //echo "need to insert: ", print_r($row, 1), "<br>\n";
            $inserts[] = $row;
          }
        }
        if (count($inserts)) {
          $this->insert($model, $inserts);
        }
      }
      return true;
    }
  }

  function query($sqls) {
    if (!is_array($sqls)) $sqls = array($sqls);
    if (count($sqls) === 1) {
      //echo "SQL[", $sqls[0], "]<br>\n";
      $res = pg_query($this->conn, $sqls[0]);
      $err = pg_last_error($this->conn);
      if ($err) {
        echo "pgsql::insert - err[$err] [", $sqls[0], "]<br>\n";
        return false;
      }
      return $res;
    } else {
      if (pg_connection_busy($this->conn)) {
        echo "PostGres is busy<br>\n";
        return false;
      }
      $line = join('; ', $sqls) . ';';
      pg_trace('/tmp/trace.log', 'w', $this->conn);
      pg_send_query($this->conn, $line);
      return pg_get_result($this->conn); // $res
      /*
      $results = array();
      while($res = pg_get_result($this->conn)) {
        $results[] = $res;
      }
      pg_untrace($this->conn);
      return $results;
      */
    }
  }

  public function insert($rootModel, $recs) {
    if (!count($recs)) {
      echo "pgsql::insert - no records passed in<br>\n";
      return;
    }
    $sql = $this->makeInsertQuery($rootModel, $recs);
    //echo "sql[$sql]<br>\n";

    // how does this handle multiple?
    if (0) {
      // doesn't return the id...
      $sqls = array($sql, 'select * from table_trackers;');
      $res = $this->query($sqls);
      list($id) = pg_fetch_row($res);
      pg_free_result($res);
      $res2 = pg_get_result($this->conn);
      pg_free_result($res2);
      pg_untrace($this->conn);
    } else {
      $res = pg_query($this->conn, $sql . ' returning ' . modelToId($rootModel));
      $err = pg_last_error($this->conn);
      if ($err) {
        echo "pgsql::insert - err[$err] [$sql]<br>\n";
        return false;
      }
      list($id) = pg_fetch_row($res);
      pg_free_result($res);
      if (!in_array($rootModel['name'], $this->dontTrackTables)) {
        // multiple won't matter here
        //echo "insert[", $rootModel['name'], "]<br>\n";
        $this->markWriten($rootModel);
      }
    }
    //echo "res[$res]<br>\n";
    return $id;
  }

  public function update($rootModel, $urow, $options) {
    $sql = $this->makeUpdateQuery($rootModel, $urow, $options);
    //echo "sql[$sql]<br>\n";
    $res = pg_query($this->conn, $sql);
    $err = pg_last_error($this->conn);
    if ($err) {
      echo "pgsql::update - err[$err]<br>\nsql[$sql]<br>\n";
      return false;
    }
    //echo "[", $rootModel['name'], "]" , print_r($this->dontTrackTables, 1), "\n";
    if (!in_array($rootModel['name'], $this->dontTrackTables)) {
      //echo "tracking[", $rootModel['name'], "]<br>\n";
      // multiple won't matter here
      //echo "update[", $rootModel['name'], "]<br>\n";
      $this->markWriten($rootModel);
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
    if (!in_array($rootModel['name'], $this->dontTrackTables)) {
      // multiple won't matter here
      //echo "delete[", $rootModel['name'], "]<br>\n";
      $this->markWriten($rootModel);
    }
    return true;
  }

  // not currently used
  /*
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
  */

  // options
  //   fields = if not set, give all fields, else expect an comma separate string
  //   criteria = if set, an array
  //              array(field, comparison, field/constant)
  public function find($rootModel, $options = false, $fields = '*') {
    $sql = $this->makeSelectQuery($rootModel, $options, $fields);
    $res = pg_query($this->conn, $sql);
    //$err = pg_result_error($res);
    $err = pg_last_error($this->conn);
    if ($err) {
      $trace = gettrace();
      echo "pgsql::find - err[$err]<br>\nSQL[<pre>$sql</pre>]", $trace, "<br>\n";
      return false;
    }
    return $res;
  }
  public function count($rootModel, $options = false) {
    // in postgres, if you try to order the count, you'll need a group by
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
    if ($value === true) return 'true';
    if ($value === false) return 'false';
    return '\''. pg_escape_string($value) . '\'';
  }
  public function groupAgg($field) {
    return 'string_agg(' . $field . ', \',\')';
  }

  public function unixtime($val = '') {
    if ($val === '') $val = 'CURRENT_TIMESTAMP';
    return 'cast(extract(epoch from ' . $val . ') as integer)';
  }
  public function randOrder() {
    return 'random()';
  }
  /*
  public function unixtimeTs($val = '') {
    if ($val === '') $val = 'CURRENT_TIMESTAMP';
    return '(\'epoch\'::timestamptz + ' . $val . ' * \'1 second\'::interval)';
  }
  */
  // how about something that just returns 't' so we can use it in queries too
  // tho 1 seems to work for both mysql/pgsql bools
  public function isTrue($val) {
    return $val === 't';
  }
  public function isFalse($val) {
    return $val === 'f';
  }
}

?>