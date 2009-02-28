<?php
  require_once('text.php');

  function probability_textwithpreview($field) {
    return preg_match('/^(tiny||medium|long)text\b/', $field['Type']) ? 0.6 : 0;
  }

  function typename_textwithpreview($field) {
    return $field['Field'];
  }

  function in_desc_textwithpreview() { return 0; }
  function in_sort_textwithpreview() { return 0; }
  function in_list_textwithpreview() { return 0; }
  function in_edit_textwithpreview() { return 1; }

  function formfield_textwithpreview($metabasename, $databasename, $field, $value, $readonly) {
    return formfield_text($metabasename, $databasename, $field, $value, $readonly);
  }

  function formvalue_textwithpreview($field) {
    return formvalue_text($field);
  }

  function cell_textwithpreview($metabasename, $databasename, $field, $value) {
    return cell_text($metabasename, $databasename, $field, $value);
  }
  
  function css_textwithpreview() {
    return
      ".textwithpreview { width: 20em; height: 10em; white-space: pre-wrap; }\n".
      ".textwithpreview.focus { color: #000; }\n".
      ".textwithpreview.blur { color: #fff; }\n".
      ".preview { width: 20em; height: 10em; margin: -10em 3px 0; overflow: auto; }\n".
      ".preview.focus { visibility: visible; }\n".
      ".preview.blur { visibility: hidden; }\n";
  }

  function jquery_document_ready_textwithpreview() {
    return
      "$('.textwithpreview').".
      "each(".
        "function() {".
          "$(this).".
          "addClass('blur').".
          "focus(function() { $(this).removeClass('blur').next().addClass('blur'); }).".
          "blur( function() { $(this).addClass('blur').next().removeClass('blur').html(this.value); }).".
          "after('<div id=\"preview_' + this.id + '\" class=\"preview focus\">' + this.value + '</div>');".
        "}".
      ");\n";
  }
?>
