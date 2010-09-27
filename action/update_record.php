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

  $metabasename            = parameter('post', 'metabasename');
  $databasename            = parameter('post', 'databasename');
  $tablename               = parameter('post', 'tablename');
  $tablenamesingular       = parameter('post', 'tablenamesingular');
  $uniquefieldname         = parameter('post', 'uniquefieldname');
  $uniquevalue             = parameter('post', 'uniquevalue');
  $referencedfromfieldname = parameter('post', 'referencedfromfieldname');
  $back                    = parameter('post', 'back');

  $viewname = table_or_view($metabasename, $databasename, $tablename);

  insert_or_update_from_formvalues($metabasename, $databasename, $tablename, $viewname, $uniquefieldname, $uniquevalue, 'UPDATE');

  back();
?>
