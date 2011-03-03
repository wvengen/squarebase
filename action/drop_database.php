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

  $databasename = get_get('databasename', null);

  if ($databasename) {
    page('drop database', breadcrumbs(null, $databasename),
      form(
        html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
        html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>get_referer())).
        html('p', array(), sprintf(_('Drop database %s?'), html('strong', array(), $databasename))).
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'drop_database', 'class'=>'submit')).
        internal_reference(http_parse_url(get_referer()), 'cancel', array('class'=>'cancel')),
        array('method'=>'post')
      )
    );
  }

  $databasename = get_post('databasename');

  query('DROP DATABASE `<databasename>`', array('databasename'=>$databasename));

  if (has_preference('messagy'))
    add_log('message', sprintf(_('database %s dropped'), $databasename));

  back();
?>
