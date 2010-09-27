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

  $metabasename      = parameter('get', 'metabasename');
  $databasename      = parameter('get', 'databasename');
  $tablename         = parameter('get', 'tablename');
  $tablenamesingular = parameter('get', 'tablenamesingular');
  $uniquefieldname   = parameter('get', 'uniquefieldname');
  $limit             = parameter('get', 'limit');
  $offset            = first_non_null(parameter('get', 'offset'), 0);
  $orderfieldname    = parameter('get', 'orderfieldname');
  $orderasc          = first_non_null(parameter('get', 'orderasc'), 'on') == 'on';

  page('show table', breadcrumbs($metabasename, $databasename, $tablename, $uniquefieldname),
    list_table($metabasename, $databasename, $tablename, $tablenamesingular, $limit, $offset, $uniquefieldname, null, $orderfieldname, $orderasc, null, null, null, true)
  );
?>
