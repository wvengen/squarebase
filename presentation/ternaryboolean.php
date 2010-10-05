<?php
  function probability_ternaryboolean($field) {
    if ($field['is_nullable'])
      return 0;
    $probability_boolean = probability_boolean($field);
    return $probability_boolean ? $probability_boolean + 0.1 : 0;
  }

  function in_desc_ternaryboolean($field) { return 0; }
  function in_list_ternaryboolean($field) { return 0; }
  function in_edit_ternaryboolean($field) { return 1; }

  function is_sortable_ternaryboolean() { return true; }
  function is_quickaddable_ternaryboolean() { return true; }

  function formfield_ternaryboolean($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return
      html('select', array('name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'class'=>join_non_null(' ', $field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'readonly'=>$readonly ? 'readonly' : null),
        (!$readonly || is_null($value) ? html('option', array_merge(array('value'=>''   ), is_null($value) ? array('selected'=>'selected') : array()), ''      ) : '').
        (!$readonly || $value == '0'   ? html('option', array_merge(array('value'=>'no' ), $value == '0'   ? array('selected'=>'selected') : array()), _('no' )) : '').
        (!$readonly || $value == '1'   ? html('option', array_merge(array('value'=>'yes'), $value == '1'   ? array('selected'=>'selected') : array()), _('yes')) : '')
      );
  }

  function formvalue_ternaryboolean($field) {
    $value = get_parameter($_POST, "field:$field[fieldname]", null);
    return $value == 'yes' ? 1 : ($value == 'no' ? 0 : null);
  }

  function list_ternaryboolean($metabasename, $databasename, $field, $value) {
    return formfield_ternaryboolean($metabasename, $databasename, $field, $value, true, true, false);
  }
  
  function css_ternaryboolean() {
    return "th.ternaryboolean, td.ternaryboolean { text-align: right; }\n";
  }
?>
