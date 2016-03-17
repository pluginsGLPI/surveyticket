<?php

/*
  ------------------------------------------------------------------------
  Surveyticket
  Copyright (C) 2012-2016 by the Surveyticket plugin Development Team.

  https://forge.glpi-project.org/projects/surveyticket
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
  @author    Infotel
  @copyright Copyright (c) 2012-2016 Surveyticket plugin team
  @license   AGPL License 3.0 or (at your option) any later version
  http://www.gnu.org/licenses/agpl-3.0-standalone.html
  @link      https://forge.glpi-project.org/projects/surveyticket
  @since     2012

  ------------------------------------------------------------------------
 */


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginSurveyticketSurveyQuestion extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return _n('Question', 'Questions', $nb, 'surveyticket');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'PluginSurveyticketSurvey') {
         return _n('Question', 'Questions', 2, 'surveyticket');
      }
      return '';
   }

   /**
    * Display content of tab
    *
    * @param CommonGLPI $item
    * @param integer $tabnum
    * @param interger $withtemplate
    *
    * @return boolean TRUE
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'PluginSurveyticketSurvey') {
         $pfSurveyQuestion = new self();
         $pfSurveyQuestion->showQuestions($item->getID());
      }
      return TRUE;
   }

   static function questionUsed($plugin_surveyticket_questions_id, $a_used = array()) {
      $psAnswer = new PluginSurveyticketAnswer();
      $result = $psAnswer->find("`plugin_surveyticket_questions_id` = " . $plugin_surveyticket_questions_id);
      foreach ($result as $data) {
         if ($data['link'] > 0) {
            $a_used[] = $data['link'];
            $a_used = self::questionUsed($data['link'], $a_used);
         }
      }
      return $a_used;
   }

   function showQuestions($items_id) {
      global $CFG_GLPI;

      echo "<form method='post' name='form_addquestion' action='" . $CFG_GLPI['root_doc'] .
      "/plugins/surveyticket/front/surveyquestion.form.php'>";

      echo "<table class='tab_cadre' width='700'>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . _n('Question', 'Questions', 1, 'surveyticket') . "&nbsp;:</td>";
      echo "<td>";
      $a_questions = $this->find("`plugin_surveyticket_surveys_id`='" . $items_id . "'", "`order`");
      $a_used = array();
      foreach ($a_questions as $data) {
         $a_used[] = $data['plugin_surveyticket_questions_id'];
         //recovery of links to other issues
         $a_used = self::questionUsed($data['plugin_surveyticket_questions_id'], $a_used);
      }

      Dropdown::show("PluginSurveyticketQuestion", array("name" => "plugin_surveyticket_questions_id",
         "used" => $a_used)
      );
      echo "</td>";
      echo "<td>" . __('Position') . "&nbsp;:</td>";
      echo "<td>";
      Dropdown::showInteger("order", "0", 0, 20);
      echo "</td>";

      echo "<td>" . __('Mandatory', 'surveyticket') . "&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo('mandatory');
      echo "</td>";
      echo "</tr>";


      echo "<tr>";
      echo "<td class='tab_bg_2 top' colspan='6'>";
      echo "<input type='hidden' name='plugin_surveyticket_surveys_id' value='" . $items_id . "'>";
      echo "<div class='center'>";
      echo "<input type='submit' name='add' value=\"" . __('Add') . "\" class='submit'>";
      echo "</div></td></tr>";

      echo "</table>";
      Html::closeForm();


      // list questions
      self::showListQuestions($a_questions);
   }

   static function showListQuestions($a_questions) {
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo _n('Question', 'Questions', 1, 'surveyticket');
      echo "</th>";
      echo "<th>";
      echo __('Type');
      echo "</th>";
      echo "<th>";
      echo __('Position') . " / " . __('Link');
      echo "</th>";
      echo "<th>";
      echo __('Mandatory', 'surveyticket');
      echo "</th>";
      echo "<th>";
      echo "</th>";
      echo "</tr>";
      foreach ($a_questions as $data) {
         self::showQuestion($data);
      }
      echo "</table>";
   }

   static function showQuestion($data) {
      global $CFG_GLPI;
      $psQuestion = new PluginSurveyticketQuestion();
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      $psQuestion->getFromDB($data['plugin_surveyticket_questions_id']);
      echo $psQuestion->getLink(1);
      echo "</td>";
      echo "<td>";
      echo PluginSurveyticketQuestion::getQuestionTypeName($psQuestion->fields['type']);
      echo "</td>";
      if (isset($data['id'])) {
         echo "<td>";
         echo $data['order'];
         echo "</td>";
         echo "<td>";
         if ($data['mandatory']) {
            echo __('Yes');
         } else {
            //no
            echo __('No');
         }
         echo "</td>";
         echo "<td align='center'>";
         echo "<form method='post' name='form_addquestion' action='" . $CFG_GLPI['root_doc'] .
         "/plugins/surveyticket/front/surveyquestion.form.php'>";
         echo "<input type='hidden' name='id' value='" . $data['id'] . "'>";
         echo "<input type='submit' name='delete' value=\"" . _sx('button', 'Delete permanently') . "\" class='submit'>";
         Html::closeForm();
         echo "</td>";
      } else {
         //question display which is linked to other
//         $psQuestion->getFromDB($data['old_plugin_surveyticket_questions_id']);
//         echo "<td>";
//         echo $psQuestion->getLink(1);
//         echo "</td>";
//         echo "<td>";
//         echo __('No');
//         echo "</td>";
//         echo "<td align='center'>";
//         echo "</td>";
          echo "<td>";
         echo '0';
         echo "</td>";
         echo "<td>";

            echo __('No');
         echo "</td><td align='center'></td>";
      }

      echo "</tr>";
      self::showLinkQuestion($data['plugin_surveyticket_questions_id']);
   }

   static function showLinkQuestion($plugin_surveyticket_questions_id) {
      $psAnswer = new PluginSurveyticketAnswer();
      $result = $psAnswer->find("`plugin_surveyticket_questions_id` = " . $plugin_surveyticket_questions_id);
      $psQuestionName = new PluginSurveyticketQuestion();
      $psQuestion = new PluginSurveyticketQuestion();
      foreach ($result as $data) {
         if ($data['link'] > 0) {
            $psQuestion->getFromDB($data['link']);
            $psQuestionName->getFromDB($data['plugin_surveyticket_questions_id']);
            echo "<tr class='tab_bg_2'>";
            echo "<td>".__('Answer', 'surveyticket')." : ".$psQuestionName->fields['name']."</td><td>";
            echo $data['name'];
            echo "</td><td>".$psQuestion->getLink(1)."</td><td colspan='2'></td>";
            echo "</tr>";
            self::showQuestion(array('plugin_surveyticket_questions_id' => $data['link'], 'old_plugin_surveyticket_questions_id' => $plugin_surveyticket_questions_id));
         }
      }
   }

   /**
    * Actions done before update
    * 
    * @param type $input
    * @return type
    */
   function prepareInputForAdd($input) {
      $msg = array();
      $psAnswer = new PluginSurveyticketAnswer();
      $result = $psAnswer->find("`plugin_surveyticket_questions_id` = " . $input['plugin_surveyticket_questions_id']);
      $bool = false;
      $survey = new PluginSurveyticketSurveyQuestion();
      $a_questions = $survey->find("`plugin_surveyticket_surveys_id`='" . $input['plugin_surveyticket_surveys_id'] . "'", "`order`");
      $psQuestion = new PluginSurveyticketQuestion();
      $a_used = array();
       foreach ($a_questions as $data) {
         $a_used[$data['plugin_surveyticket_questions_id']] = $data['plugin_surveyticket_questions_id'];
       }

      foreach ($result as $data) {
         if ($data['link'] > 0) {
            if (array_key_exists($data['link'], $a_used)) {
               $bool = true;
               $psQuestion->getFromDB($data['link']);
               $msg[] = $psQuestion->fields['name'];
            }
         }
      }
      if ($bool) {
         Session::addMessageAfterRedirect(sprintf(__("The question can not be added because it has links to other questions : %s. Please delete the questionnaire if you want to add it.", 'surveyticket'), implode(', ', $msg)), false, ERROR);
      } else {
         return $input;
      }
   }
   
   static function deleteSurveyQuestion($id){
      $temp = new self();
      $temp->deleteByCriteria(array('plugin_surveyticket_surveys_id' => $id));
   }

}

?>