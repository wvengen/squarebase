<?php
  require_once('text.php');

  function probability_textwithpreview($field) {
    if (!preg_match('@^(tiny||medium|long)text\b@', $field['column_type']))
      return 0;
    $texts = query('data', "SELECT $field[column_name] FROM `$field[table_schema]`.`$field[table_name]` WHERE $field[column_name] IS NOT NULL LIMIT 10");
    if (mysql_num_rows($texts) == 0)
      return 0;
    while ($text = mysql_fetch_assoc($texts)) {
      if (preg_match('@<\w+\b[^<]*>@', $text[$field['column_name']]))
        return 0.6;
    }
    return 0;
  }

  function in_desc_textwithpreview($field) { return 0; }
  function in_list_textwithpreview($field) { return 0; }
  function in_edit_textwithpreview($field) { return 1; }

  function is_sortable_textwithpreview() { return true; }

  function formfield_textwithpreview($metabasename, $databasename, $field, $value, $readonly) {
    return formfield_text($metabasename, $databasename, $field, $value, $readonly);
  }

  function formvalue_textwithpreview($field) {
    return formvalue_text($field);
  }

  function list_textwithpreview($metabasename, $databasename, $field, $value) {
    return list_text($metabasename, $databasename, $field, $value);
  }
  
  function css_textwithpreview() {
    return
      ".wrapper { position: relative; margin-bottom: 0.6em; }\n".
      "textarea.textwithpreview { width: 20em; padding: 0.2em; min-height: 2em; white-space: pre-wrap; }\n".
      "textarea.textwithpreview.blur { display: none; color: #ffe; visibility: hidden; }\n".
      ".preview { width: 20em; min-height: 2em; white-space: normal; padding: 0.2em; position: absolute; top: 0; border: 0.1em solid #999; background-color: #ffe; }\n".
      ".preview.blur { visibility: hidden; }\n";
  }

  function jquery_enhance_form_textwithpreview() {
    return
      "find('.textwithpreview').\n".
      "autogrow().\n".
      "wrap('<div class=\"wrapper\"></div>').\n".
      "addClass('blur').\n".
      "focus(function() { $(this).parent().css('height', 'auto'); $(this).removeClass('blur').next().addClass('blur'); }).\n".
      "blur( function() { $(this).parent().css('height', $(this).addClass('blur').next().removeClass('blur').html(this.value).css('height')); }).\n".
      "after('<div class=\"preview\"></div>').\n".
      "blur().\n". //to put the html in the preview box
      "next().\n".
      "click(function() { $(this).prev().focus(); }).\n".
      "end().\n".
      "end().\n";
  }
?>
