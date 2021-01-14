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
        if (is_array($set[2])) {
          $sets[] = $alias . $set[0] . ' ' . $set[1] . ' ' . $set[2][0];
        } else {
          $sets[] = $alias . $set[0] . ' ' . $set[1] . ' ' . $this->make_constant($set[2]);
        }
      } else {
        // named key
        $sets[] = $alias . $k . '=' . $set;
      }
    }
    return join(' AND ', $sets);
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
    echo "<pre>model[", print_r($model, 1), "] is missing a name</pre>\n";
    return;
  }
  return $model['name'].'s';
}
function modelToId($model) {
  if (!isset($model['name'])) {
    echo "<pre>model[", print_r($model, 1), "] is missing a name</pre>\n";
    return;
  }
  $parts = explode('_', $model['name']);
  $name = array_pop($parts);
  return $name.'id';
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
