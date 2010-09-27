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

  $metabasename            = parameter('get', 'metabasename');
  $databasename            = parameter('get', 'databasename');
  $tablename               = parameter('get', 'tablename');
  $tablenamesingular       = parameter('get', 'tablenamesingular');
  $uniquefieldname         = parameter('get', 'uniquefieldname');
  $uniquevalue             = parameter('get', 'uniquevalue');
  $referencedfromfieldname = parameter('get', 'referencedfromfieldname');
  $back                    = parameter('get', 'back');

  page('new record', breadcrumbs($metabasename, $databasename, $tablename, $uniquefieldname, $uniquevalue),
    edit_record('INSERT', $metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname, $uniquevalue, $referencedfromfieldname, $back ? $back : parameter('server', 'HTTP_REFERER'))
  );
?>
