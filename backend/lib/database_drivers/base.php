<?php

interface database_driver_base {
  // direct
  public function connect($host, $user, $pass, $port = 0);
  // direct
  public function switch_db($db);
  // easy
  public function autoupdate($model);
  public function build_where($criteria);
  public function make_constant($value);
  public function insert($rootModel, $recs);
  public function update($rootModel, $urow, $options);
  // options
  //   fields = if not set, give all fields, else expect an array
  //   criteria = if set, an array
  //              array(field, comparison, field/constant)
  public function find($rootModel, $options = false);
  public function count($rootModel, $options = false);
  public function findById($rootModel, $id, $options = false);
  // result functions
  public function num_rows($res);
  public function get_row($res);
  public function toArray($res);
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
  // convert array into where clause
  public function build_where($criteria, $defAlias = '') {
    // field, comparator, field
    $sets = array();
    $alias = $defAlias ? $defAlias . '.' : '';
    foreach($criteria as $k => $set) {
      if (is_numeric($k)) {
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
  public function toArray($res) {
    $arr = array();
    while($row = $this->getrow($res)) {
      $arr[] = $row;
    }
    return $arr;
  }
}

function modelToTableName($model) {
  return $model['name'].'s';
}
function modelToId($model) {
  $parts = explode('_', $model['name']);
  $name = array_pop($parts);
  return $name.'id';
}

function make_db_field($value) {
  return array($value);
}

?>