<?php
  function probability_datetime($field) {
    return preg_match('@^datetime\b@', $field['Type']) ? 0.5 : 0;
  }

  function typename_datetime($field) {
    return 'datetime';
  }

  function in_desc_datetime($field) { return false; }
  function in_list_datetime($field) { return false; }
  function in_edit_datetime($field) { return true; }

  function formfield_datetime($metabasename, $databasename, $field, $value, $readonly) {
    return 
      html('input', array('type'=>'text', 'class'=>join(' ', array_clean(array($field['presentation'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'))), 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'value'=>datetime2local($value), 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null)).
      html('span', array('class'=>'help'), strftime(_('e.g. %x %X')));
  }

  function formvalue_datetime($field) {
    return local2datetime(parameter('get', "field:$field[fieldname]"));
  }

  function list_datetime($metabasename, $databasename, $field, $value) {
    return datetime2local($value);
  }

  function datetime2local($value) {
    return change_datetime_format($value, '%Y-%m-%d %H:%M:%S', '%x %X');
  }
  
  function local2datetime($value) {
    return change_datetime_format($value, '%x %X', '%Y-%m-%d %H:%M:%S');
  }
  
  function css_datetime() {
    return '';
  }
?>
