<?php
  function formfield_varchar($metabasename, $databasename, $field, $value, $action) {
    return html('input', array('type'=>'text', 'class'=>$field['presentation'], 'name'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$action == 'delete_record' ? 'readonly' : null, 'disabled'=>$action == 'delete_record' ? 'disabled' : null));
  }

  function formvalue_varchar($field) {
    return parameter('get', "field:$field[fieldname]");
  }
?>
