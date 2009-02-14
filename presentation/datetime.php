<?php
  function formfield_datetime($metabasename, $databasename, $field, $value, $action) {
    return html('input', array('type'=>'text', 'class'=>$field['presentation'], 'name'=>"field:$field[fieldname]", 'value'=>swap_datetime($value), 'readonly'=>$action == 'delete_record' ? 'readonly' : null, 'disabled'=>$action == 'delete_record' ? 'disabled' : null));
  }

  function formvalue_datetime($field) {
    return swap_datetime(parameter('get', "field:$field[fieldname]"));
  }

  function swap_datetime($value) {
    if (!preg_match('/^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)$/', $value, $matches))
      return $value;
    return "$matches[3]-$matches[2]-$matches[1] $matches[4]:$matches[5]:$matches[6]";
  }
?>
