<?php
  function formfield_boolean($metabasename, $databasename, $field, $value, $action) {
    return html('input', array('type'=>'checkbox', 'class'=>$field['presentation'], 'name'=>"field:$field[fieldname]", 'readonly'=>$action == 'delete_record' ? 'readonly' : null, 'disabled'=>$action == 'delete_record' ? 'disabled' : null, 'checked'=>$value ? 'checked' : null));
  }

  function formvalue_boolean($field) {
    return parameter('get', "field:$field[fieldname]") ? 1 : 0;
  }
?>
