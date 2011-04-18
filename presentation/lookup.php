<?php
  function linkedtable_lookup($tablename, $fieldname, $foreigntablename = null, $alltablenames = null) {
    static $linkedtables = array();
    if (!isset($linkedtables["$tablename-$fieldname"])) {
      if (is_null($foreigntablename)) {
        $likeness = array();
        foreach ($alltablenames as $onetablename=>$oneprimarykeyfieldname) {
          $likeness[$onetablename] =
            (preg_match("@(\b|_)$oneprimarykeyfieldname(_|\b)@i", $fieldname)
            ? 200 + strlen($oneprimarykeyfieldname) - levenshtein($onetablename, $fieldname)
            : (preg_match("@(\b|_)$onetablename(\b|_)@i", $fieldname)
              ? 100 + strlen($onetablename)
              : 0
              )
            );
        }
        arsort($likeness);
        reset($likeness);
        list($table1, $likeness1) = each($likeness);
        list($table2, $likeness2) = each($likeness);
        $linkedtables["$tablename-$fieldname"] = is_null($likeness1) || $likeness1 === $likeness2 ? '' : $table1;
      }
      else
        $linkedtables["$tablename-$fieldname"] = $foreigntablename;
    }
    return isset($linkedtables["$tablename-$fieldname"]) ? $linkedtables["$tablename-$fieldname"] : null;
  }

  function probability_lookup($field) {
    return 
      ($field['referenced_table_name'] && linkedtable_lookup($field['table_name'], $field['column_name'], $field['referenced_table_name'])
      ? 1.0
      : (preg_match('@^(int|integer)\b@', $field['column_type']) && linkedtable_lookup($field['table_name'], $field['column_name'], null, $field['alltablenames'])
        ? 0.6
        : 0
        )
      );
  }

  function in_desc_lookup($field) { return $field['fieldnr'] < 5 ? 1 : 0.9; }
  function in_list_lookup($field) { return $field['fieldnr'] < 5 ? 1 : 0.9; }
  function in_edit_lookup($field) { return 1; }

  function is_sortable_lookup() { return true; }
  function is_quickaddable_lookup() { return true; }

  callable_function('ajax_lookup', array('metabasename', 'databasename', 'fieldname', 'value', 'presentationname', 'foreigntablename', 'foreigntablenamesingular', 'foreignuniquefieldname', 'nullallowed', 'defaultvalue', 'readonly', 'extra'));

  function ajax_lookup($metabasename, $databasename, $fieldname, $value, $presentationname, $foreigntablename, $foreigntablenamesingular, $foreignuniquefieldname, $nullallowed, $defaultvalue, $readonly, $extra = true) {
    if (!$foreigntablename)
      error(sprintf(_('no foreigntablename for %s'), $fieldname));
    $foreignviewname = table_or_view($metabasename, $databasename, $foreigntablename);
    $descriptor = descriptor($metabasename, $databasename, $foreigntablename, $foreignviewname);
    $references = query("SELECT `$foreignviewname`.`$foreignuniquefieldname` AS _id, $descriptor[select] AS _descriptor FROM `$databasename`.`$foreignviewname` ".join(' ', $descriptor['joins']).($readonly ? ($value ? "WHERE `$foreignviewname`.`$foreignuniquefieldname` = ".((int) $value) : "LIMIT 0") : "ORDER BY ".join(', ', $descriptor['orders'])));
    $selected_descriptor = null;
    $oneselected = false;
    $options = array();
    while ($reference = mysql_fetch_assoc($references)) {
      $selected = $value == $reference['_id'];
      if ($selected)
        $selected_descriptor = $reference['_descriptor'];
      $oneselected = $oneselected || $selected;
      $options[] = html('option', array_merge(array('value'=>$reference['_id']), $selected ? array('selected'=>'selected') : array()), $reference['_descriptor']);
    }
    if (!$readonly)
      array_unshift($options, html('option', array_merge(array('value'=>''), $value ? array() : array('selected'=>'selected')), ''));
    if (!$oneselected && $value) {
      array_unshift($options, html('option', array('value'=>$value, 'selected'=>'selected', 'class'=>'unknownforeignrecord'), $value));
      $selected_descriptor = $value;
    }
    return
      html('div', array('class'=>'ajax', 'id'=>http_build_url(array('action'=>'call_function', 'functionname'=>'ajax_lookup', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'fieldname'=>$fieldname, 'value'=>$value, 'presentationname'=>$presentationname, 'foreigntablename'=>$foreigntablename, 'foreigntablenamesingular'=>$foreigntablenamesingular, 'foreignuniquefieldname'=>$foreignuniquefieldname, 'nullallowed'=>$nullallowed, 'defaultvalue'=>$defaultvalue, 'readonly'=>$readonly, 'extra'=>$extra))),
        html('div', array(),
          html('select', array('name'=>"field-$fieldname", 'id'=>"field-$fieldname", 'class'=>array($presentationname, "lookup-from-table-$foreigntablenamesingular", $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $nullallowed || $defaultvalue != '' ? null : 'notempty'), 'readonly'=>$readonly ? 'readonly' : null), join($options)).
          ($extra
          ? (has_grant('INSERT', $databasename, $foreigntablename, '?') ? internal_reference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$foreigntablename, 'tablenamesingular'=>$foreigntablenamesingular, 'uniquefieldname'=>$foreignuniquefieldname, 'referencedfromfieldname'=>$fieldname, 'back'=>get_server('REQUEST_URI')), sprintf(_('new %s'), $foreigntablenamesingular), array('class'=>'newrecordlookup')) : '').
            (has_grant('UPDATE', $databasename, $foreigntablename, '?')
            ? internal_reference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$foreigntablename, 'tablenamesingular'=>$foreigntablenamesingular, 'uniquefieldname'=>$foreignuniquefieldname, 'uniquevalue'=>$value, 'referencedfromfieldname'=>$fieldname, 'back'=>get_server('REQUEST_URI')), sprintf(_('edit %s %s'), $foreigntablenamesingular, $selected_descriptor), array('class'=>array('existingrecord', $oneselected ? null : 'hidden')))
            : internal_reference(array('action'=>'show_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$foreigntablename, 'tablenamesingular'=>$foreigntablenamesingular, 'uniquefieldname'=>$foreignuniquefieldname, 'uniquevalue'=>$value, 'referencedfromfieldname'=>$fieldname, 'back'=>get_server('REQUEST_URI')), sprintf(_('show %s %s'), $foreigntablenamesingular, $selected_descriptor), array('class'=>array('existingrecord', $oneselected ? null : 'hidden')))
            ).
            html('span', array('class'=>'changeslost'), ' '._('(changes to form fields are lost)'))
          : ''
          )
        )
      );
  }

  function formfield_lookup($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    $lookup = ajax_lookup($metabasename, $databasename, $field['fieldname'], $value, $field['presentationname'], $field['foreigntablename'], $field['foreigntablenamesingular'], $field['foreignuniquefieldname'], $field['nullallowed'], $field['defaultvalue'], $readonly, $extra);
    return $extra ? ajaxcontent($lookup) : $lookup;
  }

  function formvalue_lookup($field) {
    $value = get_post("field-$field[fieldname]", null);
    return $value == '' ? null : $value;
  }

  function list_lookup($metabasename, $databasename, $field, $value) {
    return
      ($field['thisrecord']
      ? $field['descriptor']
      : ($field['descriptor']
        ? internal_reference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$field['foreigntablename'], 'tablenamesingular'=>$field['foreigntablenamesingular'], 'uniquefieldname'=>$field['foreignuniquefieldname'], 'uniquevalue'=>$value, 'back'=>get_server('REQUEST_URI')), $field['descriptor']) 
        : htmlentities($value)
        )
      );
  }
  
  function css_lookup() {
    return 
      ".lookup.edit { width: 20.5em; }\n".
      ".lookup.list { width: auto; max-width: 20.5em; }\n".
      ".newrecordlookup, .existingrecord, .changeslost { margin-left: 0.5em; }\n".
      ".existingrecord.hidden { display: none; }\n";
  }

  function jquery_enhance_form_lookup() {
    return
      "find('.existingrecord').\n".
      "  siblings('select').\n".
      "    change(\n".
      "      function() {\n".
      "        var selectedoption = $(this).find('option:selected');\n".
      "        $(this).\n".
      "        siblings('.existingrecord').\n".
      "        attr('href', $(this).siblings('.existingrecord').attr('href').replace(/uniquevalue=\d*/, '') + '&uniquevalue=' + $(this).val()).\n".
      "        text($(this).siblings('.existingrecord').text().replace(/^(\w+ \w+ ).*$/, '$1' + selectedoption.text())).\n".
      "        toggleClass('hidden', selectedoption.hasClass('unknownforeignrecord') || !$(this).val());\n".
      "      }\n".
      "    ).\n".
      "  end().\n".
      "end().\n";
  }
?>
