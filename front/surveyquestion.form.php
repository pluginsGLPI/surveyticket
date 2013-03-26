<?php

define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/inc/includes.php");

Html::header("surveyticket", $_SERVER["PHP_SELF"], "plugins", 
             "surveyticket", "surveyquestion");

$psSurveyQuestion = new PluginSurveyticketSurveyQuestion();

if (isset ($_POST["add"])) {
   $psSurveyQuestion->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   $psSurveyQuestion->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   $psSurveyQuestion->delete($_POST);
   Html::back();
}

Html::footer();

?>