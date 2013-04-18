<?php

define('GLPI_ROOT','../../..');
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");

$psSurvey = new PluginSurveyticketSurvey();
$psAnswer = new PluginSurveyticketAnswer();
if ($psAnswer->getFromDB($_POST[$_POST['myname']])) {
   if ($psAnswer->fields['link'] > 0) {
      $psSurvey->displaySurvey($psAnswer->fields['link']);
   }
}

?>