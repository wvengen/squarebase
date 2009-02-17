<?php
  function formfield_boolean($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'checkbox', 'class'=>$field['presentation'], 'name'=>"field:$field[fieldname]", 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null, 'checked'=>$value ? 'checked' : null));
  }

  function formvalue_boolean($field) {
    return parameter('get', "field:$field[fieldname]") ? 1 : 0;
  }

  function cell_boolean($metabasename, $databasename, $field, $value) {
    return formfield_boolean($metabasename, $databasename, $field, $value, true);
  }
?>
