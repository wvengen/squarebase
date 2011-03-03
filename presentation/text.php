<?php
  function probability_text($field) {
    return preg_match('@^(tiny|medium|long)?text\b@', $field['column_type']) ? 0.5 : 0;
  }

  function in_desc_text($field) { return 0; }
  function in_list_text($field) { return 0; }
  function in_edit_text($field) { return 1; }

  function is_sortable_text() { return true; }
  function is_quickaddable_text() { return false; }

  function formfield_text($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return html('textarea', array('name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'rows'=>10, 'cols'=>80, 'class'=>array($field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'readonly'=>$readonly ? 'readonly' : null), htmlentities($value));
  }

  function formvalue_text($field) {
    $value = get_post("field:$field[fieldname]", null);
    return $value == '' ? null : $value;
  }

  function list_text($metabasename, $databasename, $field, $value) {
    return htmlentities($value);
  }
  
  function css_text() {
    return
      ".text { width: 20em; white-space: pre-wrap; margin-bottom: 0.2em; }\n".
      ".text.list { height: 1.25em; }\n";
  }

  function jquery_enhance_form_text() {
    return
      "getScripts('jquery/autogrow.js', '.text.edit',\n".
      "  function() {\n".
      "    $(this).\n".
      "    autogrow();\n".
      "  }\n".
      ").\n";
  }
?>
