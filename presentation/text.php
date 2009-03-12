<?php
  function probability_text($field) {
    return preg_match('/^(tiny||medium|long)text\b/', $field['Type']) ? 0.5 : 0;
  }

  function typename_text($field) {
    return strtolower($field['Field']);
  }

  function in_desc_text($field) { return false; }
  function in_list_text($field) { return false; }
  function in_edit_text($field) { return true; }

  function formfield_text($metabasename, $databasename, $field, $value, $readonly) {
    return html('textarea', array('name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'class'=>join(' ', array_clean(array($field['presentation'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'))), 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null), preg_replace('/<(.*?)>/', '&lt;$1&gt;', $value));
  }

  function formvalue_text($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function list_text($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_text() {
    return ".text { width: 20em; height: 10em; white-space: pre-wrap; }\n";
  }
?>
