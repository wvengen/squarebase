<?php
  function formfield_datetime($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>$field['presentation'], 'name'=>"field:$field[fieldname]", 'value'=>swap_datetime($value), 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null));
  }

  function formvalue_datetime($field) {
    return swap_datetime(parameter('get', "field:$field[fieldname]"));
  }

  function cell_datetime($metabasename, $databasename, $field, $value) {
    return swap_datetime($value);
  }

  function swap_datetime($value) {
    if (!preg_match('/^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)$/', $value, $matches))
      return $value;
    return "$matches[3]-$matches[2]-$matches[1] $matches[4]:$matches[5]:$matches[6]";
  }
?>
