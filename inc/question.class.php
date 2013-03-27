<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginSurveyticketQuestion extends CommonDBTM {
   
   public $dohistory = true;

   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName() {
      global $LANG;

      return "question";
   }



   function canCreate() {
      return true;
   }


   function canView() {
      return true;
   }

   

   function getSearchOptions() {
      global $LANG;

      $tab = array();
    
      $tab['common'] = "question";

      $tab[1]['table']     = $this->getTable();
      $tab[1]['field']     = 'name';
      $tab[1]['linkfield'] = 'name';
      $tab[1]['name']      = $LANG['common'][16];
      $tab[1]['datatype']  = 'itemlink';

      return $tab;
   }
   
   
   
   function showForm($items_id, $options=array()) {
      global $LANG;

      if ($items_id!='') {
         $this->getFromDB($items_id);
      } else {
         $this->getEmpty();
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td rowspan='2'>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td rowspan='2'>";
      //echo '<input type="text" name="name" value="'.$this->fields["name"].'" size="50"/>';
      echo '<textarea maxlength="255" cols="70" rows="3"
         name="name">'.$this->fields["name"].'</textarea>';
      echo "</td>";
      echo "<td>Type&nbsp;:</td>";
      echo "<td>";
      $array = array();
      $array['yesno'] = 'Yes/No';
      $array['dropdown'] = 'dropdown';
      $array['checkbox'] = 'checkbox';
      $array['radio'] = 'radio';
      $array['date'] = 'date';
      
      Dropdown::showFromArray('type', $array, array('value'=>$this->fields['type']));
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td></td>";
      echo "<td>";

      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td colspan='3' class='middle'>";
      echo "<textarea cols='110' rows='3' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td>";
      echo "</tr>";
      
      $this->showFormButtons($options);

      return true;
   }


}

?>