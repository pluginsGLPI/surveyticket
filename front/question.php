<?php

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}

include (GLPI_ROOT."/inc/includes.php");

Html::header("survey", $_SERVER["PHP_SELF"], "plugins", 
             "surveyticket", "questions");

Search::show('PluginSurveyticketQuestion');

Html::footer();

?>