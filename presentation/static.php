<?php
  function formfield_static($metabasename, $databasename, $field, $value, $readonly) {
    return html('span', array('class'=>$field['presentation']), $value);
  }

  function formvalue_static($field) {
    return null;
  }

  function cell_static($metabasename, $databasename, $field, $value) {
    return $value;
  }
?>
