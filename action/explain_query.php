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

  $query = get_get('query');

  $explanations = query('EXPLAIN EXTENDED '.$query);
  query('SHOW WARNINGS');

  $headings = array();
  for ($i = 0; $i < mysql_num_fields($explanations); $i++) {
    $meta = mysql_fetch_field($explanations, $i);
    $headings[] = $meta->name;
  }

  $rows = array(html('tr', array(), html('th', array(), $headings)));
  while ($explanation = mysql_fetch_assoc($explanations)) {
    $cells = array();
    foreach (array_keys($explanation) as $key) {
      $cells[] = is_null($explanation[$key]) ? '-' : $explanation[$key];
    }
    $rows[] = html('tr', array('class'=>array(count($rows) % 2 ? 'rowodd' : 'roweven', 'list')), html('td', array(), $cells));
  }

  page('explain query', null,
    html('p', array(), $query).
    html('table', array('class'=>'box'),
      join($rows)
    ).
    html('p', array(), external_reference('http://dev.mysql.com/doc/refman/5.0/en/using-explain.html', 'MySQL 5.0 Reference Manual :: 7.2.1 Optimizing Queries with EXPLAIN'))
  );
?>
