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
  $uniquevalue             = get_parameter($_POST, 'uniquevalue');
  $deleterecord            = get_parameter($_POST, 'deleterecord', null);

  $viewname = table_or_view($metabasename, $databasename, $tablename);

  if ($deleterecord)
    query('DELETE FROM `<databasename>`.`<viewname>` WHERE <uniquefieldname> = "<uniquevalue>"', array('databasename'=>$databasename, 'viewname'=>$viewname, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));
  else
    insert_or_update_from_formvalues($metabasename, $databasename, $tablename, $viewname, $uniquefieldname, $uniquevalue, 'UPDATE');

  back();
?>
