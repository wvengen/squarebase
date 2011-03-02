<?php
  /*
    Copyright 2009,2010 Frans Reijnhoudt

    This file is part of Squarebase.

    Squarebase is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Squarebase is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
  */

  include('functions.php');

  init();

  $metabasename            = get_parameter($_POST, 'metabasename');
  $databasename            = get_parameter($_POST, 'databasename');
  $tablename               = get_parameter($_POST, 'tablename');
  $tablenamesingular       = get_parameter($_POST, 'tablenamesingular');
  $uniquefieldname         = get_parameter($_POST, 'uniquefieldname');
  $addrecordandedit        = get_parameter($_POST, 'addrecordandedit', null);
  $back                    = get_parameter($_POST, 'back', null);
  $ajax                    = get_parameter($_POST, 'ajax', null);

  $viewname = table_or_view($metabasename, $databasename, $tablename);

  $uniquevalue = insert_or_update_from_formvalues($metabasename, $databasename, $tablename, $viewname, $uniquefieldname, null, 'INSERT');

  if ($uniquevalue) {
    if (has_preference('messagy'))
      add_log('message', sprintf(_('added %s %s'), $tablenamesingular, description($metabasename, $databasename, $tablename, $viewname, $uniquefieldname, $uniquevalue)));

    if ($ajax)
      set_parameter($_POST, 'ajax', preg_replace('@\bvalue=\d+\b@', '', $ajax)."&value=$uniquevalue".($addrecordandedit ? "&uniquevalue=$uniquevalue" : ''));
    elseif ($addrecordandedit)
      internal_redirect(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue, 'back'=>$back));
  }

  back();
?>
