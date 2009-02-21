<?php
  require_once('text.php');

  function formfield_textwithpreview($metabasename, $databasename, $field, $value, $readonly) {
    return
      html('div', array('class'=>'textwithpreviewbox'),
        html('textarea', array('name'=>"field:$field[fieldname]", 'class'=>$field['presentation'], 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null, 'onkeyup'=>"document.getElementById('preview:$field[fieldname]').innerHTML = this.value; return true;"), preg_replace('/<(.*?)>/', '&lt;$1&gt;', $value)).
        html('div', array('id'=>"preview:$field[fieldname]", 'class'=>'preview'), $value)
      );
  }

  function formvalue_textwithpreview($field) {
    return formvalue_text($field);
  }

  function cell_textwithpreview($metabasename, $databasename, $field, $value) {
    return cell_text($metabasename, $databasename, $field, $value);
  }
?>
