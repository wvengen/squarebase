<?php
  /*
    Copyright 2009-2011 Frans Reijnhoudt

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

  $metabasename = get_get('metabasename');
  $databasename = get_get('databasename');

  query('INSERT IGNORE INTO `<metabasename>`.`databases` SET `databasename` = "<databasename>"', array('metabasename'=>$metabasename, 'databasename'=>$databasename));

  if (has_preference('messagy'))
    add_log('message', sprintf(_('database %s attached to metabase %s'), $databasename, $metabasename));

  internal_redirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
?>
