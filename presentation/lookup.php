<?php
  function linkedtable_lookup($tablename, $fieldname, $alltables = null, $primarykeyfieldname = null) {
    static $linkedtables = array();
    if (is_null($linkedtables["$tablename:$fieldname"])) {
      $likeness = array();
      foreach ($alltables as $onetable) {
        $likeness[$onetable] =
          ($fieldname == $onetable ? 10 : 0) +
          (substr($fieldname, -strlen($primarykeyfieldname[$onetable])) == $primarykeyfieldname[$onetable] ? 5 : 0) +
          (strpos($fieldname, $onetable) !== FALSE ? 5 : 0);
      }
      arsort($likeness);
      reset($likeness);
      list($table1, $likeness1) = each($likeness);
      list($table2, $likeness2) = each($likeness);
      $linkedtables["$tablename:$fieldname"] = $likeness1 == $likeness2 ? '' : $table1;
    }
    return $linkedtables["$tablename:$fieldname"] ? $linkedtables["$tablename:$fieldname"] : null;
  }

  function probability_lookup($field) {
    return probability_int($field) && linkedtable_lookup($field['Table'], $field['Field'], $field['Alltables'], $field['Primarykeyfieldname']) ? 0.6 : 0;
  }

  function typename_lookup($field) {
    return 'lookup'.linkedtable_lookup($field['Table'], $field['Field']);
  }

  function in_desc_lookup($field) { return $field['FieldNr'] < 5; }
  function in_sort_lookup($field) { return in_desc_lookup($field); }
  function in_list_lookup($field) { return in_desc_lookup($field); }
  function in_edit_lookup($field) { return 1; }

  function ajax_lookup($metabasename, $databasename, $fieldname, $value, $presentation, $foreigntableid, $foreigntablename, $foreignuniquefieldname, $nullallowed, $readonly) {
    if (!$foreigntableid)
      error("no foreigntableid for $fieldname");
    list($references) = orderedrows($metabasename, $databasename, $foreigntableid, $foreigntablename, 0, 0, $foreignuniquefieldname, 'desc');
    $options = '';
    while ($reference = mysql_fetch_assoc($references)) {
      $option = $reference["${foreigntablename}_descriptor"];
      $checkoption = $value == $reference[$foreignuniquefieldname];
      $checked = $checked || $checkoption;
      $options .= html('option', array_merge(array('value'=>$reference[$foreignuniquefieldname]), $checkoption ? array('selected'=>'selected') : array()), $option);
    }
    $options =
      html('option', array_merge(array('value'=>''), $checked ? array() : array('selected'=>'selected')), '').
      $options;
    return
      html('div', array('class'=>'ajax', 'id'=>http_build_query(array('function'=>'ajax_lookup', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'fieldname'=>$fieldname, 'value'=>$value, 'presentation'=>$presentation, 'foreigntableid'=>$foreigntableid, 'foreigntablename'=>$foreigntablename, 'foreignuniquefieldname'=>$foreignuniquefieldname, 'nullallowed'=>$nullallowed, 'readonly'=>$readonly))),
        html('select', array('name'=>"field:$fieldname", 'id'=>"field:$fieldname", 'class'=>join(' ', cleanlist(array($presentation, $readonly ? 'readonly' : null, $nullallowed ? null : 'notempty'))), 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null), $options).' '.
        ($readonly ? '' : internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$foreigntableid, 'back'=>parameter('server', 'REQUEST_URI')), "new $foreigntablename").html('span', array('class'=>'changeslost'), ' (changes to form fields are lost)')).
        html('div', array('class'=>'ajaxcontent'), '')
      );
  }

  function formfield_lookup($metabasename, $databasename, $field, $value, $readonly) {
    return ajax_lookup($metabasename, $databasename, $field['fieldname'], $value, $field['presentation'], $field['foreigntableid'], $field['foreigntablename'], $field['foreignuniquefieldname'], $field['nullallowed'], $readonly);
  }

  function formvalue_lookup($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function cell_lookup($metabasename, $databasename, $field, $value) {
    return
      ($field['thisrecord']
      ? html('span', array('class'=>'thisrecord'), $field['descriptor'])
      : ($field['descriptor']
        ? internalreference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$field['foreigntableid'], 'uniquevalue'=>$value, 'back'=>parameter('server', 'REQUEST_URI')), $field['descriptor']) 
        : $value
        )
      );
  }
  
  function css_lookup() {
    return 
      ".lookup { width: 10em; }\n".
      ".column.lookup { width: 20em; }\n";
  }
?>
