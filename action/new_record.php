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

  $metabasename            = get_parameter($_GET, 'metabasename');
  $databasename            = get_parameter($_GET, 'databasename');
  $tablename               = get_parameter($_GET, 'tablename');
  $tablenamesingular       = get_parameter($_GET, 'tablenamesingular');
  $uniquefieldname         = get_parameter($_GET, 'uniquefieldname');
  $referencedfromfieldname = get_parameter($_GET, 'referencedfromfieldname', null);

  page('new record', breadcrumbs($metabasename, $databasename, $tablename),
    edit_record('INSERT', $metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname, null, $referencedfromfieldname)
  );
?>
