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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginSurveyticketAnswer extends CommonDBTM {
   
   public $dohistory = true;

   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb = 0) {
      return _n('Answer', 'Answers', $nb, 'surveyticket');
   }



   static function canCreate() {
      return PluginSurveyticketProfile::haveRight("config", 'w');
   }


   static function canView() {
      return PluginSurveyticketProfile::haveRight("config", 'r');
   }

   

   function getSearchOptions() {

      $tab = array();
    
      $tab['common'] = __('Characteristics');

      $tab[1]['table']     = $this->getTable();
      $tab[1]['field']     = 'name';
      $tab[1]['linkfield'] = 'name';
      $tab[1]['name']      = __('Name');
      $tab[1]['datatype']  = 'itemlink';

      return $tab;
   }

   

   /**
    * Get the answer
    * 
    * @param type $data
    * 
    * @return type
    */
   function getAnswer($data) {
      
      if (!empty($data['name'])) {
         return $data['name'];
      } else {
         $itemtype = $data['itemtype'];
         $item = new $itemtype();
         $item->getFromDB($data['items_id']);
         return $item->getName();
      }      
   }
   
   
   
   function listAnswers($questions_id) {
      global $DB,$CFG_GLPI;
      
      $psQuetion = new PluginSurveyticketQuestion();
      
      $_SESSION['glpi_plugins_surveyticket']['questions_id'] = $questions_id;
      
      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>";
      echo _n('Answer', 'Answers', 2, 'surveyticket')." ";
      echo "<a href='".Toolbox::getItemTypeFormURL('PluginSurveyticketAnswer')."?add=1'>
         <img src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'/></a>";
      echo "</th>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo "id";
      echo "</th>";
      echo "<th>";
      echo __('Label');
      echo "</th>";
      echo "<th>";
      echo __('+ field', 'surveyticket');
      echo "</th>";
      echo "<th>";
      echo __('Go to question', 'surveyticket');
      echo "</th>";
      echo "</tr>";
      
      $query= "SELECT * FROM `".$this->getTable()."`
         WHERE `plugin_surveyticket_questions_id` = '".$questions_id."'";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $this->getFromDB($data['id']);
         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         echo "<a href='".$this->getLinkURL()."'>".$data['id']."</a>";
         echo "</td>";
         echo "<td>";
         echo $data['name'];
         echo "</td>";
         echo "<td align='center'>";
         $texttype = array();
         $texttype[''] = "";
         $texttype['shorttext'] = __('Text')." - court";
         $texttype['longtext'] = __('Text')." - long";
         $texttype['date'] = __('Date');
         $texttype['number'] = __('Number');
         echo $texttype[$data['answertype']];
         echo "</td>";
         echo "<td>";
         if ($data['link'] > 0) {
            $psQuetion->getFromDB($data['link']);
            echo $psQuetion->getLink(1);
         }
         echo "</td>";
         echo "</tr>";         
      }
      echo "</table>";
   }
   
   
      
   function showForm($items_id, $options=array()) {

      if ($items_id!='') {
         $this->getFromDB($items_id);
      } else {
         $this->getEmpty();
         if (isset($_SESSION['glpi_plugins_surveyticket']['questions_id'])) {
            $this->fields['plugin_surveyticket_questions_id'] = 
                    $_SESSION['glpi_plugins_surveyticket']['questions_id'];            
         }
      }

      $this->initForm($items_id, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Answer', 'Answers', 1, 'surveyticket')."&nbsp;:</td>";
      echo "<td colspan='3'>";
      $psQuestion = new PluginSurveyticketQuestion();
      $psQuestion->getFromDB($this->fields['plugin_surveyticket_questions_id']);
      echo $psQuestion->getLink();
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Label')."&nbsp;:</td>";
      echo "<td>";
      /////////////////////////// Correction du bug : Ajout d'un nouveau type de question (Texte long) ////////////////////////////////
      switch ($psQuestion->fields['type']){
      	case PluginSurveyticketQuestion::DATE : 
      		echo '<i>'.__('date').'</i>';
      		break;
      	case PluginSurveyticketQuestion::INPUT :
         	echo '<i>'.__('Short text', 'surveyticket').'</i>';
         	break;
        case PluginSurveyticketQuestion::TEXTAREA :
         	echo '<i>'.__('Long text', 'surveyticket').'</i>';
         	break;
        default :
         	echo '<textarea maxlength="255" cols="70" rows="3" name="name">'.$this->fields["name"].'</textarea>'; 
         	break;
      } 
      ///////////////////////////////////////////////////// Fin de la correction du bug /////////////////////////////////////////////////
      
      echo "</td>";
      echo "<td>";
      $psQuestion = new PluginSurveyticketQuestion();
      $psQuestion->getFromDB($_SESSION['glpi_plugins_surveyticket']['questions_id']);
      if ($psQuestion->fields['type'] != 'date'
              && $psQuestion->fields['type'] != 'input' && $psQuestion->fields['type'] != 'textarea') {
         echo __('+ field', 'surveyticket')."&nbsp;:";
      }
      echo "</td>";
      echo "<td>";
      $texttype = array();
      $texttype[''] = Dropdown::EMPTY_VALUE;
      $texttype['shorttext'] = __('Text')." - court";
      $texttype['longtext'] = __('Text')." - long";
      $texttype['date'] = __('Date');
      $texttype['number'] = __('Number');
      
      /////////////////////////////// Correction du bug : Ajout d'un nouveau type de question /////////////////////////////////
      if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type'])) {
         Dropdown::showFromArray("answertype", $texttype, array('value' => $this->fields['answertype']));   
      }
      ////////////////////////////////////////////// Fin de la correction du bug ////////////////////////////////////////////////
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Linked to question', 'surveyticket')."&nbsp;:</td>";
      echo "<td colspan='3'>";
      Dropdown::show("PluginSurveyticketQuestion", array(
          'name'=>'plugin_surveyticket_questions_id',
          'value'=>$this->fields['plugin_surveyticket_questions_id']
         ));
      echo "</td>";
      echo "</tr>";
      
      if ($psQuestion->fields['type'] != PluginSurveyticketQuestion::CHECKBOX) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Go to question', 'surveyticket')."&nbsp;:</td>";
         echo "<td colspan='3'>";
         Dropdown::show("PluginSurveyticketQuestion", array(
             'name'=>'link',
             'value'=>$this->fields['link'],
             'used' => array($psQuestion->getID())
            ));
         echo "</td>";
         echo "<td>" . __('Mandatory', 'surveyticket') . "&nbsp;:</td>";
         echo "</td>";
         echo "<td>";
         Dropdown::showYesNo('mandatory', $this->fields['mandatory']);
         echo "</td>";
         echo "</tr>";
      }
      
      $this->showFormButtons($options);

      return true;
   }
   
   
   
   function addYesNo($questions_id) {
      global $DB;
      
      $query= "SELECT * FROM `".$this->getTable()."`
         WHERE `plugin_surveyticket_questions_id` = '".$questions_id."'
            AND `is_yes`='0' AND `is_no`='0'";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $this->delete($data);
      }
      
      $query= "SELECT * FROM `".$this->getTable()."`
         WHERE `plugin_surveyticket_questions_id` = '".$questions_id."'
            AND `is_yes`='1'";
      $result = $DB->query($query);
      if ($DB->numrows($result) == 0) {
         $input = array();
         $input['plugin_surveyticket_questions_id'] = $questions_id;
         $input['is_yes'] = 1;
         $input['name'] = __('Yes');
         $input['mandatory'] = 0;
         $this->add($input);
      }
      $query= "SELECT * FROM `".$this->getTable()."`
         WHERE `plugin_surveyticket_questions_id` = '".$questions_id."'
            AND `is_no`='1'";
      $result = $DB->query($query);
      if ($DB->numrows($result) == 0) {
         $input = array();
         $input['plugin_surveyticket_questions_id'] = $questions_id;
         $input['is_no'] = 1;
         $input['name'] = __('No');
         $input['mandatory'] = 0;
         $this->add($input);
      }
   }
   
   
   
   function removeYesNo($questions_id) {
      global $DB;
      
      $query= "SELECT * FROM `".$this->getTable()."`
         WHERE `plugin_surveyticket_questions_id` = '".$questions_id."'
            AND `is_yes`='1'";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $this->delete($data);
      }
      $query= "SELECT * FROM `".$this->getTable()."`
         WHERE `plugin_surveyticket_questions_id` = '".$questions_id."'
            AND `is_no`='1'";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $this->delete($data);
      }
   }
   
   function prepareInputForUpdate($input) {
      if (($input['link']) > 0) {
         $surveyquestion  = new PluginSurveyticketSurveyQuestion();
         $surveyquestions = $surveyquestion->find('`plugin_surveyticket_questions_id` = ' . $input['link']);
         foreach ($surveyquestions as $data) {
            $surveys = $surveyquestion->find("`plugin_surveyticket_surveys_id` = " . $data['plugin_surveyticket_surveys_id']);

            foreach ($surveys as $data_survey) {
               $tab    = PluginSurveyticketSurveyQuestion::questionUsed($data_survey['plugin_surveyticket_questions_id']);
               $survey = new PluginSurveyticketSurvey();
               $survey->getFromDB($data_survey['plugin_surveyticket_surveys_id']);
               if (in_array($input['plugin_surveyticket_questions_id'], $tab)) {
                  Session::addMessageAfterRedirect(__('The question is present in the survey', 'surveyticket') . " : " . $survey->fields['name'] . " " . __('Please delete the questionnaire if you want to add it.', 'surveyticket'), false, ERROR);
                  return array();
               }
            }
         }
      } else {
         $input['mandatory'] = 0;
      }
      return $input;
   }
}

?>