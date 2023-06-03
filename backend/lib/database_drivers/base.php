<?php

interface database_driver_base {
  public function connect_db($host, $user, $pass, $db, $port = 0);
  // easy
  public function autoupdate($model);
  public function build_where($criteria);
  // FIXME: no snake case?
  public function make_constant($value);
  public function make_direct($value);
  public function insert($rootModel, $recs);
  // options.criteria
  public function update($rootModel, $urow, $options);
  // options.criteria
  public function delete($rootModel, $options);
  // options
  //   fields = if not set, give all fields, else expect an array
  //   criteria = if set, available formats:
  //              array(field, comparison, field/constant)
  //              or
  //              field => field/constant
  public function find($rootModel, $options = false);
  public function count($rootModel, $options = false);
  // options doesn't look to be used...
  public function findById($rootModel, $id, $options = false);
  // options doesn't look to be used...
  public function updateById($rootModel, $id, $row, $options = false);
  // options.criteria
  public function deleteById($rootModel, $id, $options = false);
  public function deleteByIds($rootModel, $ids, $options = false);
  // result functions
  // FIXME: no snake case?
  public function num_rows($res);
  public function get_row($res);
  public function toArray($res);
  public function free($res);
  public function groupAgg($field);
  public function randOrder();
}

class database_driver_base_class {
  function __construct() {
    $this->conn = null;
    $this->modelToSQL = array();
    $this->sqlToModel = array();
    $this->subselectCounter = 0;
    $this->registeredTables = array();
    $this->dontTrackTables = array('table_tracker');
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
    // num => array(field, comparator, field),
    // or
    // field => field,
    $alias = $defAlias ? $defAlias . '.' : '';
    $mode = 'and';
    $tokens = array();
    $end = count($criteria);
    $c = 0;
    foreach($criteria as $k => $set) {
      //echo "k[$k] [", print_r($set, 1), "]<bR>\n";
      if (is_numeric($k)) {
        // array(field, comparison, field/constant) version
        if (!is_array($set)) {
          echo '<pre>base:::database_driver_base_class::build_where - [', $defAlias, '] Criteria[', print_r($criteria, 1), "]</pre>\n";
          exit(1);
        }
        // flexible criteria
        if (is_array($set[0])) {
          $left = $set[0][0];
        } else {
          if (is_string($set)) {
            $upperSet = strtoupper($set);
            if ($upperSet === 'OR') {
              //echo "Changing mode<br>\n";
              $mode = 'or';
              $c++;
              continue;
            }
            if ($upperSet === 'AND') {
              //echo "Changing mode<br>\n";
              $mode = 'and';
              $c++;
              continue;
            }
          }
          // $this->make_constant()
          // postgres can't put quotes around fields
          $left = $set[0];
        }
        if ($left === '(' || $left === ')') {
          if ($left === ')') array_pop($tokens); // remove last and/or
          $tokens[] = $left;
          $c++;
          continue;
        }
        if (strpos($left, '(') !== false) {
          $one = $left;
        } else {
          $one = $alias . $left;
        }
        $operand = $set[1];
        $upperOperand = strtoupper($operand);
        if ($upperOperand === 'IN' || $upperOperand === 'NOT IN') {
          // list operands
          if (!is_array($set[2])) {
            // auto promote to array or abort?
            return false;
          }
          $inSet = $set[2];
          $inSet = array_map(function($v) {
            return is_array($v) ? $v[0] : $this->make_constant($v);
          }, $inSet);
          // we will need to address this...
          /*
          if ($defAlias) {
            $inSet = array();
            foreach($set[2] as $cond) {
              $inSet[] = $defAlias . '.' . $cond;
            }
          }
          */
          $tokens[] = $one . ' ' . $operand . ' (' . join(',', $inSet). ')';
        } else {
          $tokens[] = $one . ' ' . $operand . ' ' . (is_array($set[2]) ? $set[2][0] : $this->make_constant($set[2]));
        }
      } else {
        // named key
        // direct vs default: safe
        $tokens[] = $alias . $k . '=' . (is_array($set) ? $set[0] : $this->make_constant($set));
      }
      $c++;
      //echo "[$c/$end]<br>\n";
      if ($c !== $end) {
        //echo "Adding [$mode]<br>\n";
        $tokens[] = ($mode === 'and') ? 'AND' : 'OR';
      }
    }
    return join(' ', $tokens);
  }

  // sets up table table for tracking writes
  function ensureTables() {
    global $models;
    if (!isset($models['table'])) {
      return false;
    }
    $res = $this->find($models['table'], array('criteria' => array(
      array('table_name', 'IN', $this->registeredTables),
    )));
    if (!$res) {
      return false;
    }
    // an array, so copy
    $toCreateNames = array_flip($this->registeredTables);
    while($row = $this->get_row($res)) {
      //print_r($row);
      $table = $row['table_name'];
      unset($toCreateNames[$table]);
    }
    $this->free($res);
    $rows = array_map(function($tableName) {
      return array('table_name' => $tableName);
    }, array_filter(array_keys($toCreateNames), function($v) { return $v; } ));
    //echo "<pre>Rows[", print_r($rows, 1), "]</pre>\n";
    if (count($rows)) {
      $this->insert($models['table'], $rows);
    }
    // clear, so we can't ensureTables again
    $this->registeredTables = array();
  }

  function getLast($tables) {
    global $models;
    if (!is_array($tables)) $tables = array($tables);
    $res = $this->find($models['table'], array('criteria' => array(
      array('table_name', 'IN', $tables),
    )));
    // memory constrained version
    $max = 0;
    while($row = $this->get_row($res)) {
      $max = max($max, $row['updated_at']);
    }
    $this->free($res);
    return $max;
  }

  protected function markWriten($rootModel) {
    global $models;
    if (!isset($models['table'])) {
      return false;
    }
    $tableName = modelToTableName($rootModel);
    $this->update($models['table'], array(), array('criteria' => array(
      'table_name' => $tableName
    )));
    return true;
  }

  protected function makeInsertQuery($rootModel, $recs) {
    global $now;
    $tableName = modelToTableName($rootModel);
    $date = (int)$now;
    $recs[0]['json'] = '{}';
    $recs[0]['created_at'] = $date;
    $recs[0]['updated_at'] = $date;
    $fields = join(',', array_keys($recs[0]));
    $sTableName = $this->btTables ? '`' . $tableName . '`' : $tableName;
    $sql = 'insert into ' . $sTableName . ' (' . $fields . ') values';
    $sets = array();
    foreach($recs as $rec) {
      $cleanArr = array();
      $rec['json'] = '{}';
      $rec['created_at'] = $date;
      $rec['updated_at'] = $date;
      foreach($rec as $val) {
        $cleanArr[] = is_array($val) ? $val[0] : $this->make_constant($val);
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
      'updated_at' => 'updated_at = ' . (int)$now,
    );
    if (!empty($urow['json'])) {
      if (!is_string($urow['json'])) {
        $urow['json'] = json_encode($urow['json']);
      }
    }
    //echo "<pre>", print_r($urow, 1), "</pre>\n";
    foreach($urow as $f=>$v) {
      // updates are always assignments (=, never </>=)
      $val = is_array($v) ? $v[0] : $this->make_constant($v);
      //echo "val[$val]<br>\n";
      $sets[$f] = $f . '=' . $val;
    }
    // postgres doesn't allow you to set the AI PK
    // lets make sure other doesn't do this too then
    //if ($this->forceUnsetIdOnUpdate) {
    $idf = modelToId($rootModel);
    unset($sets[$idf]);
    //}
    $sTableName = $this->btTables ? '`' . $tableName . '`' : $tableName;
    $sql = 'update ' .$sTableName . ' set '. join(', ', $sets);
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria']);
    }
    return $sql;
  }

  protected function makeDeleteQuery($rootModel, $options) {
    $tableName = modelToTableName($rootModel);
    $sTableName = $this->btTables ? '`' . $tableName . '`' : $tableName;
    $sql = 'delete from ' .$sTableName;
    if (isset($options['critera']) && !isset($options['criteria'])) {
      $options['criteria'] = $options['critera'];
    }
    if (isset($options['criteria'])) {
      $sql .= ' where ' . $this->build_where($options['criteria']);
    //} else {
      // a warning? or something to prevent total table loss if typo...
      // options doesn't have a default for now
      // that's a good enough warning
    }
    return $sql;
  }

  // what's the minimum in join? model
  private function handleJoin($models, $data, $tableName, $useField = '') {
    //echo "tableName[$tableName]<br>\n";
    $originalTableName = $tableName;
    foreach($models as $join) {
      $tableName = $originalTableName;
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
      //echo "joinTable[$joinTable] joinAlias[$joinAlias] tableName[$tableName] joinField[$joinField]\n";

      // support an empty array
      if (isset($join['pluck'])) {
        if (!is_array($join['pluck'])) {
          echo "Warning, string detect in pluck, fix this!<br>\n";
        }
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
        // mysql only_full_group_by needs all fields used in the group by
        //echo "<pre>", print_r($useGroupBys, 1), "</pre>\n";
        $useGroupBys = array_map(function($val) use ($tableName, $joinAlias) {
          // why ALIAS elsewhere but MODEL here?
          // because it's the base table, not the joining
          return str_replace(array('MODEL.', 'ALIAS.'),
            array($tableName . '.', $joinAlias . '.'), $val);
        }, $useGroupBys);
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
      // FIXME: this could be tigher
      $tableName = 't' . ($this->subselectCounter - 1);
    } else {
      $tableName = modelToTableName($rootModel);
    }
    //echo "expandJoin tableName[$tableName]<br>\n";
    if (!empty($rootModel['children']) && is_array($rootModel['children'])) {
      // subquery?
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
      // FIXME: a way to give an alias to the initial table...
      if ($this->btTables) {
        $tableAlias = '`' . $tableName . '`';
        $tableName = '`' . $tableName . '`';
      } else {
        $tableAlias = $tableName;
      }
    }
    // not used...
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
    if (!empty($options['having'])) {
      $data['having'] = $options['having'];
    }
    $data = $this->expandJoin($rootModel, $data);
    // FIXME: renaming support
    if ($fields === false) {
      $useFields = $data['fields'];
    } else {
      $useFields = array_merge(array_map(function($f) use ($data, $tableAlias) {
        return (count($data['joins']) ? $tableAlias . '.' : '') . $f;
      }, explode(',', $fields)), $data['fields']);
    }

    $sql = 'select '. join("\n" . ',', $useFields) . "\n" . 'from ' . $tableName;
    $useAlias = '';
    if (count($data['joins'])) {
      $sql .= "\n" . join("\n", $data['joins']);
      $useAlias = $tableName;
    }
    if (isset($options['criteria']) && count($options['criteria'])) {
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
    if (isset($options['order']) || isset($options['orderNoAlias'])) {
      if (!empty($options['orderNoAlias'])) {
        $sql .= "\n" . 'order by ' . $options['orderNoAlias'];
      } else {
        //echo "tablename[$tableName]<br>\n";
        $defAlias = count($data['joins']) ? $tableAlias : '';
        $alias = $defAlias ? $defAlias . '.' : '';
        $sql .= "\n" . 'order by ' . $alias . $options['order'];
      }
    }
    // FIXME: make an array (postgres doesn't like offset,max)
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

  // options doesn't look to be used...
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
  // options doesn't look to be used...
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
  // could we take an array of IDs?
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
  // you can override and optimize this...
  public function deleteByIds($rootModel, $ids, $options = false) {
    $results = array();
    foreach($ids as $id) {
      $results[$id] = $this->deleteById($rootModel, $id, $options);
    }
    return $results;
  }
  public function toArray($res) {
    $arr = array();
    while($row = $this->getrow($res)) {
      $arr[] = $row;
    }
    return $arr;
  }

  public function isTrue($val) {
    return $val;
  }
  public function isFalse($val) {
    return !$val;
  }
}

function modelToTableName($model) {
  if (!isset($model['name'])) {
    $trace = gettrace();
    echo "<pre>base::modelToTableName - model is missing a name[", print_r($model, 1), "] $trace</pre>\n";
    return;
  }
  return str_replace('-', '_', $model['name'].'s');
}
function modelToId($model) {
  if (!isset($model['name'])) {
    $trace = gettrace();
    echo "<pre>base::modelToId - model is missing a name[", print_r($model, 1), "] $trace</pre>\n";
    return;
  }
  // this isn't great, board_users becomes userid, and that's a field...
  $parts = explode('_', $model['name']);
  $name = array_pop($parts);
  //$name = str_replace('_', '', $model['name']);
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