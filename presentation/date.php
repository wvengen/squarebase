<?php
  function probability_date($field) {
    return preg_match('@^date\b@', $field['Type']) ? 0.5 : 0;
  }

  function typename_date($field) {
    return 'date';
  }

  function in_desc_date($field) { return false; }
  function in_list_date($field) { return false; }
  function in_edit_date($field) { return true; }

  function is_sortable_date() { return true; }

  function formfield_date($metabasename, $databasename, $field, $value, $readonly) {
    return 
      html('input', array('type'=>'text', 'class'=>join(' ', array_clean(array($field['presentation'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'))), 'name'=>"field:$field[fieldname]", 'value'=>date2local($value), 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null)).
      html('span', array('class'=>'help'), strftime(_('e.g. %x')));
  }

  function formvalue_date($field) {
    return local2date(parameter('get', "field:$field[fieldname]"));
  }

  function list_date($metabasename, $databasename, $field, $value) {
    return date2local($value);
  }

  function date2local($value) {
    return change_datetime_format($value, '%Y-%m-%d', '%x');
  }
  
  function local2date($value) {
    return change_datetime_format($value, '%x', '%Y-%m-%d');
  }
  
  function css_date() {
    return '';
  }
?>
