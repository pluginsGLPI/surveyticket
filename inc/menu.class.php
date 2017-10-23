<?php

/*
  ------------------------------------------------------------------------
  Surveyticket
  Copyright (C) 2012-2017 by the Surveyticket plugin Development Team.

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
  @copyright Copyright (c) 2012-2017 Surveyticket plugin team
  @license   AGPL License 3.0 or (at your option) any later version
  http://www.gnu.org/licenses/agpl-3.0-standalone.html
  @link      https://github.com/pluginsGLPI/surveyticket
  @since     2012

  ------------------------------------------------------------------------
 */


/**
 * Class PluginSurveyticketMenu
 */
class PluginSurveyticketMenu extends CommonGLPI {
   static $rightname = 'config';

   function showMenu(){
      global $CFG_GLPI;
      echo "<table class='tab_cadre' width='250'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo __('Menu', 'surveyticket');
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/surveyticket/front/survey.php'>" .
      _n('Survey', 'Surveys', 2, 'surveyticket') . "</a>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/surveyticket/front/question.php'>" .
      _n('Question', 'Questions', 2, 'surveyticket') . "</a>";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
   }


   static function getMenuName() {
      return __('Survey', 'surveyticket');
   }


   /**
    * @return array
    */
   static function getMenuContent() {
      $menu          = array();
      $menu['title'] = self::getMenuName();
      $menu['page']  = '/plugins/surveyticket/front/menu.php';

      return $menu;
   }



   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['assets']['types']['PluginSurveyticketMenu'])) {
         unset($_SESSION['glpimenu']['assets']['types']['PluginSurveyticketMenu']);
      }
      if (isset($_SESSION['glpimenu']['assets']['content']['PluginSurveyticketMenu'])) {
         unset($_SESSION['glpimenu']['assets']['content']['PluginSurveyticketMenu']);
      }
   }
}