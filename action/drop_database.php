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

  $databasename = get_parameter($_GET, 'databasename', null);

  if ($databasename) {
    page('drop database', breadcrumbs(null, $databasename),
      form(
        html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
        html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>get_parameter($_SERVER, 'HTTP_REFERER'))).
        html('p', array(), sprintf(_('Drop database %s?'), html('strong', array(), $databasename))).
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'drop database', 'class'=>'submit')).
        internal_reference(http_parse_url(get_parameter($_SERVER, 'HTTP_REFERER')), 'cancel', array('class'=>'cancel')),
        'post'
      )
    );
  }

  $databasename = get_parameter($_POST, 'databasename');

  query('DROP DATABASE `<databasename>`', array('databasename'=>$databasename));

  if (has_preference('messagy'))
    add_log('message', sprintf(_('database %s dropped'), $databasename));

  back();
?>
