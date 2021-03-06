<?php
  function probability_autoincrement($field) {
    return
      (preg_match1('@\bauto_increment\b@', $field['extra'])
      ? 1.0
      : (probability_int($field) && (preg_match('@\bPRI\b@', $field['column_key']) || $field['column_name'] == $field['primarykeyfieldname'])
        ? 0.9
        : 0
        )
      );
  }

  function in_desc_autoincrement($field) { return 0.1; }
  function in_list_autoincrement($field) { return 0.1; }
  function in_edit_autoincrement($field) { return 1; }

  function is_sortable_autoincrement() { return false; }
  function is_quickaddable_autoincrement() { return true; }

  function formfield_autoincrement($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return html('input', array('type'=>'text', 'class'=>array($field['presentationname'], $extra ? 'edit' : 'list', 'readonly'), 'name'=>"field-$field[fieldname]", 'id'=>"field-$field[fieldname]", 'value'=>$value, 'readonly'=>'readonly'));
  }

  function formvalue_autoincrement($field) {
    $value = get_post("field-$field[fieldname]", null);
    return $value == '' ? null : $value;
  }

  function list_autoincrement($metabasename, $databasename, $field, $value) {
    return htmlentities($value);
  }
  
  function css_autoincrement() {
    return ".autoincrement.edit { width: 20em; }\n";
  }
?>
