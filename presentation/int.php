<?php
  function probability_int($field) {
    return preg_match('/^(tiny|small|medium||int|big)int\b/', $field['Type']) ? 0.4 : 0;
  }

  function typename_int($field) {
    return $field['Field'];
  }

  function in_desc_int() { return 0; }
  function in_sort_int() { return 0; }
  function in_list_int() { return 0; }
  function in_edit_int() { return 1; }

  function formfield_int($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>$field['presentation'], 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null));
  }

  function formvalue_int($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function cell_int($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_int() {
    return ".int { text-align: right; width: 3em; }\n";
  }
?>
