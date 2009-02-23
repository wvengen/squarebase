<?php
  function probability_varchar($field) {
    return 0.1;
  }

  function typename_varchar($field) {
    return $field['Field'];
  }

  function in_desc_varchar() { return 1; }
  function in_sort_varchar() { return 1; }
  function in_list_varchar() { return 1; }
  function in_edit_varchar() { return 1; }

  function formfield_varchar($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>$field['presentation'], 'name'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null));
  }

  function formvalue_varchar($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function cell_varchar($metabasename, $databasename, $field, $value) {
    return $value;
  }
?>
