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

  //rules are in locale/<locale>/rules_singular_plural.txt
  //a rule has the form "<body>/<suffixsingular>/<suffixplural>", so the singular is "...<body><suffixsingular>" and the plural is "...<body><suffixplural>"
  //backrefences are allowed
  //if a backrefence references a group in the other suffix, use the syntax \<number>=(<group>), so the regexp can use the group and the replacement the backreference

  //external functions

  function pluralize_noun($noun) {
    return inflect_noun_internal($noun, 1);
  }

  function singularize_noun($noun) {
    return inflect_noun_internal($noun, 2);
  }

  function test_inflection_singular_plural() {
    $current_locale = preg_replace('/\..*/', '', setlocale(LC_ALL, 0));
    $rules = read_file(array('locale', $current_locale, 'rules_singular_plural.txt'), FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
    $tests = read_file(array('locale', $current_locale, 'tests_singular_plural.txt'), FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
    return array_merge(
      test_lines_internal($rules, $rules),
      test_lines_internal($tests, $rules)
    );
  }

  //internal functions

  function inflect_noun_internal($noun, $fromquantity) {
    //$fromquantity == 1 => $noun is singular
    //$fromquantity != 1 => $noun is plural
    list($inflected_noun) = inflect_noun_verbose_internal($noun, $fromquantity);
    return $inflected_noun;
  }

  function inflect_noun_verbose_internal($noun, $fromquantity, $forbiddenrule = null) {
    $current_locale = preg_replace('/\..*/', '', setlocale(LC_ALL, 0));
    $rules = read_file(array('locale', $current_locale, 'rules_singular_plural.txt'), FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
    foreach ($rules as $rule) {
      if ($rule && $rule[0] != '#' && $rule != $forbiddenrule) {
        list($body, $suffixsingular, $suffixplural) = explode('/', $rule);
        list($from, $to) = $fromquantity == 1 ? array($suffixsingular, $suffixplural) : array($suffixplural, $suffixsingular);
        //\<number>=(<group>) => (<group>)
        //\<number> => \<number+1> because an extra group ($body) is prepended
        $from = preg_replace('@(\\\\)(\d+)(=(\(.*?\)))?@e', "'\\3' ? '\\4' : '\\1'.(\\2 + 1)", $from);
        if (preg_match("@($body)$from$@i", $noun)) {
          //\<number>=(<group>) and \<number> => \<number+1>
          $to = "\\1".preg_replace('@(\\\\)(\d+)(=(\(.*?\)))?@e', "'\\1'.(\\2 + 1)", $to);
          return array(preg_replace("@($body)$from$@i", $to, $noun), $rule);
        }
      }
    }
    return array($noun, null);
  }

  function test_lines_internal($lines, $rules) {
    if ($lines != $rules)
      $used = array_fill_keys($rules, 0);
    $messages = array();
    foreach ($lines as $line) {
      if ($line && $line[0] != '#') {
        $line = preg_replace('@\^@', '', $line);
        if (substr_count($line, '/') != 2)
          $messages[] = "not exactly 2 slashes: $line";
        list($body, $suffixsingular, $suffixplural) = explode('/', $line);
        if (!preg_match('@[\[\]\(\)\?\*\+\.\^\|]@', $body)) {
          list($nounsingular, $rulesingular) = inflect_noun_verbose_internal("$body$suffixsingular", 1);
          if ($nounsingular != "$body$suffixplural")
            $messages[] = "1 $body$suffixsingular &rarr; 2 $nounsingular (instead of $body$suffixplural by rule $rulesingular)";

          list($nounplural, $ruleplural) = inflect_noun_verbose_internal("$body$suffixplural", 2);
          if ($nounplural != "$body$suffixsingular")
            $messages[] = "2 $body$suffixplural &rarr; 1 $nounplural (instead of $body$suffixsingular by rule $ruleplural)";

          if ($lines != $rules) {
            $used[$rulesingular]++;
            $used[$ruleplural]++;
          }
          else {
            if ($rulesingular && $ruleplural) {
              list($nounsingular2, $rulesingular2) = inflect_noun_verbose_internal("$body$suffixsingular", 1, $rulesingular);
              list($nounplural2, $ruleplural2) = inflect_noun_verbose_internal("$body$suffixplural", 2, $ruleplural);
              if ($nounsingular2 == "$body$suffixplural" && $nounplural2 == "$body$suffixsingular")
                $messages[] = "superfluous rule: $line ($rulesingular2) ($ruleplural2)";
            }
          }
        }
      }
    }
    if ($lines != $rules) {
      foreach ($used as $rule=>$count)
        if ($count == 0)
          $messages[] = "unused rule: $rule";
    }
    return $messages;
  }
?>
