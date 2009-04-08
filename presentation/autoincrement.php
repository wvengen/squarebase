<?php
  function probability_autoincrement($field) {
    return preg_match('@(auto_increment)@', $field['Extra']) ? 0.7 : 0;
  }

  function typename_autoincrement($field) {
    return 'autoincrement';
  }

  function in_desc_autoincrement($field) { return false; }
  function in_list_autoincrement($field) { return false; }
  function in_edit_autoincrement($field) { return true; }

  function is_sortable_autoincrement() { return false; }

  function formfield_autoincrement($metabasename, $databasename, $field, $value, $readonly) {
    return 
      html('span', array('class'=>$field['presentation']), is_null($value) ? '?' : $value).
      html('input', array('type'=>'hidden', 'name'=>"field:$field[fieldname]", 'value'=>$value));
  }

  function formvalue_autoincrement($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function list_autoincrement($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_autoincrement() {
    return ".autoincrement { color: #666; }\n";
  }
?>
