<?php
  function probability_enum($field) {
    if (preg_match('@^(enum)\b@', $field['column_type']))
      return 0.5;
    $distinct = query1('data', "
    	SELECT COUNT(`<fieldname>`) AS numberofrows,
    	       COUNT(DISTINCT(`<fieldname>`)) AS numberofdistinctvalues
    	    FROM `<databasename>`.`<tablename>`",
    	array('databasename'=>$field['table_schema'],
    	      'tablename'=>$field['table_name'],
    	      'fieldname'=>$field['column_name']));
    if (!$distinct['numberofrows']) return 0;
    return 0.4 - 0.4 * $distinct['numberofdistinctvalues'] / $distinct['numberofrows'];
  }

  function in_desc_enum($field) { return 0; }
  function in_list_enum($field) { return 0; }
  function in_edit_enum($field) { return 1; }

  function is_sortable_enum() { return true; }

  function formfield_enum($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    // TODO retrieve enum values
    $cfields = fields_from_table($metabasename, $databasename, $field[tablename], $field[viewname], 'SELECT', true);
    $isenum = false;
    $values = array();
    while ($cfield = mysql_fetch_assoc($cfields)) {
    	//var_dump($cfield);
    	if ($cfield[fieldname]==$field[fieldname]) {
    	  $isenum = true;
    	  break;
    	}
    }
    if ($isenum) { }
    
    $options = array();
    $selected = true;
    // TODO foreach ... to add the options
    $options[] = html('option', array('value'=>'test', $selected ? array('selected'=>'selected') : array()), 'test');
    return html('select', array('name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'class'=>join_clean(' ', $presentationname, $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $nullallowed || $defaultvalue != '' ? null : 'notempty'), 'readonly'=>$readonly ? 'readonly' : null), join($options));
  }

  function formvalue_enum($field) {
    return parameter('get', "field:$field[fieldname]") ? 1 : 0;
  }

  function list_enum($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_enum() {
    return "";
  }
?>
