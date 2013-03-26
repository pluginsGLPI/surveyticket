<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginSurveyticketSurveyQuestion extends CommonDBTM {
   
   static function getTypeName() {
      return "Questions";
   }



   function canCreate() {
      return true;
   }


   function canView() {
      return true;
   }
   
   
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType()=='PluginSurveyticketSurvey') {
         return "Questions";
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
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      if ($item->getType() == 'PluginSurveyticketSurvey') {
         $pfSurveyQuestion = new self();
         $pfSurveyQuestion->showQuestions($item->getID());
      }
      return TRUE;
   }
   
   
   
   function showQuestions($items_id) {
      global $CFG_GLPI, $LANG;
      
      $psQuestion = new PluginSurveyticketQuestion();
      
      echo "<form method='post' name='form_addquestion' action='".$CFG_GLPI['root_doc'].
             "/plugins/surveyticket/front/surveyquestion.form.php'>";

      echo "<table class='tab_cadre' width='700'>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>Question&nbsp;:</td>";
      echo "<td>";
      $a_questions = $this->find("`plugin_surveyticket_surveys_id`='".$items_id."'", "`order`");
      $a_used = array();
      foreach ($a_questions as $data) {
         $a_used[] = $data['plugin_surveyticket_questions_id'];
      }
      
      Dropdown::show("PluginSurveyticketQuestion", 
                     array("name" => "plugin_surveyticket_questions_id",
                           "used" => $a_used)
                    );
      echo "</td>";
      echo "<td>Order&nbsp;:</td>";
      echo "<td>";
      Dropdown::showInteger("order", "0", 0, 20);
      echo "</td>";
      echo "</tr>";
      

      echo "<tr>";
      echo "<td class='tab_bg_2 top' colspan='4'>";
      echo "<input type='hidden' name='plugin_surveyticket_surveys_id' value='".$items_id."'>";
      echo "<div class='center'>";
      echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
      echo "</div></td></tr>";
         
      echo "</table>";
      Html::closeForm();
      
      
      // list questions
      
      
      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo "Question";
      echo "</th>";
      echo "<th>";
      echo "Order";
      echo "</th>";
      echo "<th>";
      echo "</th>";
      echo "</tr>";    
      
      foreach ($a_questions as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         $psQuestion->getFromDB($data['plugin_surveyticket_questions_id']);
         echo $psQuestion->getLink(1);
         echo "</td>";
         echo "<td>";
         echo $data['order'];
         echo "</td>";
         echo "<td align='center'>";
         echo "<form method='post' name='form_addquestion' action='".$CFG_GLPI['root_doc'].
             "/plugins/surveyticket/front/surveyquestion.form.php'>";
         echo "<input type='hidden' name='id' value='".$data['id']."'>";
         echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
         Html::closeForm();
         echo "</td>";
         echo "</tr>";    
      }
      
      echo "</table>";
   }
   
   
}

?>
