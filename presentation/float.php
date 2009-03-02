<?php
  function probability_float($field) {
    // probability is lower than int to get it precedence with zero digits
    return preg_match('/^(real|double|float|decimal|numeric)\b/', $field['Type']) ? 0.3 : 0;
  }

  function typename_float($field) {
    return $field['Field'];
  }

  function in_desc_float() { return 0; }
  function in_sort_float() { return 0; }
  function in_list_float() { return 0; }
  function in_edit_float() { return 1; }

  function formfield_float($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>$field['presentation'].' '.($readonly ? 'readonly' : ''), 'name'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null));
  }

  function formvalue_float($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function cell_float($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_float() {
    return
      ".float { text-align: right; }\n".
      ".float .decimals { text-align: left; width: 4em; }";
  }
?>
