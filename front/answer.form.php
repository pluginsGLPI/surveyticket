<?php

define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/inc/includes.php");

Html::header("survey", $_SERVER["PHP_SELF"], "plugins", 
             "surveyticket", "answers");

$psAnswer = new PluginSurveyticketAnswer();

if (isset ($_POST["add"])) {
   $psAnswer->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   $psAnswer->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   $psAnswer->delete($_POST);
   Html::redirect(Toolbox::getItemTypeSearchURL('PluginSurveyticketAnswer'));
}


if (isset($_GET["id"])) {
   $psAnswer->showForm($_GET["id"]);
   
} else {
   $psAnswer->showForm(0);
}

Html::footer();

?>