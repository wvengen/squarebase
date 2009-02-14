<?php
  function formfield_lookup($metabasename, $databasename, $field, $value, $action) {
    if (!$field['foreigntableid'])
      error("no foreigntableid for $field[fieldname]");
    if ($action == 'delete_record') {
      list($references) = orderedrows($metabasename, $databasename, $field['foreigntableid'], $field['foreigntablename'], 0, 0, $field['foreignuniquefieldname'], 'desc', $field['foreignuniquefieldname'], $value);
      $reference = mysql_fetch_assoc($references);
      $option = $reference["${tablename}_$field[fieldname]"];
      return
        html('select', array('name'=>"field:$field[fieldname]", 'class'=>$field['presentation'], 'readonly'=>'readonly', 'disabled'=>'disabled'),
          html('option', array_merge(array('value'=>$reference[$field['foreignuniquefieldname']]), $checkoption ? array('selected'=>'selected') : array()), $option)
        );
    }
    list($references) = orderedrows($metabasename, $databasename, $field['foreigntableid'], $field['foreigntablename'], 0, 0, $field['foreignuniquefieldname'], 'desc');
    $options = '';
    while ($reference = mysql_fetch_assoc($references)) {
      $option = $reference["${field[foreigntablename]}_descriptor"];
      $checkoption = $value == $reference[$field['foreignuniquefieldname']];
      $checked = $checked || $checkoption;
      $options .= html('option', array_merge(array('value'=>$reference[$field['foreignuniquefieldname']]), $checkoption ? array('selected'=>'selected') : array()), $option);
    }
    if ($field['nullallowed'])
      $options =
        html('option', array_merge(array('value'=>''), $checked ? array() : array('selected'=>'selected')), '').
        $options;
    return
      html('select', array('name'=>"field:$field[fieldname]", 'class'=>$field['presentation']), $options).' '.
      html('input', array('type'=>'submit', 'name'=>'action', 'value'=>"new_record_$field[foreigntablename]", 'onclick'=>"this.form.newtableid.value = $field[foreigntableid]; return true;", 'class'=>'button'));
  }

  function formvalue_lookup($field) {
    return parameter('get', "field:$field[fieldname]");
  }
?>
