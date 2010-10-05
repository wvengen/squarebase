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

  $lastusernameandhost = get_parameter($_GET, 'lastusernameandhost', null);
  $usernameandhost     = get_parameter($_GET, 'usernameandhost', null);
  $password            = get_parameter($_GET, 'password');
  $language            = get_parameter($_GET, 'language');
  $next                = get_parameter($_GET, 'next', null);

  $bestusernameandhost = first_non_null($lastusernameandhost, $usernameandhost);
  if (preg_match('@^([^\@]+)\@([^\@]+)$@', $bestusernameandhost, $match)) {
    $username = $match[1];
    $host     = $match[2];
  }
  elseif ($bestusernameandhost) {
    $username = $bestusernameandhost;
    $host     = 'localhost';
  }
  else
    internal_redirect(array('action'=>'login', 'error'=>_('no username@host given')));

  login($username, $host, $password, $language);

  internal_redirect(first_non_null(http_parse_url($next), array('action'=>'index')));
?>
