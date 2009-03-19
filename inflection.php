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
    list($noun) = inflect_noun_verbose_internal($noun, $fromquantity);
    return $noun;
  }

  function inflect_noun_verbose_internal($noun, $fromquantity, $forbiddenrule = null) {
    $best_locale = best_locale();
    $rules = read_file("locale/$best_locale/rules_singular_plural.txt", FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
    foreach ($rules as $rule) {
      if ($rule != $forbiddenrule) {
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
    $best_locale = best_locale();
    $rules = read_file("locale/$best_locale/rules_singular_plural.txt", FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
    $tests = read_file("locale/$best_locale/test_singular_plural.txt", FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);
    return array_merge(
      test_lines_internal($rules, true),
      test_lines_internal($tests, false)
    );
  }

  function test_lines_internal($lines, $isrule) {
    $messages = array();
    foreach ($lines as $line) {
      $line = preg_replace('@\^@', '', $line);
      if (substr_count($line, '/') != 2)
        print "incorrect syntax: $line".html('br');
      list($body, $suffixsingular, $suffixplural) = explode('/', $line);
      if (!preg_match('@[\[\]\(\)\?\*\+\.]@', $body)) {
        list($rulesingular, $messagesingular) = test_noun_internal("$body$suffixsingular", 1);
        if ($messagesingular)
          $messages[] = $messagesingular;

        list($ruleplural, $messageplural) = test_noun_internal("$body$suffixplural", 2);
        if ($messageplural)
          $messages[] = $messageplural;

        if ($isrule && $rulesingular && $ruleplural) {
          list($nounsingular2, $rulesingular2) = inflect_noun_verbose_internal("$body$suffixsingular", 1, $rulesingular);
          list($nounplural2, $ruleplural2) = inflect_noun_verbose_internal("$body$suffixplural", 2, $ruleplural);
          if ($nounsingular2 == "$body$suffixplural" && $nounplural2 == "$body$suffixsingular")
            $messages[] = "superfluous rule: $line ($rulesingular2) ($ruleplural2)";
        }
      }
    }
    return $messages;
  }

  function test_noun_internal($noun, $fromquantity) {
    $toquantity = $fromquantity == 1 ? 2 : 1;
    list($noun1, $rule1) = inflect_noun_verbose_internal($noun, $fromquantity);
    list($noun2, $rule2) = inflect_noun_verbose_internal($noun1, $toquantity);
    if ($noun2 != $noun)
      return array(null, "$fromquantity $noun &rarr; $rule1 &rarr; $toquantity $noun1 &rarr; $rule2 &rarr; $fromquantity $noun2");
    return array($rule1, null);
  }
?>
