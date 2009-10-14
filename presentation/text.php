<?php
  function probability_text($field) {
    return preg_match('@^(tiny|medium|long)?text\b@', $field['column_type']) ? 0.5 : 0;
  }

  function in_desc_text($field) { return 0; }
  function in_list_text($field) { return 0; }
  function in_edit_text($field) { return 1; }

  function is_sortable_text() { return true; }

  function formfield_text($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return html('textarea', array('name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'class'=>join_clean(' ', $field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'readonly'=>$readonly ? 'readonly' : null), preg_replace('@<(.*?)>@', '&lt;$1&gt;', $value));
  }

  function formvalue_text($field) {
    $value = parameter('get', "field:$field[fieldname]");
    return $value == "" ? null : $value;
  }

  function list_text($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_text() {
    return
      ".text { width: 20em; white-space: pre-wrap; margin-bottom: 0.2em; }\n".
      ".text.list { height: 1.25em; }\n";
  }

  function jquery_enhance_form_text() {
    return
      "find('.text.edit').\n".
      "  autogrow().\n".
      "end().\n";
  }
?>
