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