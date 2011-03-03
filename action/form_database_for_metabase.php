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
  $rows = array(html('th', array(), _('database')).html('th', array('class'=>'filler'), ''));
  $databasenames = databasenames($metabasename);

  $databases = all_databases();
  while ($database = mysql_fetch_assoc($databases)) {
    $databasename = $database['schema_name'];
    $rows[] =
      html('tr', array('class'=>array(count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
        html('td', array(),
          array(
            internal_reference(array('action'=>'attach_database_to_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $databasename),
            internal_reference(array('action'=>'attach_database_to_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename), in_array($databasename, $databasenames) ? 'update' : 'add')
          )
        )
      );
  }
  $rows[] =
    html('tr', array('class'=>count($rows) % 2 ? 'rowodd' : 'roweven'),
      html('td', array(),
        array(
          html('input', array('type'=>'text', 'name'=>'databasename')),
          html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
          html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'attach database to metabase', 'class'=>'submit'))
        )
      )
    );
  page('form database for metabase', breadcrumbs($metabasename),
    form(
      html('table', array('class'=>'box'), html('tr', array(), $rows))
    )
  );
?>
