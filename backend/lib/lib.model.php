<?php

function modelToTableName($model) {
  return $model['name'].'s';
}
function modelToId($model) {
  $parts = explode('_', $model['name']);
  $name = array_pop($parts);
  return $name.'id';
}

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

function make_db_field($value) {
  return array($value);
}

?>
