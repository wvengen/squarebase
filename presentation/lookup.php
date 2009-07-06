<?php
  function linkedtable_lookup($tablename, $fieldname, $alltables = null, $primarykeyfieldname = null) {
    static $linkedtables = array();
    if (is_null($linkedtables["$tablename:$fieldname"])) {
      $likeness = array();
      $fieldname_lower = strtolower($fieldname);
      foreach ($alltables as $onetable) {
        $onetable_lower = strtolower($onetable);
        $likeness[$onetable] =
          ($fieldname_lower == singularize_noun($onetable_lower) ? 10 : 0) +
          (substr($fieldname_lower, -strlen($primarykeyfieldname[$onetable])) == $primarykeyfieldname[$onetable] ? 5 : 0) +
          (strpos($fieldname_lower, singularize_noun($onetable_lower)) !== FALSE ? 5 : 0) -
          levenshtein($fieldname_lower, $onetable_lower);
      }
      arsort($likeness);
      reset($likeness);
      list($table1, $likeness1) = each($likeness);
      list($table2, $likeness2) = each($likeness);
      $linkedtables["$tablename:$fieldname"] = $likeness1 < 0 || $likeness1 == $likeness2 ? '' : $table1;
    }
    return $linkedtables["$tablename:$fieldname"] ? $linkedtables["$tablename:$fieldname"] : null;
  }

  function probability_lookup($field) {
    return preg_match('@^(int|integer)\b@', $field['Type']) && linkedtable_lookup($field['Table'], $field['Field'], $field['Alltables'], $field['Primarykeyfieldname']) ? 0.6 : 0;
  }

  function in_desc_lookup($field) { return $field['FieldNr'] < 5; }
  function in_list_lookup($field) { return $field['FieldNr'] < 5; }
  function in_edit_lookup($field) { return true; }

  function is_sortable_lookup() { return true; }

  function ajax_lookup($metabasename, $databasename, $fieldname, $value, $presentationname, $foreigntablename, $foreignuniquefieldname, $nullallowed, $readonly) {
    if (!$foreigntablename)
      error(sprintf(_('no foreigntablename for %s'), $fieldname));
    $descriptor = descriptor($metabasename, $databasename, $foreigntablename, $foreigntablename);
    $references = query('data', "SELECT $foreignuniquefieldname, $descriptor[select] AS _descriptor FROM `$databasename`.$foreigntablename$descriptor[joins] ORDER BY _descriptor");
    $options = array();
    while ($reference = mysql_fetch_assoc($references)) {
      $selected = $value == $reference[$foreignuniquefieldname];
      $oneselected = $oneselected || $selected;
      $options[] = html('option', array_merge(array('value'=>$reference[$foreignuniquefieldname]), $selected ? array('selected'=>'selected') : array()), $reference['_descriptor']);
    }
    array_unshift($options, html('option', array_merge(array('value'=>''), $oneselected ? array() : array('selected'=>'selected')), ''));
    return
      html('div', array('class'=>'ajax', 'id'=>http_build_query(array('function'=>'ajax_lookup', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'fieldname'=>$fieldname, 'value'=>$value, 'presentationname'=>$presentationname, 'foreigntablename'=>$foreigntablename, 'foreignuniquefieldname'=>$foreignuniquefieldname, 'nullallowed'=>$nullallowed, 'readonly'=>$readonly))),
        html('div', array(),
          html('select', array('name'=>"field:$fieldname", 'id'=>"field:$fieldname", 'class'=>join_clean(' ', $presentationname, $readonly ? 'readonly' : null, $nullallowed ? null : 'notempty'), 'readonly'=>$readonly ? 'readonly' : null), join($options)).
          ($readonly ? '' : ' '.internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$foreigntablename, 'back'=>parameter('server', 'REQUEST_URI')), sprintf(_('new %s'), singularize_noun($foreigntablename))).html('span', array('class'=>'changeslost'), ' '._('(changes to form fields are lost)')))
        )
      );
  }

  function formfield_lookup($metabasename, $databasename, $field, $value, $readonly) {
    return ajax_lookup($metabasename, $databasename, $field['fieldname'], $value, $field['presentationname'], $field['foreigntablename'], $field['foreignuniquefieldname'], $field['nullallowed'], $readonly);
  }

  function formvalue_lookup($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function list_lookup($metabasename, $databasename, $field, $value) {
    return
      ($field['thisrecord']
      ? $field['descriptor']
      : ($field['descriptor']
        ? internalreference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$field['foreigntablename'], 'uniquefieldname'=>$field['foreignuniquefieldname'], 'uniquevalue'=>$value, 'back'=>parameter('server', 'REQUEST_URI')), $field['descriptor']) 
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
