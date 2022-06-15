<?php

function post_queue_display($qposts) {
  $str = '';
  global $pipelines;

  $io = array(
    'addFields' => array()
  );
  $pipelines[PIPELINE_FE_ADMIN_QUEUE_ROW]->execute($io);
  $addFields = $io['addFields'];

  $str .= '<table width=100%>';
  // could but a check all here...
  $fields = array(
    '', 'uri', 'id', 'created', 'thread', 'type', 'ip', 'post', 'votes',
  );
  $str .= '<tr><th><nobr>' . join('</nobr><th><nobr>', $fields) . '</nobr>';
  foreach($addFields as $f) {
    $str .= '<th>' . $f['label'];
  }
  $str .= '<th>Actions';
  foreach($qposts as $s) {
    $uri = $s['board_uri'];
    $d = json_decode($s['data'], true);
    $str .= '<tr>';
    $str .= '<td><input type=checkbox name="list[]" value="' . $s['queueid'] . '">';
    $str .= '<th><a href="/' . $uri . '" target=_blank>' . $uri;
    $str .= '<td>' . $s['queueid'];
    // age would be better...
    $str .= '<td>' . date('Y-m-d H:i:s', $s['created_at']);
    $str .= '<td>' . (!$s['thread_id'] ? 'new' : ('<a href="' . $uri . '/thread/' . $s['thread_id'] . '.html" target=_blank>' . $s['thread_id'] . '</a>'));
    $str .= '<td>' . $s['type'];
    $str .= '<td>' . $s['ip'] . '<td>' . renderPost($uri, $s['post']);
    $str .= '<td>' . $s['votes'];
    foreach($addFields as $f) {
      $field = $f['field'];
      $val = $s[$field];
      if (!empty($f['type'])) {
        switch($f['type']) {
          case 'compact_informative':
            $str .= '<td>';
            foreach($val as $l) {
              $str .= '<a href="' . $l . '">X</a>' . "\n";
            }
          break;
          case 'compact_informative':
            $str .= '<td><span title="' . join(',', $val) . '">X</span>';
          break;
          default:
            echo "Unknown type[", $f['type'], "]<br>\n";
          break;
        }
      } else {
        // default
        if (is_array($val)) {
          $str .= '<td>' . join(',', $val);
        } else {
          $str .= '<td>' . $val;
        }
      }
    }
    // then actions?
    $dataActions = array(
      array('link' => 'admin/queue/' . $s['queueid'] . '/delete.html', 'label' => 'Delete'),
    );
    $htmlLinks = array();
    foreach($dataActions as $a) {
      $htmlLinks[] = action_getLinkHTML($a, array('where' => false));
    }
    $str .= '<td>' . join('<br>' > "\n", $htmlLinks);
    //$str .= '<td><pre>' . print_r($s, 1) . "</pre>\n";
  }
  $str .= '</table>';
  return $str;
}

return array();

?>