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

  $metabasename            = get_post('metabasename');
  $databasename            = get_post('databasename');
  $tablename               = get_post('tablename');
  $tablenamesingular       = get_post('tablenamesingular');
  $uniquefieldname         = get_post('uniquefieldname');
  $uniquevalue             = get_post('uniquevalue');
  $deleterecord            = get_post('deleterecord', null);

  $viewname = table_or_view($metabasename, $databasename, $tablename);

  if ($deleterecord) {
    if (has_preference('messagy'))
      $description = description($metabasename, $databasename, $tablename, $viewname, $uniquefieldname, $uniquevalue);
    query('DELETE FROM `<databasename>`.`<viewname>` WHERE <uniquefieldname> = "<uniquevalue>"', array('databasename'=>$databasename, 'viewname'=>$viewname, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));
    if (has_preference('messagy'))
      add_log('message', sprintf(_('deleted %s %s'), $tablenamesingular, $description));
  }
  else {
    insert_or_update_from_formvalues($metabasename, $databasename, $tablename, $viewname, $uniquefieldname, $uniquevalue, 'UPDATE');
    if (has_preference('messagy'))
      add_log('message', sprintf(_('updated %s %s'), $tablenamesingular, description($metabasename, $databasename, $tablename, $viewname, $uniquefieldname, $uniquevalue)));
  }

  back();
?>
