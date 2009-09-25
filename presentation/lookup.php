<?php
  function linkedtable_lookup($tablename, $fieldname, $foreigntablename = null, $alltables = null, $primarykeyfieldname = null) {
    static $linkedtables = array();
    if (is_null($linkedtables["$tablename:$fieldname"])) {
      if (is_null($foreigntablename)) {
        $likeness = array();
        $fieldname_lower = strtolower($fieldname);
        foreach ($alltables as $onetable) {
          $onetable_lower = strtolower($onetable);
          $likeness[$onetable] =
            ($fieldname_lower == singularize_noun($onetable_lower) ? 10 : 0) +
            (substr($fieldname_lower, -strlen($primarykeyfieldname[$onetable])) == $primarykeyfieldname[$onetable] ? 5 : 0) +
            (strpos($fieldname_lower, singularize_noun($onetable_lower)) !== false ? 5 : 0) -
            levenshtein($fieldname_lower, $onetable_lower);
        }
        arsort($likeness);
        reset($likeness);
        list($table1, $likeness1) = each($likeness);
        list($table2, $likeness2) = each($likeness);
        $linkedtables["$tablename:$fieldname"] = $likeness1 < 0 || $likeness1 == $likeness2 ? '' : $table1;
      }
      else
        $linkedtables["$tablename:$fieldname"] = $foreigntablename;
    }
    return $linkedtables["$tablename:$fieldname"] ? $linkedtables["$tablename:$fieldname"] : null;
  }

  function probability_lookup($field) {
    return 
      ($field['referenced_table_name'] && linkedtable_lookup($field['table_name'], $field['column_name'], $field['referenced_table_name'])
      ? 1.0
      : (preg_match('@^(int|integer)\b@', $field['column_type']) && linkedtable_lookup($field['table_name'], $field['column_name'], null, $field['alltables'], $field['primarykeyfieldname'])
        ? 0.6
        : 0
        )
      );
  }

  function in_desc_lookup($field) { return $field['fieldnr'] < 5 ? 1 : 0.9; }
  function in_list_lookup($field) { return $field['fieldnr'] < 5 ? 1 : 0.9; }
  function in_edit_lookup($field) { return 1; }

  function is_sortable_lookup() { return true; }

  function ajax_lookup($metabasename, $databasename, $fieldname, $value, $presentationname, $foreigntablename, $foreigntablenamesingular, $foreignuniquefieldname, $nullallowed, $hasdefaultvalue, $readonly, $extra = true) {
    if (!$foreigntablename)
      error(sprintf(_('no foreigntablename for %s'), $fieldname));
    $descriptor = descriptor($metabasename, $databasename, $foreigntablename, $foreigntablename);
    $references = query('data', "SELECT $foreigntablename.$foreignuniquefieldname AS _id, $descriptor[select] AS _descriptor FROM `$databasename`.$foreigntablename$descriptor[joins] ORDER BY _descriptor");
    $options = array();
    while ($reference = mysql_fetch_assoc($references)) {
      $selected = $value == $reference['_id'];
      $oneselected = $oneselected || $selected;
      $options[] = html('option', array_merge(array('value'=>$reference['_id']), $selected ? array('selected'=>'selected') : array()), $reference['_descriptor']);
    }
    array_unshift($options, html('option', array_merge(array('value'=>''), $oneselected ? array() : array('selected'=>'selected')), ''));
    return
      html('div', array('class'=>'ajax', 'id'=>http_build_query(array('function'=>'ajax_lookup', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'fieldname'=>$fieldname, 'value'=>$value, 'presentationname'=>$presentationname, 'foreigntablename'=>$foreigntablename, 'foreigntablenamesingular'=>$foreigntablenamesingular, 'foreignuniquefieldname'=>$foreignuniquefieldname, 'nullallowed'=>$nullallowed, 'hasdefaultvalue'=>$hasdefaultvalue, 'readonly'=>$readonly))),
        html('div', array(),
          html('select', array('name'=>"field:$fieldname", 'id'=>"field:$fieldname", 'class'=>join_clean(' ', $presentationname, $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $nullallowed || $hasdefaultvalue ? null : 'notempty'), 'readonly'=>$readonly ? 'readonly' : null), join($options)).
          ($extra ? ' '.internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$foreigntablename, 'tablenamesingular'=>$foreigntablenamesingular, 'back'=>parameter('server', 'REQUEST_URI')), sprintf(_('new %s'), $foreigntablenamesingular)).html('span', array('class'=>'changeslost'), ' '._('(changes to form fields are lost)')) : '')
        )
      );
  }

  function formfield_lookup($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return ajax_lookup($metabasename, $databasename, $field['fieldname'], $value, $field['presentationname'], $field['foreigntablename'], $field['foreigntablenamesingular'], $field['foreignuniquefieldname'], $field['nullallowed'], $field['hasdefaultvalue'], $readonly, $extra);
  }

  function formvalue_lookup($field) {
    $value = parameter('get', "field:$field[fieldname]");
    return $value == "" ? null : $value;
  }

  function list_lookup($metabasename, $databasename, $field, $value) {
    return
      ($field['thisrecord']
      ? $field['descriptor']
      : ($field['descriptor']
        ? internalreference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$field['foreigntablename'], 'tablenamesingular'=>$field['foreigntablenamesingular'], 'uniquefieldname'=>$field['foreignuniquefieldname'], 'uniquevalue'=>$value, 'back'=>parameter('server', 'REQUEST_URI')), $field['descriptor']) 
        : $value
        )
      );
  }
  
  function css_lookup() {
    return 
      ".lookup.edit { width: 20.45em; }\n".
      ".lookup.list { width: auto; }\n";
  }
?>
