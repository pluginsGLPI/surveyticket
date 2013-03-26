<?php

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");


Session::checkLoginUser();

if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   Html::helpHeader("survey", $_SERVER["PHP_SELF"], "plugins", 
             "surveyticket", "survey");
} else {
   Html::header("survey", $_SERVER["PHP_SELF"], "plugins", 
             "surveyticket", "survey");
}


$psSurvey = new PluginSurveyticketSurvey();
$psSurvey->showFormHelpdesk(Session::getLoginUserID());


Html::footer();

?>