<?php

define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/inc/includes.php");

Html::header("surveyticket", $_SERVER["PHP_SELF"], "plugins", 
             "surveyticket", "questions");

$psQuestion = new PluginSurveyticketQuestion();
$psAnswer = new PluginSurveyticketAnswer();

if (isset ($_POST["add"])) {
   $questions_id = $psQuestion->add($_POST);
   if ($_POST['type'] == 'yesno') {
      $psAnswer->addYesNo($questions_id);
   } else {
      $psAnswer->removeYesNo($questions_id);
   }
   Html::back();
} else if (isset ($_POST["update"])) {
   $psQuestion->update($_POST);
   if ($_POST['type'] == 'yesno') {
      $psAnswer->addYesNo($_POST['id']);
   } else {
      $psAnswer->removeYesNo($_POST['id']);
   }
   Html::back();
} else if (isset ($_POST["delete"])) {
   $psQuestion->delete($_POST);
   Html::redirect(Toolbox::getItemTypeSearchURL('PluginSurveyticketQuestion'));
}


if (isset($_GET["id"])) {
   $psQuestion->showForm($_GET["id"]);
   
   $psAnswer = new PluginSurveyticketAnswer();
   $psAnswer->listAnswers($_GET["id"]);
   
} else {
   $psQuestion->showForm(0);
}

Html::footer();

?>