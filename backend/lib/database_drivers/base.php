<?php

interface database_driver_base {
  public function connect_db($host, $user, $pass, $db, $port = 0);
  // easy
  public function autoupdate($model);
  public function build_where($criteria);
  public function make_constant($value);
  public function make_direct($value);
  public function insert($rootModel, $recs);
  public function update($rootModel, $urow, $options);
  public function delete($rootModel, $options);
  // options
  //   fields = if not set, give all fields, else expect an array
  //   criteria = if set, an array
  //              array(field, comparison, field/constant)
  public function find($rootModel, $options = false);
  public function count($rootModel, $options = false);
  public function findById($rootModel, $id, $options = false);
  public function updateById($rootModel, $id, $row, $options = false);
  public function deleteById($rootModel, $id, $options = false);
  // result functions
  public function num_rows($res);
  public function get_row($res);
  public function toArray($res);
  public function groupAgg($field);
}

class database_driver_base_class {
  function __construct() {
    $this->conn = null;
    $this->modelToSQL = array();
    $this->sqlToModel = array();
    $this->subselectCounter = 0;
  }
  public function connect_db($host, $user, $pass, $db, $port = 0) {
    if (!$this->connect($host, $user, $pass, $port)) {
      return false;
    }
    return $this->switch_db($db);
  }
  public function make_constant($value) {
    return '"'. addslashes($value) . '"';
  }
  public function make_direct($value) {
    return array($value);
  }
  // convert array into where clause
  public function build_where($criteria, $defAlias = '') {
    // field, comparator, field
    $sets = array();
    $alias = $defAlias ? $defAlias . '.' : '';
    foreach($criteria as $k => $set) {
      if (is_numeric($k)) {
        // flexible criteria
        $one = $alias;
        if (is_array($set[0])) {
          $one .= $set[0][0];
        } else {
          // $this->make_constant()
          // postgres can't put quotes around fields
          $one .= $set[0];
        }
        $operand = $set[1];
        if ($operand === 'IN') {
          // list operands
          if (!is_array($set[2])) {
            // auto promote to array or abort?
            return false;
          }
          $inSet = $set[2];
          // we will need to address this...
          /*
          if ($defAlias) {
            $inSet = array();
            foreach($set[2] as $cond) {
              $inSet[] = $defAlias . '.' . $cond;
            }
          }
          */
          $sets[] = $one . ' ' . $operand . ' (' . join(',', $inSet). ')';
        } else {
          if (is_array($set[2])) {
            $sets[] = $one . ' ' . $operand . ' ' . $set[2][0];
          } else {
            $sets[] = $one . ' ' . $operand . ' ' . $this->make_constant($set[2]);
          }
        }
      } else {
        // named key
        if (is_array($set)) {
          // direct
          $sets[] = $alias . $k . '=' . $set;
        } else {
          // default: safe
          $sets[] = $alias . $k . '=' . $this->make_constant($set);
        }
      }
    }
    return join(' AND ', $sets);
  }

  protected function makeInsertQuery($rootModel, $recs) {
    global $now;
    $tableName = modelToTableName($rootModel);
    $date = $now;
    $recs[0]['json'] = '{}';
    $recs[0]['created_at'] = $now;
    $recs[0]['updated_at'] = $now;
    $fields = join(',', array_keys($recs[0]));
    if ($this->btTables) {
      $sql = 'insert into `' . $tableName . '` (' . $fields . ') values';
    } else {
      $sql = 'insert into ' . $tableName . ' (' . $fields . ') values';
    }
    $sets = array();
    foreach($recs as $rec) {
      $cleanArr = array();
      $rec['json'] = '{}';
      $rec['created_at'] = $now;
      $rec['updated_at'] = $now;
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
    return $sql;
  }

  protected function makeUpdateQuery($rootModel, $urow, $options) {
    global $now;
    $tableName = modelToTableName($rootModel);
    $sets = array(
      'updated_at' => 'updated_at = ' . $now,
    );
    if (!empty($urow['json'])) {
      if (!is_string($urow['json'])) {
        $urow['json'] = json_encode($urow['json']);
      }
    }
    foreach($urow as $f=>$v) {
      // updates are always assignments (=, never </>=)
      if (is_array($v)) {
        $val = $v[0];
      } else {
        $val = $this->make_constant($v);
      }
      $sets[$f] = $f . '=' . $val;
    }
    if ($this->forceUnsetIdOnUpdate) {
      $idf = modelToId($rootModel);
      unset($sets[$idf]);
    }
    if ($this->btTables) {
      $sql = 'update `' .$tableName . '` set '. join(', ', $sets);
    } else {
      $sql = 'update ' .$tableName . ' set '. join(', ', $sets);
    }
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria']);
    }
    return $sql;
  }

  protected function makeDeleteQuery($rootModel, $options) {
    $tableName = modelToTableName($rootModel);
    if ($this->btTables) {
      $sql = 'delete from `' .$tableName . '`';
    } else {
      $sql = 'delete from ' .$tableName;
    }
    if (isset($options['critera']) && !isset($options['criteria'])) {
      $options['criteria'] = $options['critera'];
    }
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria']);
    //} else {
      // a warning? or something to prevent total table loss if typo...
    }
    return $sql;
  }

  // what's the minium in join? model
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
      // unless...
      if (!empty($join['srcField'])) $rootField = $join['srcField'];
      if (!empty($join['useField'])) $joinField = $join['useField'];
      // set up join table/alias
      $joinTable = modelToTableName($join['model']);
      if (!empty($join['tableOverride'])) $tableName = $join['tableOverride'];
      if ($this->btTables) {
        $joinTable = '`' . $joinTable . '`';
      }
      if ($this->btTables) {
        $tableName = '`' . $tableName . '`';
      }
      /*
      if ($this->btTables) {
        $joinAlias = '`' . $joinTable . '`';
      } else {
      */
        $joinAlias = $joinTable;
      //}

      if (!empty($join['alias'])) {
        $joinAlias = $join['alias'];
      } else
      // FIXME: well if it's used anywhere else...
      if ($joinTable === $tableName) {
        $this->joinCount++;
        $joinAlias = 'jt' . $this->joinCount;
      }
      /*
      if ($this->btTables) {
        if ($joinTable === $joinAlias) {
          $joinAlias = '`' . $joinAlias . '`';
        }
        $joinTable = '`' . $joinTable . '`';
      }
      */
      if ($joinTable !== $joinAlias) {
        // maybe should be a different var
        $joinTable .= ' as ' . $joinAlias;
      }
      $joinStr = (empty($join['type']) ? '' : $join['type'] . ' ' ) . 'join ';
      $joinStr .= $joinTable . ' on (';
      if (!empty($join['on'])) {
        $onAlias = $joinAlias;
        if (!empty($join['onAlias'])) $onAlias = $join['onAlias'];
        $joinStr .= $this->build_where($join['on'], $onAlias);
      } else {
        $joinStr .=
          $joinAlias . '.' . $joinField . '=' .
          $tableName . '.' . $rootField;
        if (!empty($join['where'])) {
          $joinStr .= ' and ' . $this->build_where($join['where'], $joinAlias);
        }
      }
      $data['joins'][] = $joinStr . ')';
      // support an empty array
      if (isset($join['pluck']) && is_array($join['pluck'])) {
        // probably integrate the alias...
        $clean = str_replace('ALIAS', $joinAlias, $join['pluck']);
        $data['fields'] = array_merge($data['fields'], $clean);
      } else {
        // if no pluck, then grab all
        /*
        if ($this->btTables) {
          $data['fields'][] = '`' . $joinAlias . '`.*';
        } else {
        */
          $data['fields'][] = $joinAlias . '.*';
        //}
      }
      if (!empty($join['groupby'])) {
        // this is a problem...
        // not longer a string...
        $useGroupBys = $join['groupby'];
        if (!is_array($useGroupBys)) {
          echo "Warning, string into groupby, fix this!<br>\n";
          //$useGroupBys = explode(',', $useGroupBys);
        }
        if ($this->btTables) {
          $useGroupBys = array_map(function($val) use ($tableName) {
            return str_replace('MODEL.', $tableName . '.', $val);
          }, $useGroupBys);
          //echo "<pre>", print_r($useGroupBys, 1), "</pre>\n";
        }
        $data['groupbys'] = array_merge($data['groupbys'], $useGroupBys);
      }
      if (!empty($join['having'])) {
        $data['having'] .= ' ' . str_replace('ALIAS', $joinAlias, $join['having']);
      }
      $data = $this->expandJoin($join['model'], $data);
    }
    //echo "<pre>", print_r($data, 1), "</pre>\n";
    return $data;
  }

  protected function expandJoin($rootModel, $data) {
    if (isset($rootModel['query'])) {
      $tableName = 't';
    } else {
      $tableName = modelToTableName($rootModel);
    }
    if (!empty($rootModel['children']) && is_array($rootModel['children'])) {
      if (isset($rootModel['query'])) {
        if (isset($rootModel['model']['query'])) {
          $idf = modelToId($rootModel['model']['model']);
        } else {
          $idf = modelToId($rootModel['model']);
        }
      } else {
        $idf = modelToId($rootModel);
      }
      $data = $this->handleJoin($rootModel['children'], $data, $tableName, $idf);
    }
    // use id field from parent table (groupid instead of usergroupid)
    if (!empty($rootModel['parents']) && is_array($rootModel['parents'])) {
      $data = $this->handleJoin($rootModel['parents'], $data, $tableName);
    }
    return $data;
  }

  protected function makeSelectQuery($rootModel, $options = false, $fields = '*') {
    if (isset($rootModel['query'])) {
      $tableAlias = 't' . $this->subselectCounter;
      $this->subselectCounter++;
      $tableName = '(' . $rootModel['query'] . ') as ' . $tableAlias;
    } else {
      $tableName = modelToTableName($rootModel);
      if (!$tableName) {
        echo "<pre>model is missing a name[", print_r($rootModel, 1), "]</pre>\n";
        return;
      }
      if ($this->btTables) {
        $tableAlias = '`' . $tableName . '`';
        $tableName = '`' . $tableName . '`';
      } else {
        $tableAlias = $tableName;
      }
    }
    $groupbys = array();
    if (!empty($options['groupby'])) {
      $groupbys = $options['groupby'];
    }
    $data = array(
      'joins'    => array(),
      'groupbys' => array(),
      'having'   => '',
      'fields'   => array(),
    );
    $data = $this->expandJoin($rootModel, $data);
    // FIXME: renaming support
    if ($fields === false) {
      $useFields = $data['fields'];
    } else {
      $useFields = array_merge(array_map(function($f) use ($data, $tableAlias) {
        return (count($data['joins']) ? $tableAlias . '.' : '') . $f;
      }, explode(',', $fields)), $data['fields']);
    }

    /*
    if ($this->btTables && !isset($rootModel['query'])) {
      $sql = 'select '. join("\n" . ',', $useFields) . "\n" . 'from `' . $tableName . '`';
    } else {
    */
      $sql = 'select '. join("\n" . ',', $useFields) . "\n" . 'from ' . $tableName;
    //}
    $useAlias = '';
    if (count($data['joins'])) {
      $sql .= "\n" . join("\n", $data['joins']);
      $useAlias = $tableName;
    }
    if (isset($options['criteria'])) {
      // I don' think we need this...
      //$this->typeCriteria($rootModel, $options['criteria']);
      $sql .= "\n" . 'where ' . $this->build_where($options['criteria'], $useAlias);
    }
    if (count($data['groupbys'])) {
      $sql .= "\n" . 'group by ' . join(',', $data['groupbys']);
    }
    if (!empty($data['having'])) {
      $sql .= "\n" . 'having ' . $data['having'];
    }
    /*
    if (isset($options['having'])) {
      $sql .= ' having ' . $options['having'];
    }
    */
    if (isset($options['order'])) {
      if (!empty($options['orderNoAlias'])) {
        $sql .= "\n" . 'order by ' . $options['orderNoAlias'];
      } else {
        $defAlias = count($data['joins']) ? $tableName : '';
        $alias = $defAlias ? $defAlias . '.' : '';
        $sql .= "\n" . 'order by ' . $alias . $options['order'];
      }
    }
    if (isset($options['limit'])) {
      $sql .= "\n" . ' limit ' . $options['limit'];
    }
    if (0) {
      $trace = gettrace();
      echo "<pre>sql[$sql] $trace</pre>\n";
    }
    //$sql = str_replace("\n", ' ', $sql);
    return $sql;
  }

  public function makeSubselect($rootModel, $options = false, $fields = '*') {
    return array(
      //'name'  => $rootModel['name'],
      'model' => $rootModel,
      'query' => $this->makeSelectQuery($rootModel, $options, $fields),
    );
  }

  public function findById($rootModel, $id, $options = false) {
    $tableName = modelToTableName($rootModel);
    $id = (int)$id;
    $fields = '*';
    $options = array(
      'criteria' => array(
        array(modelToId($rootModel) , '=', $id)
      )
    );
    return $this->find($rootModel, $options);
  }
  public function updateById($rootModel, $id, $row, $options = false) {
    $tableName = modelToTableName($rootModel);
    $id = (int)$id;
    // this stomps $options...
    $options = array(
      'criteria' => array(
        array(modelToId($rootModel) , '=', $id)
      )
    );
    return $this->update($rootModel, $row, $options);
  }
  public function deleteById($rootModel, $id, $options = false) {
    $tableName = modelToTableName($rootModel);
    $id = (int)$id;
    $options = array(
      'criteria' => array(
        array(modelToId($rootModel) , '=', $id)
      )
    );
    return $this->delete($rootModel, $options);
  }
  public function toArray($res) {
    $arr = array();
    while($row = $this->getrow($res)) {
      $arr[] = $row;
    }
    return $arr;
  }
}

function modelToTableName($model) {
  if (!isset($model['name'])) {
    $trace = gettrace();
    echo "<pre>base::modelToTableName - model is missing a name[", print_r($model, 1), "] $trace</pre>\n";
    return;
  }
  return $model['name'].'s';
}
function modelToId($model) {
  if (!isset($model['name'])) {
    $trace = gettrace();
    echo "<pre>base::modelToId - model is missing a name[", print_r($model, 1), "] $trace</pre>\n";
    return;
  }
  $parts = explode('_', $model['name']);
  $name = array_pop($parts);
  return $name . 'id';
}

function make_db_field($value) {
  return array($value);
}

// columns
// https://laravel.com/docs/8.x/collections#method-pluck
function pluck($rows, $fields) {
  $res = array();
  foreach($rows as $row) {
    $keys = array();
    if (is_array($fields)) {
      foreach($fields as $f) {
        $keys[$f] = $row[$f];
      }
    } else {
      $keys = array($fields => $row[$fields]);
    }
    // might not be able to know how to handle return values...
    if (count($keys) === 1) {
      $res[] = array_shift($keys);
    } else {
      $res[] = $keys;
    }
  }
  return $res;
}

?>
