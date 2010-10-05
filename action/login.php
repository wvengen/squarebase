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

  $usernameandhost = get_parameter($_GET, 'usernameandhost', null);
  $username        = get_parameter($_GET, 'username', null);
  $host            = get_parameter($_GET, 'host', null);
  $password        = get_parameter($_GET, 'password', null);
  $next            = get_parameter($_GET, 'next', null);

  if ($usernameandhost == $username.'@'.$host)
    internal_redirect(first_non_null(http_parse_url($next), array('action'=>'index')));

  if (is_null($usernameandhost) && get_parameter($_SESSION, 'username', null))
    internal_redirect(array('action'=>'index'));

  if (!$usernameandhost) {
    $radios = array();
    $lastusernamesandhosts = get_parameter($_COOKIE, 'lastusernamesandhosts', null);
    if ($lastusernamesandhosts) {
      foreach (explode(',', $lastusernamesandhosts) as $thisusernameandhost)
        $radios[] =
          html('input', array('type'=>'radio', 'class'=>join_non_null(' ', 'radio', 'skipfirstfocus'), 'name'=>'lastusernameandhost', 'id'=>"lastusernameandhost:$thisusernameandhost", 'value'=>$thisusernameandhost, 'checked'=>$radios ? null : 'checked')).
          html('label', array('for'=>"lastusernameandhost:$thisusernameandhost"), preg_replace('@\@localhost$@', '', $thisusernameandhost)).
          internal_reference(array('action'=>'forget_username_and_host', 'usernameandhost'=>$thisusernameandhost), 'forget', array('class'=>'forget'));
    }
    if (!$radios)
      $usernameandhost = 'root@localhost';
  }

  page('login', null,
    form(
      ($next ? html('input', array('type'=>'hidden', 'name'=>'next', 'value'=>$next)) : '').
      html('table', array('class'=>'box'),
        inputrow(_('user').'@'._('host'),
          isset($radios)
          ? html('ul', array('class'=>join_non_null(' ', 'minimal', 'lastusernamesandhosts')),
              html('li', array(),
                array_merge(
                  $radios,
                  array(
                    html('input', array('type'=>'radio', 'class'=>join_non_null(' ', 'radio', 'skipfirstfocus'), 'name'=>'lastusernameandhost', 'value'=>'')).
                    html('input', array('type'=>'text', 'class'=>join_non_null(' ', 'afterradio', 'skipfirstfocus'), 'id'=>'usernameandhost', 'name'=>'usernameandhost', 'value'=>$usernameandhost))
                  )
                )
              )
            )
          : html('input', array('type'=>'text', 'class'=>'skipfirstfocus', 'id'=>'usernameandhost', 'name'=>'usernameandhost', 'value'=>$usernameandhost)),
          _('The username@host from the underlying MySql database.')
        ).
        inputrow(_('password'), html('input', array('type'=>'password', 'id'=>'password', 'name'=>'password', 'value'=>$password)), _('The password for username@host from the underlying MySql database.')).
        inputrow(_('language'), select_locale(), _('The default language for displaying translations, dates, numbers, etc.')).
        inputrow(null,  html('input', array('type'=>'submit', 'name'=>'action',   'value'=>'connect', 'class'=>'submit')))
      )
    )
  );
?>
