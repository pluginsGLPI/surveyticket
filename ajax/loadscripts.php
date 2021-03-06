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


include ('../../../inc/includes.php');

Html::header_nocache();
Session::checkLoginUser();
header("Content-Type: text/html; charset=UTF-8");
if (isset($_POST['action'])) {
   switch ($_POST['action']) {
      case "load" :
         if (strpos($_SERVER['HTTP_REFERER'], "ticket.form.php") !== false 
               || strpos($_SERVER['HTTP_REFERER'], "helpdesk.public.php") !== false
               || strpos($_SERVER['HTTP_REFERER'], "tracking.injector.php") !== false) {
          
            $rand = mt_rand();

            $params = array('root_doc' => $CFG_GLPI['root_doc']);
                        
            echo "<script type='text/javascript'>";
            echo "var surveyticket = $(document).surveyticket(".json_encode($params).");";
            echo "surveyticket.addelements();";
            echo "</script>";
         }
         break;
   }
}