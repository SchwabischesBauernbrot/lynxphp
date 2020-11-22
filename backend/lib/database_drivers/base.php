<?php

interface database_driver_base {
  // direct
  public function connect($host, $user, $pass, $port = 0);
  // direct
  public function switchDB($db);
  // easy
  public function autoupdate($model);
  public function buildWhere($criteria);
  public function make_constant($value);
  public function insert($rootModel, $recs);
  // options
  //   fields = if not set, give all fields, else expect an array
  //   criteria = if set, an array
  //              array(field, comparison, field/constant)
  public function find($rootModel, $options = false);
  public function findById($rootModel, $id, $options = false);
}

class database_driver_base_class {
  function __construct() {
    $this->conn = null;
    $this->modelToSQL = array();
    $this->sqlToModel = array();
  }
  public function connectDB($host, $user, $pass, $db, $port = 0) {
    if (!$this->connect($host, $user, $pass, $port)) {
      return false;
    }
    return $this->switchDB($db);
  }
  public function make_constant($value) {
    return '"'. addslashes($value) . '"';
  }
  // convert array into where clause
  public function buildWhere($criteria) {
    // field, comparator, field
    $sets = array();
    foreach($criteria as $set) {
      if (is_array($set[2])) {
        $sets[] = $set[0] . ' ' . $set[1] . ' ' . $set[2][0];
      } else {
        $sets[] = $set[0] . ' ' . $set[1] . ' ' . $this->make_constant($set[2]);
      }
    }
    return join(' AND ', $sets);
  }
  public function findById($rootModel, $id, $options = false) {
    $tableName = nameToTable($rootModel);
    $id = (int)$id;
    $fields = '*';
    $options = array(
      'criteria' => array(
        array($rootModel.'id' , '=', $id)
      )
    );
    return $this->find($rootModel, $options);
  }
}

?>
