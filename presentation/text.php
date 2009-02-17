<?php
  function formfield_text($metabasename, $databasename, $field, $value, $readonly) {
    return html('textarea', array('name'=>"field:$field[fieldname]", 'class'=>'textareablur', 'onfocus'=>"this.className = 'textareafocus';", 'onblur'=>"this.className = 'textareablur';", 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly => 'disabled' : null), preg_replace('/<(.*?)>/', '&lt;$1&gt;', $value));
  }

  function formvalue_text($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function cell_text($metabasename, $databasename, $field, $value) {
    return $value;
  }
?>
