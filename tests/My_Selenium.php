<?php
  require_once('Testing/Selenium.php');

  class My_Selenium extends Testing_Selenium {
    public function getContent($locator) {
      return $this->isElementPresent($locator) ? $this->getText($locator) : null;
    }

    public function waitForAjaxToLoad($timeout) {
      return $this->waitForCondition('selenium.browserbot.getCurrentWindow().jQuery.active == 0', $timeout);
    }
  }
?>
