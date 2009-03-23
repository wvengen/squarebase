<?php
  //rules are in local/<locale>/rules_singular_plural.txt
  //a rule has the form "body/suffixsingular/suffixplural"
  //backrefences are allowed
  //if a backrefence references a group in the other suffix, use the syntax \<number>=(<group>), so the regexp will contain the group and the replacement the backreference

  //external functions

  function pluralize_noun($noun) {
    return inflect_noun_internal($noun, 1);
  }

  function singularize_noun($noun) {
    return inflect_noun_internal($noun, 2);
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
    $rules = read_file("locale/$current_locale/rules_singular_plural.txt", FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
    foreach ($rules as $rule) {
      if ($rule && $rule != $forbiddenrule) {
        list($body, $suffixsingular, $suffixplural) = explode('/', $rule);
        list($from, $to) = $fromquantity == 1 ? array($suffixsingular, $suffixplural) : array($suffixplural, $suffixsingular);
        //\<number>=(<group>) => (<group>)
        //\<number> => \<number+1> beacuse an extra group ($body) is prepended
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

  function test_plural_singular_internal() {
    $current_locale = preg_replace('/\..*/', '', setlocale(LC_ALL, 0));
    $rules = read_file("locale/$current_locale/rules_singular_plural.txt", FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
    $tests = read_file("locale/$current_locale/tests_singular_plural.txt", FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
    return array_merge(
      test_lines_internal($rules, $rules),
      test_lines_internal($tests, $rules)
    );
  }

  function test_lines_internal($lines, $rules) {
    if ($lines != $rules)
      $used = array_fill_keys($rules, 0);
    $messages = array();
    foreach ($lines as $line) {
      $line = preg_replace('@\^@', '', $line);
      if (substr_count($line, '/') != 2)
        $messages[] = "not exactly 2 slashes: $line";
      list($body, $suffixsingular, $suffixplural) = explode('/', $line);
      if (!preg_match('@[\[\]\(\)\?\*\+\.\^]@', $body)) {
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
    if ($lines != $rules) {
      foreach ($used as $rule=>$count)
        if ($count == 0)
          $messages[] = "unused rule: $rule";
    }
    return $messages;
  }
?>
