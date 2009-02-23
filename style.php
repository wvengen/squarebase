<?php
  include('functions.php');

  $css = join(file('style.css'))."\n";

  $presentations = get_presentations();
  foreach ($presentations as $presentation) {
    $css .= call_user_func("css_$presentation");
  }

  header('Content-Type: text/css'); 
  print $css;
?>
