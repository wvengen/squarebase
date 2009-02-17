<?php
  function formfield_lookup($metabasename, $databasename, $field, $value, $readonly) {
    if (!$field['foreigntableid'])
      error("no foreigntableid for $field[fieldname]");
    if ($readonly) {
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

  function cell_lookup($metabasename, $databasename, $field, $value) {
    return
      ($field['thisrecord']
      ? $field['descriptor']
      : ($field['descriptor']
        ? internalreference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$field['foreigntableid'], 'uniquevalue'=>$value, 'back'=>parameter('server', 'REQUEST_URI')), $field['descriptor']) 
        : $value
        )
      );
  }
?>
