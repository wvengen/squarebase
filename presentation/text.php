<?php
  function formfield_text($metabasename, $databasename, $field, $value, $action) {
    return html('textarea', array_merge(array('name'=>"field:$field[fieldname]", 'class'=>'textareablur', 'onfocus'=>"this.className = 'textareafocus';", 'onblur'=>"this.className = 'textareablur';"), preg_replace('/<(.*?)>/', '&lt;$1&gt;', $action == 'delete_record' ? array('readonly'=>'readonly', 'disabled'=>'disabled') : array())), ''.$value);
  }

  function formvalue_text($field) {
    return parameter('get', "field:$field[fieldname]");
  }
?>
