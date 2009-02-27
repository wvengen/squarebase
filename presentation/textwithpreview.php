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
      ".textwithpreview { width: 20em; height: 10em; white-space: pre-wrap; float: left; }\n".
      ".preview { background-color: #eee; color: #666; width: 20em; height: 10em; border: 3px double #999; margin: 1px; margin-left: 21em; overflow: auto; }\n";
  }

  function jquery_document_ready_textwithpreview() {
    return
      "$('.textwithpreview').".
      "each(".
        "function() {".
          "$(this).".
          "wrap('<div class=\"textwithpreviewbox\"></div>').".
          "keyup(".
            "function() {".
              "$(this).next().html(this.value);".
              "return true;".
            "}".
          ").".
          "after('<div id=\"preview_' + this.id + '\" class=\"preview\">' + this.value + '</div>');".
        "}".
      ");\n";
  }
?>
