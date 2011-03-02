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

  $metabasename      = get_parameter($_GET, 'metabasename');
  $databasename      = get_parameter($_GET, 'databasename');
  $tablename         = get_parameter($_GET, 'tablename');
  $tablenamesingular = get_parameter($_GET, 'tablenamesingular');
  $uniquefieldname   = get_parameter($_GET, 'uniquefieldname');
  $limit             = get_parameter($_GET, 'limit', null);
  $offset            = get_parameter($_GET, 'offset', null);
  $orderfieldname    = get_parameter($_GET, 'orderfieldname', null);
  $orderasc          = get_parameter($_GET, 'orderasc', 'on') == 'on';

  page('show table', breadcrumbs($metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname),
    list_table($metabasename, $databasename, $tablename, $tablenamesingular, $limit, $offset, $uniquefieldname, null, $orderfieldname, $orderasc, null, null, null, true)
  );
?>
