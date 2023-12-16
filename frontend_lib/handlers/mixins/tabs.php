<?php

// inpage tabs
function renderTabs($tabs, $options = array()) {
  if (!empty($options['useDetails'])) {
    $html = '';
    foreach($tabs as $label => $d) {
      $stb = strtolower(str_replace(' ', '', $label));
      $html .= '<details style="float: left">
        <summary>'.$label.'</summary>
        <div>' . $d.'</div>
      </details>' . "\n";
    }
  } else {
    if (empty($options['tabGroup'])) {
      global $tabGroup;
      if (empty($tabGroup)) $tabGroup = 0;
      $tabGroup++;
    } else {
      $tabGroup = $options['tabGroup'];
    }
    $name = 'tabgroup_' . $tabGroup;
    if (!empty($options['name'])) {
      $name = $options['name'];
    }
    // maybe should use visibility hidden to reduce the jumpiness
    $html = '<div class="tabs">';
    $c = 0;
    $selectFirst = true;
    if (!empty($options['defaultNone'])) {
      $selectFirst = false;
      $sel = '';
    }
    // labels
    foreach($tabs as $tb => $d) {
      $stb = strtolower(str_replace(' ', '', $tb));
      if ($selectFirst) {
        $sel = $c === 0 ? ' checked' : '';
      }
      $html .= '<input type="radio" class="tab" id="tab_' . $stb . '" ' .
        'name="' . $name . '" value="'.$stb.'" role="button"' . $sel .
        '>';
      $html .= '<label for="tab_' . $stb . '">' . $tb . '</label>
      <style>
      #tab_'.$stb.'.tab + label {
        padding: 10px;
        margin-top: 20px;
        margin-bottom: 20px;
        border-width: 0 1px 1px 0;
        border-style: solid;
        background: var(--post-color);
        border-color: var(--post-outline-color);
      }
      #tab_'.$stb.':checked.tab + label {
        font-weight: 800;
      }
      #tab_'.$stb.':checked.tab~#tabContent_'.$stb.' {
        display: block;
        border-width: 0 1px 1px 0;
        border-style: solid;
        background: var(--post-color);
        border-color: var(--post-outline-color);
      }
      ';
      if (!empty($options['any'])) {
        $html .= '
      #tab_'.$stb.':checked.tab~#tabGroup'.$tabGroup.' {
        display: block;
        border-width: 0 1px 1px 0;
        border-style: solid;
        background: var(--post-color);
        border-color: var(--post-outline-color);
      }
        ';
      }
      if (!empty($options['closeAll'])) {
        $html .= '
      #tab_'.$stb.':checked.tab~#tabLabel_CLOSEALL {
        display: inline;
      }
        ';
      }
      $html .= '
      </style>
      ';
      $c++;
    }
    if (!empty($options['closeAll'])) {
      $stb = 'CLOSEALL';
      $html .= '<input type="radio" class="tab" id="tab_'.$stb.'" ' .
        'name="' . $name . '" role="button">';
      $html .= '<label id="tabLabel_'.$stb.'" for="tab_' . $stb . '">X</label>
      <style>
      #tabLabel_CLOSEALL {
        cursor: pointer;
      }
      #tab_'.$stb.'.tab + label {
        display: none;
        padding: 10px;
        margin-top: 20px;
        margin-bottom: 20px;
        border-width: 0 1px 1px 0;
        border-style: solid;
        background: var(--post-color);
        border-color: var(--post-outline-color);
      }
      #tab_'.$stb.':checked.tab + label {
        font-weight: 800;
      }
      #tab_'.$stb.':checked.tab~#tabContent_'.$stb.' {
        display: block;
        border-width: 0 1px 1px 0;
        border-style: solid;
        background: var(--post-color);
        border-color: var(--post-outline-color);
      }
      </style>
      ';
    }
    $html .= '<br><br>';
    // content
    foreach($tabs as $tb => $h) {
      $stb = strtolower(str_replace(' ', '', $tb));
      $html .= '<div class="tab" id="tabContent_' . $stb . '">' . $h. '</div>';
    }
    if (!empty($options['any'])) {
      $html .= '<div class="tab" id="tabGroup' . $tabGroup . '">' . $options['any'] . '</div>';
    }
  }
  $html .= '</div><!-- end tabs -->';
  return $html;
}

?>
