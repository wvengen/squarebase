<?php
  require_once('text.php');

  function probability_textwithpreview($field) {
    if (!preg_match('/^(tiny||medium|long)text\b/', $field['Type']))
      return 0;
    $texts = query('data', "SELECT $field[Field] FROM `$field[Database]`.$field[Table] WHERE $field[Field] IS NOT NULL LIMIT 10");
    while ($text = mysql_fetch_assoc($texts)) {
      if (preg_match('/<\w+\b[^<]*>/', $text[$field['Field']]))
        return 0.6;
    }
    return 0;
  }

  function typename_textwithpreview($field) {
    return strtolower($field['Field']);
  }

  function in_desc_textwithpreview($field) { return 0; }
  function in_sort_textwithpreview($field) { return 0; }
  function in_list_textwithpreview($field) { return 0; }
  function in_edit_textwithpreview($field) { return 1; }

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
      ".textwithpreview { width: 20em; height: 10em; white-space: pre-wrap; }\n".
      ".textwithpreview.blur { color: #ffe; overflow: hidden; }\n".
      ".preview { width: 20em; height: 10em; margin-top: -10.1em; overflow: auto; border: 0.1em solid #999; }\n".
      ".preview.blur { visibility: hidden; }\n";
  }

  function jquery_document_ready_textwithpreview() {
    return
      "$('.textwithpreview').".
      "addClass('blur').".
      "focus(function() { $(this).removeClass('blur').next().addClass('blur'); }).".
      "blur( function() { $(this).addClass('blur').next().removeClass('blur').html(this.value); }).".
      "after('<div id=\"preview_' + this.id + '\" class=\"preview\"></div>').".
      "blur().". //to put the html in the preview box
      "next().".
      "click(function() { $(this).prev().focus(); });\n";
  }
?>
