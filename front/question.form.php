<?php

/*
   ------------------------------------------------------------------------
   Surveyticket
   Copyright (C) 2012-2014 by the Surveyticket plugin Development Team.

   https://forge.indepnet.net/projects/surveyticket
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Surveyticket plugin project.

   Surveyticket plugin is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Surveyticket plugin is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Surveyticket plugin. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Surveyticket plugin
   @author    David Durieux
   @copyright Copyright (c) 2012-2014 Surveyticket plugin team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet.net/projects/surveyticket
   @since     2012

   ------------------------------------------------------------------------
 */

include ("../../../inc/includes.php");

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