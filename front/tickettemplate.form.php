<?php

define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/inc/includes.php");

Html::header("surveyticket", $_SERVER["PHP_SELF"], "plugins", 
             "surveyticket", "tickettemplate");

$psticketTemplate = new PluginSurveyticketTicketTemplate();

if (isset ($_POST["add"])) {
   if ($_POST['tickettemplates_id'] == 0) {
      Html::back();
   }
   $psticketTemplate->add($_POST);
   Html::back();
} else if (isset ($_POST["delete"])) {
   $psticketTemplate->delete($_POST);
   Html::back();
}

Html::footer();

?>