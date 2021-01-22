<?php

function generateForm($action, $fields, $values) {
  $html = '<form action="' . $action . '" method="post" enctype="multipart/form-data"><dl>';
  // fields should always be an array
  foreach($fields as $field => $details) {
    if (!isset($details['label'])) {
      echo "Skipping [$field], no label<br>\n";
      continue;
    }
    $html .= '<dt><label for="' . $field . '">'. $details['label'] . ': &nbsp;</label>';
    // can be stomped if needed
    $value = empty($values[$field]) ? '' : $values[$field];
    $html .= '<dd>';
    switch($details['type']) {
      case 'text':
        $html .= '<input type=text name="'.$field.'" value="' . $value . '">';
      break;
      case 'checkbox':
        $checked = $value ? ' CHECKED' : '';
        $html .= '<input id="'.$field.'" type=checkbox name="'.$field.'" value="1"'.$checked.'>';
      break;
      case 'image':
        if ($value) {
          // FIXME: BACKEND_BASE_URL
          // if not set it will clear it, so we need a clear checkbox...
          $html .= '<img height=100 src="backend/' . $value . '">';
        }
        $html .= '<input type=file name="'.$field.'">';
      break;
      default:
        //echo "No such type [", $details['type'], "]<br>\n";
        $html .= 'Error unknown type '.$details['type'].', skipping ';
      break;
    }
  }
  $html .= '</dl>
    <input type=submit value="Update">
  </form>';
  return $html;
}

?>
