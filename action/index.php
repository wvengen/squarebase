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

  $metabases = all_databases();
  $rows = array(html('th', array('class'=>'filler'), _('database')).html('th', array('class'=>'secondary'), _('metabase')).html('th', array(), ''));
  $links = array();
  while ($metabase = mysql_fetch_assoc($metabases)) {
    $metabasename = $metabase['schema_name'];
    $databasenames = databasenames($metabasename);
    foreach ($databasenames as $databasename) {
      $link = array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename);
      $links[] = $link;
      $rows[] =
        html('tr', array('class'=>join_non_null_with_blank(count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          html('td', array('class'=>'filler'),
            internal_reference($link, $databasename)
          ).
          html('td', array('class'=>'secondary'),
            has_grant('DROP', $metabasename)
            ? array(
                internal_reference(array('action'=>'form_metabase_for_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $metabasename),
                internal_reference(array('action'=>'drop_database', 'databasename'=>$metabasename), 'drop', array('class'=>'drop'))
              )
            : array('', '')
          )
        );
    }
  }

  $can_create = has_grant('CREATE', '?');

  if (count($links) == 0 && $can_create)
    internal_redirect(array('action'=>'new_metabase_from_database'));

  if (count($links) == 1 && !$can_create)
    internal_redirect($links[0]);

  page('index', null,
    html('table', array('class'=>'box'), join($rows)).
    ($can_create ? internal_reference(array('action'=>'new_metabase_from_database'), _('new metabase from database')) : '')
  );
?>
