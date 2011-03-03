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

  $rows = array(html('th', array('class'=>'filler'), _('database')).html('th', array('class'=>'secondary'), _('tables')).html('th', array(), ''));
  $databases = all_databases();
  while ($database = mysql_fetch_assoc($databases)) {
    $databasename = $database['schema_name'];
    $dblist = array();
    $dbs = databasenames($databasename);
    if ($dbs) {
      foreach ($dbs as $db)
        $dblist[] = internal_reference(array('action'=>'form_metabase_for_database', 'databasename'=>$db, 'metabasename'=>$databasename), $db);
      $dblist[] = internal_reference(array('action'=>'form_database_for_metabase', 'metabasename'=>$databasename), _('(add database)'));
      $contents = html('ul', array('class'=>'compact'), html('li', array(), $dblist));
    }
    else {
      $tables = query('SELECT table_name FROM information_schema.tables WHERE table_schema = "<databasename>"', array('databasename'=>$databasename));
      if ($tables) {
        $tablelist = array();
        while ($table = mysql_fetch_assoc($tables)) {
          $tablelist[] = $table['table_name'];
        }
        $notshown = null;
        if (count($tablelist) > 5) {
          $notshown = '+ '.join(' ', array_slice($tablelist, 4));
          array_splice($tablelist, 4, count($tablelist), '&hellip;');
        }
        $contents = html('ul', array('class'=>'compact', 'title'=>$notshown), html('li', array(), $tablelist));
      }
    }
    $rows[] =
      html('tr', array('class'=>array(count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
        html('td', array('class'=>'filler'), internal_reference(array('action'=>'language_for_database', 'databasename'=>$databasename), $databasename)).
        html('td', array('class'=>'secondary'), $contents).
        html('td', array('class'=>'secondary'), has_grant('DROP', $databasename) ? internal_reference(array('action'=>'drop_database', 'databasename'=>$databasename), 'drop', array('class'=>'drop')) : '')
      );
  }
  page('new metabase from database', null,
    form(
      html('table', array('class'=>'box'), join($rows))
    )
  );
?>
