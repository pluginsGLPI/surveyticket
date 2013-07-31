<?php

/*
   ------------------------------------------------------------------------
   Surveyticket
   Copyright (C) 2012-2013 by the Surveyticket plugin Development Team.

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
   @copyright Copyright (c) 2012-2013 Surveyticket plugin team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet.net/projects/surveyticket
   @since     2012

   ------------------------------------------------------------------------
 */

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT."/inc/includes.php");

Session::checkLoginUser();

//if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
//   Html::helpHeader("survey", $_SERVER["PHP_SELF"], "plugins", 
//             "surveyticket", "displaysurvey");
//} else {
//   Html::header("survey", $_SERVER["PHP_SELF"], "plugins", 
//             "surveyticket", "displaysurvey");
//}

$psQuestion = new PluginSurveyticketQuestion();
$psAnswer = new PluginSurveyticketAnswer();
//print_r($_POST);exit;
$description = '';
foreach ($_POST as $question=>$answer) {
   if (strstr($question, "question")) {
      $psQuestion->getFromDB(str_replace("question", "", $question));
      if (is_array($answer)) {
         // Checkbox
         $description .= "Question : ".$psQuestion->fields['name']."\n";
         foreach ($answer as $answers_id) {
            if ($psAnswer->getFromDB($answers_id)) {               
               $description .= "Réponse : ".$psAnswer->fields['name']."\n";
               $qid = str_replace("question", "", $question);
               if (isset($_POST["text-".$qid."-".$answers_id])
                       AND $_POST["text-".$qid."-".$answers_id] != '') {
                  $description .= "Texte : ".$_POST["text-".$qid."-".$answers_id]."\n";
               }
            }
         }
         $description .= "\n";
         unset($_POST[$question]);
      } else {
         if ($psAnswer->getFromDB($answer)) {
            $description .= "Question : ".$psQuestion->fields['name']."\n";
            $description .= "Réponse : ".$psAnswer->fields['name']."\n";
            $qid = str_replace("question", "", $question);
            if (isset($_POST["text-".$qid."-".$answer])
                    AND $_POST["text-".$qid."-".$answer] != '') {
               $description .= "Texte : ".$_POST["text-".$qid."-".$answer]."\n";
            }
            $description .= "\n";
            unset($_POST[$question]);
         }
      }
   }
}


if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   $_POST['content'] = addslashes($description);

         if (empty($_POST["_type"])
             || ($_POST["_type"] != "Helpdesk")
             || !$CFG_GLPI["use_anonymous_helpdesk"]) {
            Session::checkRight("create_ticket", "1");
         }

         $track = new Ticket();

         // Security check
         if (empty($_POST) || count($_POST) == 0) {
            Html::redirect($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
         }

         if (isset($_POST["_type"]) && ($_POST["_type"] == "Helpdesk")) {
            Html::nullHeader($LANG['title'][10]);
         } else if ($_POST["_from_helpdesk"]) {
            Html::helpHeader($LANG['Menu'][31],'',$_SESSION["glpiname"]);
         } else {
            Html::header($LANG['Menu'][31],'',$_SESSION["glpiname"],"maintain","tracking");
         }

         if (isset($_POST["_my_items"]) && !empty($_POST["_my_items"])) {
            $splitter = explode("_",$_POST["_my_items"]);
            if (count($splitter) == 2) {
               $_POST["itemtype"] = $splitter[0];
               $_POST["items_id"] = $splitter[1];
            }
         }

         if (!isset($_POST["itemtype"]) || (empty($_POST["items_id"]) && $_POST["itemtype"] != 0)) {
            $_POST["itemtype"] = '';
            $_POST["items_id"] = 0;
         }


         if (isset($_POST['add'])) {
            if ($newID = $track->add($_POST)) {
               if (isset($_POST["_type"]) && ($_POST["_type"] == "Helpdesk")) {
                  echo "<div class='center'>".$LANG['help'][18]."<br><br>";
                  Html::displayBackLink();
                  echo "</div>";
               } else {
                  echo "<div class='center b spaced'>";
                  echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' alt='OK'>";
                  Session::addMessageAfterRedirect($LANG['help'][19]);
                  Html::displayMessageAfterRedirect();
                  echo "</div>";
               }

            } else {
               echo "<div class='center'>";
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/warning.png' alt='warning'><br>";
               Html::displayMessageAfterRedirect();
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?create_ticket=1'>";
               echo $LANG['buttons'][13]."</a>";

               echo "</div>";

            }
            Html::nullFooter();

         } else { // reload display form
            $_SESSION["helpdeskSaved"] = $_REQUEST;
            PluginSurveyticketSurvey::showFormHelpdesk(Session::getLoginUserID());
            Html::helpFooter();
         }
   
//   $_SESSION["helpdeskSaved"]['content'] = $description;
//   Html::redirect($CFG_GLPI['root_doc']."/front/helpdesk.public.php?create_ticket=1");
} else {
//   $_SESSION["helpdeskSaved"]['content'] = $description;
   $_POST['content'] = Toolbox::addslashes_deep($description);
//   include(GLPI_ROOT."/front/ticket.form.php"); 

   $_GET['id'] = "";

   if (isset($_POST["add"])) {
      $track = new Ticket();
      $track->check(-1,'w',$_POST);

      if (isset($_POST["_my_items"]) && !empty($_POST["_my_items"])) {
         $splitter = explode("_",$_POST["_my_items"]);
         if (count($splitter) == 2) {
            $_POST["itemtype"] = $splitter[0];
            $_POST["items_id"] = $splitter[1];
         }
      }
      $track->add($_POST);
      Html::back();
   } else {
      $_SESSION["helpdeskSaved"] = $_POST;
      Html::redirect($CFG_GLPI['root_doc']."/front/ticket.form.php");
      Html::footer();
   }
}



?>