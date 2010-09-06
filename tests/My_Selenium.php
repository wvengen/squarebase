<?php
  require_once('Testing/Selenium.php');

  class My_Selenium extends Testing_Selenium {
    public function startAndShowProgress() {
      $this->start();
      $this->equal();
    }

    public function stopAndClearProgress($error = null) {
      $this->stop();
      print sprintf("\r%s\r%s", str_repeat(' ', 120), $error);
      if ($error)
        exit(1);
   }

    //returns a nicely formatted value
    private function value($value, $level = '') {
      if (is_array($value)) {
        $return = array();
        foreach($value as $key=>$element)
          $return[] = "\n".$level.$key.': '.$this->value($element, $level.'  ');
        return join($return);
      }
      if (is_null($value))
        return 'null';
      if (is_bool($value))
        return ($value ? 'true' : 'false');
      if (is_numeric($value))
        return $value;
      if (preg_match('@[\x00-\x1f\x7f-\xff]@', $value))
        return 'md5('.md5($value).')';
      return '"'.$value.'"';
    }       

    private function combine($found, $expected) {
      $array1 = explode("\n", $this->value($found));
      $array2 = explode("\n", $this->value($expected));
      array_shift($array1);
      array_shift($array2);
      $max = max(count($array1), count($array2));
      $array1 = array_pad($array1, $max, '-');
      $array2 = array_pad($array2, $max, '-');
      $return = array();
      for ($i = 0; $i < $max; $i++)
        $return[] = $array1[$i] == $array2[$i] ? $array1[$i] : sprintf('%-60.60s %-60.60s', $array1[$i], $array2[$i]);
      return sprintf("%-60.60s %-60.60s\n%s\n%s", 'FOUND', 'EXPECTED', str_repeat('-', 2 * 60 + 1), join("\n", $return));
    }

    //tests the equality of its parameters and updates the progress
    public function equal($found = null, $expected = null) {
      static $source = null;
      $trace = debug_backtrace();
      $firstcall = array_pop($trace);
      if (is_null($source))
        $source = file($firstcall['file']);
      $linenumber = $firstcall['line'];
      $maxlinenumber = count($source);
      if ($found != $expected)
        $this->stopAndClearProgress(sprintf("%3d/%d : %s%s\n", $linenumber, $maxlinenumber, $source[$linenumber - 1], $this->combine($found, $expected)));
      $percentage = max(min(floor($linenumber / $maxlinenumber * 100), 100), 0);
      print sprintf("\r%3d/%d = %3d%% [%s>%s]", $linenumber, $maxlinenumber, $percentage, str_repeat('=', $percentage), str_repeat(' ', 100 - $percentage));
    }

    public function getContent($locator) {
      return $this->isElementPresent($locator) ? $this->getText($locator) : null;
    }

    public function noErrorAndNoWarning() {
      $this->equal($this->getContent('warning'), null);
      $this->equal($this->getContent('error'), null);
    }

    public function noErrorAndNoWarningAndTitle($title) {
      $this->noErrorAndNoWarning();
      $this->equal($this->getTitle(), $title);
    }

    public function clickAndWaitForPageToLoad($locator, $title) {
      $this->click($locator);
      $this->waitForPageToLoad(5000);
      $this->noErrorAndNoWarningAndTitle($title);
    }

    public function clickAndWaitForAjaxToLoad($locator) {
      $this->click($locator);
      $this->waitForCondition('selenium.browserbot.getCurrentWindow().jQuery.active == 0', 5000);
      $this->noErrorAndNoWarning();
    }
  }
?>
