<?php
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
