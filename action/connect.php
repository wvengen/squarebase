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

  $usernameandhost = parameter('get', 'lastusernameandhost');
  if (!$usernameandhost)
    $usernameandhost = parameter('get', 'usernameandhost');
  if (preg_match('@^([^\@]+)\@([^\@]+)$@', $usernameandhost, $match)) {
    $username = $match[1];
    $host     = $match[2];
  }
  elseif ($usernameandhost) {
    $username = $usernameandhost;
    $host     = 'localhost';
  }
  else
    internal_redirect(array('action'=>'login', 'error'=>_('no username@host given')));
  $password = parameter('get', 'password');
  $language = parameter('get', 'language');

  login($username, $host, $password, $language);

  $next = parameter('get', 'next');
  internal_redirect(first_non_null(http_parse_query($next), array('action'=>'index')));
?>
