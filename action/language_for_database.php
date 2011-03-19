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

  $databasename = get_get('databasename');

  $tables = query('SELECT table_name FROM information_schema.tables WHERE table_schema = "<databasename>"', array('databasename'=>$databasename));
  if ($tables) {
    $tablelist = array();
    while ($table = mysql_fetch_assoc($tables)) {
      $tablelist[] = $table['table_name'];
    }
    $tables = join(', ', $tablelist);
  }

  page('language for database', breadcrumbs(null, $databasename),
    form(
      html('table', array('class'=>'box'),
        inputrow(_('database'), html('input', array('type'=>'text', 'id'=>'databasename', 'name'=>'databasename', 'value'=>$databasename, 'readonly'=>'readonly', 'class'=>'readonly')), _('The name of the database to build a metabase for.')).
        inputrow(_('tables'), html('input', array('type'=>'text', 'id'=>'tables', 'name'=>'tables', 'value'=>$tables, 'title'=>$tables, 'readonly'=>'readonly', 'class'=>'readonly')), _('The names of the tables in this database.')).
        inputrow(_('language'), select_locale(), _('The language for displaying dates, numbers, etc in this database.'))
      ).
      html('p', array(),
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'form_metabase_for_database', 'class'=>array('submit', 'formmetabasefordatabase')))
      )
    )
  );
?>
