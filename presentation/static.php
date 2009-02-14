<?php
  function formfield_static($metabasename, $databasename, $field, $value, $action) {
    return html('span', array('class'=>$field['presentation']), $value);
  }

  function formvalue_static($field) {
    return null;
  }
?>
