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

function plugin_surveyticket_install() {
   global $DB;

   if (!TableExists('glpi_plugin_surveyticket_questions')) {
      $DB_file = GLPI_ROOT ."/plugins/surveyticket/install/mysql/plugin_surveyticket-empty.sql";
      $DBf_handle = fopen($DB_file, "rt");
      $sql_query = fread($DBf_handle, filesize($DB_file));
      fclose($DBf_handle);
      foreach ( explode(";\n", "$sql_query") as $sql_line) {
         if (Toolbox::get_magic_quotes_runtime()) $sql_line=Toolbox::stripslashes_deep($sql_line);
         if (!empty($sql_line)) $DB->query($sql_line);
      }

      include (GLPI_ROOT . "/plugins/surveyticket/inc/profile.class.php");
      $psProfile = new PluginSurveyticketProfile();
      $psProfile->initProfile();
   }
   
   return true;
}



// Uninstall process for plugin : need to return true if succeeded
function plugin_surveyticket_uninstall() {
   global $DB;

   $query = "SHOW TABLES;";
   $result=$DB->query($query);
   while ($data=$DB->fetch_array($result)) {
      if (strstr($data[0], "glpi_plugin_surveyticket_")) {

         $query_delete = "DROP TABLE `".$data[0]."`;";
         $DB->query($query_delete) or die($DB->error());
      }
   }

   $query="DELETE FROM `glpi_displaypreferences`
           WHERE `itemtype` LIKE 'PluginSurveyticket%';";
   $DB->query($query) or die($DB->error());

   return true;
}



function plugin_surveyticket_post_init() {

   if ((strpos($_SERVER['PHP_SELF'], "ticket.form.php") 
            && !isset($_GET['id'])
            && (!isset($_POST['id'])
               || $_POST['id'] == 0))
     || (strpos($_SERVER['PHP_SELF'], "helpdesk.public.php")
            && isset($_GET['create_ticket']))
     || (strpos($_SERVER['PHP_SELF'], "tracking.injector.php"))) {

      if (isset($_POST)) {
         $psQuestion = new PluginSurveyticketQuestion();
         $psAnswer = new PluginSurveyticketAnswer();
         //print_r($_POST);exit;
         $description = '';
         foreach ($_POST as $question=>$answer) {
            if (strstr($question, "question")
                    && !strstr($question, "realquestion")) {
               $psQuestion->getFromDB(str_replace("question", "", $question));
               if (is_array($answer)) {
                  // Checkbox
                  $description .= _n('Question', 'Questions', 1, 'surveyticket')." : ".$psQuestion->fields['name']."\n";
                  foreach ($answer as $answers_id) {
                     if ($psAnswer->getFromDB($answers_id)) {               
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket')." : ".$psAnswer->fields['name']."\n";
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
                  $real = 0;
                  if (isset($_POST['realquestion'.(str_replace("question", "", $question))])) {
                     $realanswer = $answer;
                     $answer = $_POST['realquestion'.str_replace("question", "", $question)];
                     $real = 1;
                  }
                  if ($psAnswer->getFromDB($answer)) {
                     $description .= _n('Question', 'Questions', 1, 'surveyticket')." : ".$psQuestion->fields['name']."\n";
                     if ($real == 1) {
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket')." : ".$realanswer."\n";
                     } else {
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket')." : ".$psAnswer->fields['name']."\n";
                     }
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
         if ($description != '') {
            $_POST['content'] = addslashes($description);
         }
      }
      if (!isset($_POST['add'])) {
         if (strpos($_SERVER['PHP_SELF'], "ticket.form.php")) {
            Html::header(__('New ticket'), '', "maintain", "ticket");

            PluginSurveyticketSurvey::getCentral();
            Html::footer();
            exit;            
         } else if (strpos($_SERVER['PHP_SELF'], "helpdesk.public.php")
                 || (strpos($_SERVER['PHP_SELF'], "tracking.injector.php"))) {

            Html::helpHeader(__('Simplified interface'), '', $_SESSION["glpiname"]);
            PluginSurveyticketSurvey::getHelpdesk();
            Html::helpFooter();
            exit;
         }
      }
   }
}

?>