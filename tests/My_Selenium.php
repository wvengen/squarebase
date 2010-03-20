<?php
  require_once('Testing/Selenium.php');

  class My_Selenium extends Testing_Selenium {
    public function getContent($locator) {
      return $this->isElementPresent($locator) ? $this->getText($locator) : null;
    }

    public function clickAndWaitForPageToLoad($locator) {
      $this->click($locator);
      $this->waitForPageToLoad(5000);
    }

    public function clickAndWaitForAjaxToLoad($locator) {
      $this->click($locator);
      $this->waitForCondition('selenium.browserbot.getCurrentWindow().jQuery.active == 0', 5000);
    }
  }
?>
