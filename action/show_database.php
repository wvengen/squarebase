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

  $metabasename = get_get('metabasename');
  $databasename = get_get('databasename');

  $tables = query('SELECT * FROM `<metabasename>`.tables LEFT JOIN `<metabasename>`.fields ON tables.uniquefieldid = fields.fieldid WHERE intablelist = true ORDER BY tablename', array('metabasename'=>$metabasename));
  $rows = array(html('th', array('class'=>'filler'), _('table')));
  while ($table = mysql_fetch_assoc($tables)) {
    $tablename = $table['tablename'];
    if (has_grant('SELECT', $databasename, table_or_view($metabasename, $databasename, $tablename), '?'))
      $rows[] =
        html('tr', array('class'=>array(count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          html('td', array(),
            internal_reference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$table['singular'], 'uniquefieldname'=>$table['fieldname']), $table['plural'])
          )
        );
  }
  page('show database', breadcrumbs($metabasename, $databasename),
    html('div', array('class'=>'ajax'),
      html('table', array('class'=>'box'), join($rows))
    )
  );
?>
