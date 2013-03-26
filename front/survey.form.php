<?php

define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/inc/includes.php");

Html::header("surveyticket", $_SERVER["PHP_SELF"], "plugins", 
             "surveyticket", "survey");

$psSurvey = new PluginSurveyticketSurvey();

if (isset ($_POST["add"])) {
   $psSurvey->add($_POST);
   Html::back();
} else if (isset ($_POST["update"])) {
   $psSurvey->update($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   $psSurvey->delete($_POST);
   Html::redirect(Toolbox::getItemTypeSearchURL('PluginSurveyticketSurvey'));
}


if (isset($_GET["id"])) {
   $psSurvey->showForm($_GET["id"]);
} else {
   $psSurvey->showForm(0);
}

Html::footer();

?>