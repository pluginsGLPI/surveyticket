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

class PluginSurveyticketSurvey extends CommonDBTM {

   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb = 0) {
      return _n('Survey', 'Surveys', $nb, 'surveyticket');
   }



   static function canCreate() {
      return PluginSurveyticketProfile::haveRight("config", 'w');
   }


   static function canView() {
      return PluginSurveyticketProfile::haveRight("config", 'r');
   }



   function defineTabs($options=array()){

      $ong = array();
      if ((isset($this->fields['id']))
              && ($this->fields['id'] > 0)) {

         $ong[1] = $this->getTypeName();
         $this->addStandardTab('PluginSurveyticketSurveyQuestion', $ong, $options);
         $this->addStandardTab('PluginSurveyticketTicketTemplate', $ong, $options);
      }
      return $ong;
   }



   function showForm($items_id, $options=array()) {

      if ($items_id!='') {
         $this->getFromDB($items_id);
      } else {
         $this->getEmpty();
      }

      $this->initForm($items_id, $options);
      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."&nbsp;:</td>";
      echo "<td>";
      echo '<input type="text" name="name" value="'.$this->fields["name"].'" size="50"/>';
      echo "</td>";
      echo "<td>".__('Active')."&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields['is_active']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comment')."&nbsp;:</td>";
      echo "<td colspan='3' class='middle'>";
      echo "<textarea cols='110' rows='3' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }







// ************************************************************************************ //
// *************************** Display survey in the ticket *************************** //
// ************************************************************************************ //

   /**
    * Clone of Ticket::showForm()
    * Change '$this' by '$ticket', 'self' by 'Ticket' and 'parent' by 'Ticket'
    */
   static  function getCentral($ID=0, $options=array()) {
      global $CFG_GLPI;

// * Added by plugin survey ticket
$ticket = new Ticket();
// * End of adding

      $default_values = Ticket::getDefaultValues();
      // Get default values from posted values on reload form

      if (!isset($options['template_preview'])) {
         if (isset($_POST)) {
            $values = $_POST;
         }
      }

      // Restore saved value or override with page parameter
      $saved = $ticket->restoreInput();

      foreach ($default_values as $name => $value) {
         if (!isset($values[$name])) {
            if (isset($saved[$name])) {
               $values[$name] = $saved[$name];
            } else {
               $values[$name] = $value;
            }
         }
      }
      // Default check
      if ($ID > 0) {
         $ticket->check($ID,'r');
      } else {
         // Create item
         $ticket->check(-1,'w',$values);
      }

      if (!$ID) {
         $ticket->userentities = array();
         if ($values["_users_id_requester"]) {
            //Get all the user's entities
            $all_entities = Profile_User::getUserEntities($values["_users_id_requester"], true,
                                                          true);
            //For each user's entity, check if the technician which creates the ticket have access to it
            foreach ($all_entities as $tmp => $ID_entity) {
               if (Session::haveAccessToEntity($ID_entity)) {
                  $ticket->userentities[] = $ID_entity;
               }
            }
         }
         $ticket->countentitiesforuser = count($ticket->userentities);
         if (($ticket->countentitiesforuser > 0)
             && !in_array($ticket->fields["entities_id"], $ticket->userentities)) {
            // If entity is not in the list of user's entities,
            // then use as default value the first value of the user's entites list
            $ticket->fields["entities_id"] = $ticket->userentities[0];
            // Pass to values
            $values['entities_id']       = $ticket->userentities[0];
         }
      }

      if ($values['type'] <= 0) {
         $values['type'] = Entity::getUsedConfig('tickettype', $values['entities_id'], '',
                                                 Ticket::INCIDENT_TYPE);
      }

      if (!isset($options['template_preview'])) {
         $options['template_preview'] = 0;
      }

      // Load ticket template if available :
      $tt = $ticket->getTicketTemplateToUse($options['template_preview'], $values['type'],
                                          $values['itilcategories_id'], $values['entities_id']);

      // Predefined fields from template : reset them
      if (isset($values['_predefined_fields'])) {
         $values['_predefined_fields']
                        = Toolbox::decodeArrayFromInput($values['_predefined_fields']);
      } else {
         $values['_predefined_fields'] = array();
      }

      // Store predefined fields to be able not to take into account on change template
      // Only manage predefined values on ticket creation
      $predefined_fields = array();
      if (!$ID) {

         if (isset($tt->predefined) && count($tt->predefined)) {
            foreach ($tt->predefined as $predeffield => $predefvalue) {

               if (isset($default_values[$predeffield])) {
                  // Is always default value : not set
                  // Set if already predefined field
                  // Set if ticket template change
                  if (($values[$predeffield] == $default_values[$predeffield])
                     || (isset($values['_predefined_fields'][$predeffield])
                         && ($values[$predeffield] == $values['_predefined_fields'][$predeffield]))
                     || (isset($values['_tickettemplates_id'])
                         && ($values['_tickettemplates_id'] != $tt->getID()))) {
                     // Load template data
                     $values[$predeffield]            = $predefvalue;
                     $ticket->fields[$predeffield]      = $predefvalue;
                     $predefined_fields[$predeffield] = $predefvalue;
                  }
               }
            }

         } else { // No template load : reset predefined values
            if (count($values['_predefined_fields'])) {
               foreach ($values['_predefined_fields'] as $predeffield => $predefvalue) {
                  if ($values[$predeffield] == $predefvalue) {
                     $values[$predeffield] = $default_values[$predeffield];
                  }
               }
            }
         }
      }

      // Put ticket template on $values for actors
      $values['_tickettemplate'] = $tt;

      $canupdate                 = Session::haveRight('update_ticket', '1');
      $canpriority               = Session::haveRight('update_priority', '1');
      $canstatus                 = $canupdate;

      if (in_array($ticket->fields['status'], $ticket->getClosedStatusArray())) {
         $canupdate = false;
      }

      $showuserlink              = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 1;
      }



      if (!$options['template_preview']) {
         $ticket->showTabs($options);
      } else {
         // Add all values to fields of tickets for template preview
         foreach ($values as $key => $val) {
            if (!isset($ticket->fields[$key])) {
               $ticket->fields[$key] = $val;
            }
         }
      }

      // In percent
      $colsize1 = '13';
      $colsize2 = '29';
      $colsize3 = '13';
      $colsize4 = '45';

      $canupdate_descr = $canupdate
                         || (($ticket->fields['status'] == Ticket::INCOMING)
                             && $ticket->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                             && ($ticket->numberOfFollowups() == 0)
                             && ($ticket->numberOfTasks() == 0));

      if (!$options['template_preview']) {
         echo "<form method='post' name='form_ticket' enctype='multipart/form-data' action='".
                $CFG_GLPI["root_doc"]."/front/ticket.form.php'>";
      }
      echo "<div class='spaced' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe' id='mainformtable'>";

      // Optional line
      $ismultientities = Session::isMultiEntitiesMode();
      echo "<tr class='headerRow'>";
      echo "<th colspan='4'>";

      if ($ID) {
         $text = sprintf(__('%1$s - %2$s'), $ticket->getTypeName(1),
                         sprintf(__('%1$s: %2$s'), __('ID'), $ID));
         if ($ismultientities) {
            $text = sprintf(__('%1$s (%2$s)'), $text,
                            Dropdown::getDropdownName('glpi_entities',
                                                      $ticket->fields['entities_id']));
         }
         echo $text;
      } else {
         if ($ismultientities) {
            printf(__('The ticket will be added in the entity %s'),
                   Dropdown::getDropdownName("glpi_entities", $ticket->fields['entities_id']));
         } else {
            _e('New ticket');
         }
      }
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>";
      echo $tt->getBeginHiddenFieldText('date');
      if (!$ID) {
         printf(__('%1$s%2$s'), __('Opening date'), $tt->getMandatoryMark('date'));
      } else {
         _e('Opening date');
      }
      echo $tt->getEndHiddenFieldText('date');
      echo "</th>";
      echo "<td width='$colsize2%'>";
      echo $tt->getBeginHiddenFieldValue('date');
      $date = $ticket->fields["date"];

      if ($canupdate) {
         Html::showDateTimeFormItem("date", $date, 1, false);
      } else {
         echo Html::convDateTime($date);
      }
      echo $tt->getEndHiddenFieldValue('date', $ticket);
      echo "</td>";
      // SLA
      echo "<th width='$colsize3%'>".$tt->getBeginHiddenFieldText('due_date');

      if (!$ID) {
         printf(__('%1$s%2$s'), __('Due date'), $tt->getMandatoryMark('due_date'));
      } else {
         _e('Due date');
      }
      echo $tt->getEndHiddenFieldText('due_date');
      echo "</th>";
      echo "<td width='$colsize4%' class='nopadding'>";
      if ($ID) {
         if ($ticket->fields["slas_id"] > 0) {
            echo "<table width='100%'><tr><td class='nopadding'>";
            echo Html::convDateTime($ticket->fields["due_date"]);
            echo "</td><td class='b'>".__('SLA')."</td>";
            echo "<td class='nopadding'>";
            echo Dropdown::getDropdownName("glpi_slas", $ticket->fields["slas_id"]);
            $commentsla = "";
            $slalevel   = new SlaLevel();
            if ($slalevel->getFromDB($ticket->fields['slalevels_id'])) {
               $commentsla .= '<span class="b spaced">'.
                                sprintf(__('%1$s: %2$s'), __('Escalation level'),
                                        $slalevel->getName()).'</span><br>';
            }

            $nextaction = new SlaLevel_Ticket();
            if ($nextaction->getFromDBForTicket($ticket->fields["id"])) {
               $commentsla .= '<span class="b spaced">'.
                                sprintf(__('Next escalation: %s'),
                                        Html::convDateTime($nextaction->fields['date'])).'</span>';
               if ($slalevel->getFromDB($nextaction->fields['slalevels_id'])) {
                  $commentsla .= '<span class="b spaced">'.
                                   sprintf(__('%1$s: %2$s'), __('Escalation level'),
                                           $slalevel->getName()).'</span>';
               }
            }
            $slaoptions = array();
            if (Session::haveRight('config', 'r')) {
               $slaoptions['link'] = Toolbox::getItemTypeFormURL('SLA').
                                          "?id=".$ticket->fields["slas_id"];
            }
            Html::showToolTip($commentsla,$slaoptions);
            if ($canupdate) {
               echo "&nbsp;<input type='submit' class='submit' name='sla_delete' value='".
                            _sx('button', 'Delete permanently')."'>";
            }
            echo "</td>";
            echo "</tr></table>";

         } else {
            echo "<table><tr><td class='nopadding'>";
            echo $tt->getBeginHiddenFieldValue('due_date');
            Html::showDateTimeFormItem("due_date", $ticket->fields["due_date"], 1, true, $canupdate);
            echo $tt->getEndHiddenFieldValue('due_date',$ticket);
            echo "</td>";
            if ($canupdate) {
               echo "<td>";
               echo $tt->getBeginHiddenFieldText('slas_id');
               echo "<span id='sla_action'>";
               echo "<a class='vsubmit' ".
                      Html::addConfirmationOnAction(array(__('The assignment of a SLA to a ticket causes the recalculation of the due date.'),
                       __("Escalations defined in the SLA will be triggered under this new date.")),
                                                    "cleanhide('sla_action');cleandisplay('sla_choice');").
                     ">".__('Assign a SLA').'</a>';
               echo "</span>";
               echo "<span id='sla_choice' style='display:none'>";
               echo "<span  class='b'>".__('SLA')."</span>&nbsp;";
               Sla::dropdown(array('entity' => $ticket->fields["entities_id"],
                                   'value'  => $ticket->fields["slas_id"]));
               echo "</span>";
               echo $tt->getEndHiddenFieldText('slas_id');
               echo "</td>";
            }
            echo "</tr></table>";
         }

      } else { // New Ticket
         echo "<table><tr><td class='nopadding'>";
         if ($ticket->fields["due_date"] == 'NULL') {
            $ticket->fields["due_date"]='';
         }
         echo $tt->getBeginHiddenFieldValue('due_date');
         Html::showDateTimeFormItem("due_date", $ticket->fields["due_date"], 1, false, $canupdate);
         echo $tt->getEndHiddenFieldValue('due_date',$ticket);
         echo "</td>";
         if ($canupdate) {
            echo "<td class='nopadding b'>".$tt->getBeginHiddenFieldText('slas_id');
            printf(__('%1$s%2$s'), __('SLA'), $tt->getMandatoryMark('slas_id'));
            echo $tt->getEndHiddenFieldText('slas_id')."</td>";
            echo "<td class='nopadding'>".$tt->getBeginHiddenFieldValue('slas_id');
            Sla::dropdown(array('entity' => $ticket->fields["entities_id"],
                              'value'  => $ticket->fields["slas_id"]));
            echo $tt->getEndHiddenFieldValue('slas_id',$ticket);
            echo "</td>";
         }
         echo "</tr></table>";
      }
      echo "</td></tr>";

      if ($ID) {
         echo "<tr class='tab_bg_1'>";
         echo "<th width='$colsize1%'>".__('By')."</th>";
         echo "<td width='$colsize2%'>";
         if ($canupdate) {
            User::dropdown(array('name'   => 'users_id_recipient',
                                 'value'  => $ticket->fields["users_id_recipient"],
                                 'entity' => $ticket->fields["entities_id"],
                                 'right'  => 'all'));
         } else {
            echo getUserName($ticket->fields["users_id_recipient"], $showuserlink);
         }

         echo "</td>";
         echo "<th width='$colsize3%'>".__('Last update')."</th>";
         echo "<td width='$colsize4%'>";
         if ($ticket->fields['users_id_lastupdater'] > 0) {
            //TRANS: %1$s is the update date, %2$s is the last updater name
            printf(__('%1$s by %2$s'), Html::convDateTime($ticket->fields["date_mod"]),
                   getUserName($ticket->fields["users_id_lastupdater"], $showuserlink));
         }
         echo "</td>";
         echo "</tr>";
      }

      if ($ID
          && (in_array($ticket->fields["status"], $ticket->getSolvedStatusArray())
              || in_array($ticket->fields["status"], $ticket->getClosedStatusArray()))) {

         echo "<tr class='tab_bg_1'>";
         echo "<th width='$colsize1%'>".__('Resolution date')."</th>";
         echo "<td width='$colsize2%'>";
         Html::showDateTimeFormItem("solvedate", $ticket->fields["solvedate"], 1, false,
                                    $canupdate);
         echo "</td>";
         if (in_array($ticket->fields["status"], $ticket->getClosedStatusArray())) {
            echo "<th width='$colsize3%'>".__('Close date')."</th>";
            echo "<td width='$colsize4%'>";
            Html::showDateTimeFormItem("closedate", $ticket->fields["closedate"], 1, false,
                                       $canupdate);
            echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
         echo "</tr>";
      }

      if ($ID) {
         echo "</table>";
         echo "<table  class='tab_cadre_fixe' id='mainformtable2'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".sprintf(__('%1$s%2$s'), __('Type'),
                                             $tt->getMandatoryMark('type'))."</th>";
      echo "<td width='$colsize2%'>";
      // Permit to set type when creating ticket without update right
      if ($canupdate || !$ID) {
         $opt = array('value' => $ticket->fields["type"]);
         /// Auto submit to load template
         if (!$ID) {
            $opt['on_change'] = 'submit()';
         }
         $rand = Ticket::dropdownType('type', $opt);
         if ($ID) {
            $params = array('type'            => '__VALUE__',
                            'entity_restrict' => $ticket->fields['entities_id'],
                            'value'           => $ticket->fields['itilcategories_id'],
                            'currenttype'     => $ticket->fields['type']);

            Ajax::updateItemOnSelectEvent("dropdown_type$rand", "show_category_by_type",
                                          $CFG_GLPI["root_doc"]."/ajax/dropdownTicketCategories.php",
                                          $params);
         }
      } else {
         echo Ticket::getTicketTypeName($ticket->fields["type"]);
      }
      echo "</td>";
      echo "<th width='$colsize3%'>".sprintf(__('%1$s%2$s'), __('Category'),
                                             $tt->getMandatoryMark('itilcategories_id'))."</th>";
      echo "<td width='$colsize4%'>";
      // Permit to set category when creating ticket without update right
      if ($canupdate
          || !$ID
          || $canupdate_descr) {

         $opt = array('value'  => $ticket->fields["itilcategories_id"],
                      'entity' => $ticket->fields["entities_id"]);
         if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
            $opt['condition'] = "`is_helpdeskvisible`='1' AND ";
         } else {
            $opt['condition'] = '';
         }
         /// Auto submit to load template
         if (!$ID) {
            $opt['on_change'] = 'submit()';
         }
         /// if category mandatory, no empty choice
         /// no empty choice is default value set on ticket creation, else yes
         if (($ID || $values['itilcategories_id'])
             && $tt->isMandatoryField("itilcategories_id")
             && ($ticket->fields["itilcategories_id"] > 0)) {
            $opt['display_emptychoice'] = false;
         }

         switch ($ticket->fields["type"]) {
            case Ticket::INCIDENT_TYPE :
               $opt['condition'] .= "`is_incident`='1'";
               break;

            case Ticket::DEMAND_TYPE :
               $opt['condition'] .= "`is_request`='1'";
               break;

            default :
               break;
         }
         echo "<span id='show_category_by_type'>";
         ITILCategory::dropdown($opt);
         echo "</span>";
      } else {
         echo Dropdown::getDropdownName("glpi_itilcategories", $ticket->fields["itilcategories_id"]);
      }
      echo "</td>";
      echo "</tr>";

      if (!$ID) {
         echo "</table>";
         $ticket->showActorsPartForm($ID,$values);
         echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".$tt->getBeginHiddenFieldText('status');
      printf(__('%1$s%2$s'), __('Status'), $tt->getMandatoryMark('status'));
      echo $tt->getEndHiddenFieldText('status')."</th>";
      echo "<td width='$colsize2%'>";
      echo $tt->getBeginHiddenFieldValue('status');
      if ($canstatus) {
         Ticket::dropdownStatus(array('value' => $ticket->fields["status"],
                                    'showtype' => 'allowed'));
      } else {
         echo Ticket::getStatus($ticket->fields["status"]);
      }
      echo $tt->getEndHiddenFieldValue('status',$ticket);

      echo "</td>";
      echo "<th width='$colsize3%'>".$tt->getBeginHiddenFieldText('requesttypes_id');
      printf(__('%1$s%2$s'), __('Request source'), $tt->getMandatoryMark('requesttypes_id'));
      echo $tt->getEndHiddenFieldText('requesttypes_id')."</th>";
      echo "<td width='$colsize4%'>";
      echo $tt->getBeginHiddenFieldValue('requesttypes_id');
      if ($canupdate) {
         RequestType::dropdown(array('value' => $ticket->fields["requesttypes_id"]));
      } else {
         echo Dropdown::getDropdownName('glpi_requesttypes', $ticket->fields["requesttypes_id"]);
      }
      echo $tt->getEndHiddenFieldValue('requesttypes_id',$ticket);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$tt->getBeginHiddenFieldText('urgency');
      printf(__('%1$s%2$s'), __('Urgency'), $tt->getMandatoryMark('urgency'));
      echo $tt->getEndHiddenFieldText('urgency')."</th>";
      echo "<td>";

      if (($canupdate && $canpriority)
          || !$ID
          || $canupdate_descr) {
         // Only change during creation OR when allowed to change priority OR when user is the creator
         echo $tt->getBeginHiddenFieldValue('urgency');
         $idurgency = Ticket::dropdownUrgency(array('value' => $ticket->fields["urgency"]));
         echo $tt->getEndHiddenFieldValue('urgency', $ticket);

      } else {
         $idurgency = "value_urgency".mt_rand();
         echo "<input id='$idurgency' type='hidden' name='urgency' value='".
                $ticket->fields["urgency"]."'>";
         echo Ticket::getUrgencyName($ticket->fields["urgency"]);
      }
      echo "</td>";
      // Display validation state
      echo "<th>";
      if (!$ID) {
         echo $tt->getBeginHiddenFieldText('_add_validation');
         printf(__('%1$s%2$s'), __('Approval request'), $tt->getMandatoryMark('_add_validation'));
         echo $tt->getEndHiddenFieldText('_add_validation');
      } else {
         echo $tt->getBeginHiddenFieldText('global_validation');
         _e('Approval');
         echo $tt->getEndHiddenFieldText('global_validation');
      }
      echo "</th>";
      echo "<td>";
      if (!$ID) {
         echo $tt->getBeginHiddenFieldValue('_add_validation');
         $validation_right = '';
         if (($values['type'] == Ticket::INCIDENT_TYPE)
             && Session::haveRight('create_incident_validation', 1)) {
            $validation_right = 'validate_incident';
         }
         if (($values['type'] == Ticket::DEMAND_TYPE)
             && Session::haveRight('create_request_validation', 1)) {
            $validation_right = 'validate_request';
         }

         if (!empty($validation_right)) {
            User::dropdown(array('name'   => "_add_validation",
                                 'entity' => $ticket->fields['entities_id'],
                                 'right'  => $validation_right,
                                 'value'  => $values['_add_validation']));
         }
         echo $tt->getEndHiddenFieldValue('_add_validation',$ticket);
         if ($tt->isPredefinedField('global_validation')) {
            echo "<input type='hidden' name='global_validation' value='".$tt->predefined['global_validation']."'>";
         }
      } else {
         echo $tt->getBeginHiddenFieldValue('global_validation');
         if ($canupdate) {
            TicketValidation::dropdownStatus('global_validation',
                                             array('global' => true,
                                                   'value'  => $ticket->fields['global_validation']));
         } else {
            echo TicketValidation::getStatus($ticket->fields['global_validation']);
         }
         echo $tt->getEndHiddenFieldValue('global_validation',$ticket);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$tt->getBeginHiddenFieldText('impact');
      printf(__('%1$s%2$s'), __('Impact'), $tt->getMandatoryMark('impact'));
      echo $tt->getEndHiddenFieldText('impact')."</th>";
      echo "<td>";
      echo $tt->getBeginHiddenFieldValue('impact');

      if ($canupdate) {
         $idimpact = Ticket::dropdownImpact(array('value' => $ticket->fields["impact"]));
      } else {
         $idimpact = "value_impact".mt_rand();
         echo "<input id='$idimpact' type='hidden' name='impact' value='".$ticket->fields["impact"]."'>";
         echo Ticket::getImpactName($ticket->fields["impact"]);
      }
      echo $tt->getEndHiddenFieldValue('impact',$ticket);
      echo "</td>";

      echo "<th rowspan='2'>".$tt->getBeginHiddenFieldText('itemtype');
      printf(__('%1$s%2$s'), __('Associated element'), $tt->getMandatoryMark('itemtype'));
      if ($ID && $canupdate) {
         echo "&nbsp;<img title='".__s('Update')."' alt='".__s('Update')."'
                      onClick=\"Ext.get('tickethardwareselection$ID').setDisplayed('block')\"
                      class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/showselect.png'>";
      }
      echo $tt->getEndHiddenFieldText('itemtype');
      echo "</th>";
      echo "<td rowspan='2'>";
      echo $tt->getBeginHiddenFieldValue('itemtype');

      // Select hardware on creation or if have update right
      if ($canupdate
          || !$ID
          || $canupdate_descr) {

          if ($ID) {
            if ($ticket->fields['itemtype']
                && ($item = getItemForItemtype($ticket->fields['itemtype']))
                && $ticket->fields["items_id"]) {

                if ($item->can($ticket->fields["items_id"],'r')) {
                  printf(__('%1$s - %2$s'), $item->getTypeName(),
                         $item->getLink(array('comments' => true)));
               } else {
                  printf(__('%1$s - %2$s'),  $item->getTypeName(), $item->getNameID());
               }
            }
         }
         $dev_user_id  = 0;
         $dev_itemtype = $ticket->fields["itemtype"];
         $dev_items_id = $ticket->fields["items_id"];
         if (!$ID) {
            $dev_user_id  = $values['_users_id_requester'];
            $dev_itemtype = $values["itemtype"];
            $dev_items_id = $values["items_id"];
         } else if (isset($ticket->users[CommonITILActor::REQUESTER])
                    && (count($ticket->users[CommonITILActor::REQUESTER]) == 1)) {
            foreach ($ticket->users[CommonITILActor::REQUESTER] as $user_id_single) {
               $dev_user_id = $user_id_single['users_id'];
            }
         }
         if ($ID) {
            echo "<div id='tickethardwareselection$ID' style='display:none'>";
         }
         if ($dev_user_id > 0) {
            Ticket::dropdownMyDevices($dev_user_id, $ticket->fields["entities_id"],
                                    $dev_itemtype, $dev_items_id);
         }
         Ticket::dropdownAllDevices("itemtype", $dev_itemtype, $dev_items_id,
                                  1, $dev_user_id, $ticket->fields["entities_id"]);
         if ($ID) {
            echo "</div>";
         }

         echo "<span id='item_ticket_selection_information'></span>";

      } else {
         if ($ID
             && $ticket->fields['itemtype']
             && ($item = getItemForItemtype($ticket->fields['itemtype']))) {
            $item->getFromDB($ticket->fields['items_id']);
            printf(__('%1$s - %2$s'), $item->getTypeName(), $item->getNameID());
         } else {
            _e('General');
         }
      }
      echo $tt->getEndHiddenFieldValue('itemtype',$ticket);

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".sprintf(__('%1$s%2$s'), __('Priority'), $tt->getMandatoryMark('priority'))."</th>";
      echo "<td>";
      $idajax = 'change_priority_' . mt_rand();

      if ($canupdate
          && $canpriority
          && !$tt->isHiddenField('priority')) {
         $idpriority = Ticket::dropdownPriority(array('value'     => $ticket->fields["priority"],
                                                      'withmajor' => true));
         echo "&nbsp;<span id='$idajax' style='display:none'></span>";

      } else {
         $idpriority = 0;
         echo "<span id='$idajax'>".Ticket::getPriorityName($ticket->fields["priority"])."</span>";
      }

      if ($canupdate || $canupdate_descr) {
         $params = array('urgency'  => '__VALUE0__',
                         'impact'   => '__VALUE1__',
                         'priority' => $idpriority);
         Ajax::updateItemOnSelectEvent(array($idurgency, $idimpact), $idajax,
                                       $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
      }
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      // Need comment right to add a followup with the actiontime
      if (!$ID
          && Session::haveRight("global_add_followups","1")) {
         echo "<th>".$tt->getBeginHiddenFieldText('actiontime');
         printf(__('%1$s%2$s'), __('Total duration'), $tt->getMandatoryMark('actiontime'));
         echo $tt->getEndHiddenFieldText('actiontime')."</th>";
         echo "<td>";
         echo $tt->getBeginHiddenFieldValue('actiontime');
         Dropdown::showTimeStamp('actiontime', array('value' => $values['actiontime'],
                                                     'addfirstminutes' => true));
         echo $tt->getEndHiddenFieldValue('actiontime',$ticket);
         echo "</td>";
      } else {
         echo "<th></th><td></td>";
      }
      echo "<th>".$tt->getBeginHiddenFieldText('locations_id');
      printf(__('%1$s%2$s'), __('Location'), $tt->getMandatoryMark('locations_id'));
      echo $tt->getEndHiddenFieldText('locations_id')."</th>";
      echo "<td>";
      echo $tt->getBeginHiddenFieldValue('locations_id');
      if ($canupdate) {
         Location::dropdown(array('value'  => $ticket->fields['locations_id'],
                                  'entity' => $ticket->fields['entities_id']));
      } else {
         echo Dropdown::getDropdownName('glpi_locations', $ticket->fields["locations_id"]);
      }
      echo $tt->getEndHiddenFieldValue('locations_id', $ticket);
      echo "</td></tr>";

      echo "</table>";
      if ($ID) {
         $values['canupdate'] = $canupdate;
         $ticket->showActorsPartForm($ID, $values);
      }

      $view_linked_tickets = ($ID || $canupdate);

      echo "<table class='tab_cadre_fixe' id='mainformtable4'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".$tt->getBeginHiddenFieldText('name');
      printf(__('%1$s%2$s'), __('Title'), $tt->getMandatoryMark('name'));
      echo $tt->getEndHiddenFieldText('name')."</th>";
      echo "<td width='".(100-$colsize1)."%' colspan='3'>";
      if (!$ID || $canupdate_descr) {
         echo $tt->getBeginHiddenFieldValue('name');

         $rand = mt_rand();
         echo "<script type='text/javascript' >\n";
         echo "function showName$rand() {\n";
         echo "Ext.get('name$rand').setDisplayed('none');";
         $params = array('maxlength' => 250,
                         'size'      => 90,
                         'name'      => 'name',
                         'data'      => rawurlencode($ticket->fields["name"]));
         Ajax::updateItemJsCode("viewname$rand", $CFG_GLPI["root_doc"]."/ajax/inputtext.php",
                                $params);
         echo "}";
         echo "</script>\n";
         echo "<div id='name$rand' class='tracking left' onClick='showName$rand()'>\n";
         if (empty($ticket->fields["name"])) {
            _e('Without title');
         } else {
            echo $ticket->fields["name"];
         }
         echo "</div>\n";

         echo "<div id='viewname$rand'>\n";
         echo "</div>\n";
         if (!$ID) {
            echo "<script type='text/javascript' >\n
            showName$rand();
            </script>";
         }
         echo $tt->getEndHiddenFieldValue('name', $ticket);

      } else {
         if (empty($ticket->fields["name"])) {
            _e('Without title');
         } else {
            echo $ticket->fields["name"];
         }
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".$tt->getBeginHiddenFieldText('content');
      printf(__('%1$s%2$s'), __('Description'), $tt->getMandatoryMark('content'));
      echo $tt->getEndHiddenFieldText('content')."</th>";
      echo "<td width='".(100-$colsize1)."%' colspan='3'>";

// * Added by plugin surveyticket
$psTicketTemplate = new PluginSurveyticketTicketTemplate();
$psSurvey = new PluginSurveyticketSurvey();
$plugin_surveyticket_surveys_id = 0;
$a_tickettemplates = current($psTicketTemplate->find("`tickettemplates_id`='".$tt->fields['id']."'
                                                      AND `type`='".$values['type']."'
                                                      AND `is_central`='1'"));

if (isset($a_tickettemplates['plugin_surveyticket_surveys_id'])) {
   $psSurvey = new PluginSurveyticketSurvey();
   $psSurvey->getFromDB($a_tickettemplates['plugin_surveyticket_surveys_id']);
   if ($psSurvey->fields['is_active'] == 1) {
      $plugin_surveyticket_surveys_id = $a_tickettemplates['plugin_surveyticket_surveys_id'];
      $psSurvey = new PluginSurveyticketSurvey();
      $psSurvey->startSurvey($plugin_surveyticket_surveys_id);
   }
} else {
// End of adding by plugin

      if (!$ID || $canupdate_descr) { // Admin =oui on autorise la modification de la description
         echo $tt->getBeginHiddenFieldValue('content');

         $rand = mt_rand();
         echo "<script type='text/javascript' >\n";
         echo "function showDesc$rand() {\n";
         echo "Ext.get('desc$rand').setDisplayed('none');";
         $params = array('rows'  => 6,
                         'cols'  => 90,
                         'name'  => 'content',
                         'data'  => rawurlencode($ticket->fields["content"]));
         Ajax::updateItemJsCode("viewdesc$rand", $CFG_GLPI["root_doc"]."/ajax/textarea.php",
                                $params);
         echo "}";
         echo "</script>\n";
         echo "<div id='desc$rand' class='tracking' onClick='showDesc$rand()'>\n";
         if (!empty($ticket->fields["content"])) {
            echo nl2br($ticket->fields["content"]);
         } else {
            _e('Empty description');
         }
         echo "</div>\n";

         echo "<div id='viewdesc$rand'></div>\n";
         if (!$ID) {
            echo "<script type='text/javascript' >\n
            showDesc$rand();
            </script>";
         }
         echo $tt->getEndHiddenFieldValue('content', $ticket);

      } else {
         echo nl2br($ticket->fields["content"]);
      }
// * Added by plugin surveyticket
}
// End of adding by plugin
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      // Permit to add doc when creating a ticket
      if (!$ID) {
         echo "<th width='$colsize1%'>".sprintf(__('File (%s)'), Document::getMaxUploadSize());
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/aide.png' class='pointer' alt=\"".
               __s('Help')."\" onclick=\"window.open('".$CFG_GLPI["root_doc"].
               "/front/documenttype.list.php','Help','scrollbars=1,resizable=1,width=1000,".
               "height=800')\">";
         echo "&nbsp;";
         Ticket::showDocumentAddButton();

         echo "</th>";
         echo "<td width='$colsize2%'>";
         echo "<div id='uploadfiles'><input type='file' name='filename[]' size='20'></div></td>";

      } else {
         echo "<th colspan='2'>";
         $docnb = Document_Item::countForItem($ticket);
         echo "<a href=\"".$ticket->getLinkURL()."&amp;forcetab=Document_Item$1\">";
         //TRANS: %d is the document number
         echo sprintf(_n('%d associated document', '%d associated documents', $docnb), $docnb);
         echo "</a></th>";
      }

      if ($view_linked_tickets) {
         echo "<th width='$colsize3%'>". _n('Linked ticket', 'Linked tickets', 2);
         $rand_linked_ticket = mt_rand();
         if ($canupdate) {
            echo "&nbsp;";
            echo "<img onClick=\"Ext.get('linkedticket$rand_linked_ticket').setDisplayed('block')\"
                   title=\"".__s('Add')."\" alt=\"".__s('Add')."\"
                   class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";
         }
         echo '</th>';
         echo "<td width='$colsize4%'>";
         if ($canupdate) {
            echo "<div style='display:none' id='linkedticket$rand_linked_ticket'>";
            Ticket_Ticket::dropdownLinks('_link[link]',
                                         (isset($values["_link"])?$values["_link"]['link']:''));
            printf(__('%1$s: %2$s'), __('Ticket'), __('ID'));
            echo "<input type='hidden' name='_link[tickets_id_1]' value='$ID'>\n";
            echo "<input type='text' name='_link[tickets_id_2]'
                   value='".(isset($values["_link"])?$values["_link"]['tickets_id_2']:'')."'
                   size='10'>\n";
            echo "&nbsp;";
            echo "</div>";

            if (isset($values["_link"])
                && !empty($values["_link"]['tickets_id_2'])) {
               echo "<script language='javascript'>Ext.get('linkedticket$rand_linked_ticket').
                      setDisplayed('block');</script>";
            }
         }

         Ticket_Ticket::displayLinkedTicketsTo($ID);
         echo "</td>";
      } else {
         echo "<td></td>";
      }

      echo "</tr>";

      if ((!$ID
           || $canupdate
           || $canupdate_descr
           || Session::haveRight("assign_ticket","1")
           || Session::haveRight("steal_ticket","1"))
          && !$options['template_preview']) {

         echo "<tr class='tab_bg_1'>";

         if ($ID) {
            if (Session::haveRight('delete_ticket',1)) {
               echo "<td class='tab_bg_2 center' colspan='2'>";
               if ($ticket->fields["is_deleted"] == 1) {
                  echo "<input type='submit' class='submit' name='restore' value='".
                         _sx('button', 'Restore')."'></td>";
               } else {
                  echo "<input type='submit' class='submit' name='update' value='".
                         _sx('button', 'Save')."'></td>";
               }
               echo "<td class='tab_bg_2 center' colspan='2'>";
               if ($ticket->fields["is_deleted"] == 1) {
                  echo "<input type='submit' class='submit' name='purge' value='".
                         _sx('button', 'Delete permanently')."' ".
                         Html::addConfirmationOnAction(__('Confirm the final deletion?')).">";
               } else {
                  echo "<input type='submit' class='submit' name='delete' value='".
                         _sx('button', 'Put in dustbin')."'></td>";
               }

            } else {
               echo "<td class='tab_bg_2 center' colspan='4'>";
               echo "<input type='submit' class='submit' name='update' value='".
                      _sx('button', 'Save')."'>";
            }
            echo "<input type='hidden' name='_read_date_mod' value='".$ticket->getField('date_mod')."'>";

         } else {
            echo "<td class='tab_bg_2 center' colspan='4'>";
            echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
            if ($tt->isField('id') && ($tt->fields['id'] > 0)) {
               echo "<input type='hidden' name='_tickettemplates_id' value='".$tt->fields['id']."'>";
               echo "<input type='hidden' name='_predefined_fields'
                      value=\"".Toolbox::prepareArrayForInput($predefined_fields)."\">";
            }
         }
      }

      echo "</table>";
      echo "<input type='hidden' name='id' value='$ID'>";

      echo "</div>";

      if (!$options['template_preview']) {
         Html::closeForm();
         $ticket->addDivForTabs();
      }

      return true;
   }



   /**
    * clone of function Ticket::showFormHelpdesk()
    */
   static function getHelpdesk($ID=0, $ticket_template=false) {
      global $DB, $CFG_GLPI;

// * Added by plugin survey ticket
$ticket = new Ticket();
// * End of adding

      if (!Session::haveRight("create_ticket","1")) {
         return false;
      }

      if (!$ticket_template
            && (Session::haveRight('validate_incident',1)
            || Session::haveRight('validate_request',1))) {
         $opt                  = array();
         $opt['reset']         = 'reset';
         $opt['field'][0]      = 55; // validation status
         $opt['searchtype'][0] = 'equals';
         $opt['contains'][0]   = 'waiting';
         $opt['link'][0]       = 'AND';

         $opt['field'][1]      = 59; // validation aprobator
         $opt['searchtype'][1] = 'equals';
         $opt['contains'][1]   = Session::getLoginUserID();
         $opt['link'][1]       = 'AND';

         $url_validate = $CFG_GLPI["root_doc"]."/front/ticket.php?".Toolbox::append_params($opt,
                                                                                           '&amp;');

         if (TicketValidation::getNumberTicketsToValidate(Session::getLoginUserID()) > 0) {
            echo "<a href='$url_validate' title=\"".__s('Ticket waiting for your approval')."\"
                   alt=\"".__s('Ticket waiting for your approval')."\">".
                   __('Tickets awaiting approval')."</a><br><br>";
         }
      }

      $query = "SELECT `realname`, `firstname`, `name`
                FROM `glpi_users`
                WHERE `id` = '$ID'";
      $result = $DB->query($query);


      $email  = UserEmail::getDefaultForUser($ID);
      

      // Set default values...
      $default_values = array('_users_id_requester_notif'
                                                    => array('use_notification'
                                                              => (($email == "")?0:1)),
                              'nodelegate'          => 1,
                              '_users_id_requester' => 0,
                              'name'                => '',
                              'content'             => '',
                              'itilcategories_id'   => 0,
                              'locations_id'        => 0,
                              'urgency'             => 3,
                              'itemtype'            => '',
                              'items_id'            => 0,
                              'entities_id'         => $_SESSION['glpiactive_entity'],
                              'plan'                => array(),
                              'global_validation'   => 'none',
                              'due_date'            => 'NULL',
                              'slas_id'             => 0,
                              '_add_validation'     => 0,
                              'type'                => Entity::getUsedConfig('tickettype',
                                                                             $_SESSION['glpiactive_entity'],
                                                                             '', Ticket::INCIDENT_TYPE),
                              '_right'              => "id");

      // Get default values from posted values on reload form
      if (!$ticket_template) {
         if (isset($_POST)) {
            $values = $_POST;
         }
      }

      // Restore saved value or override with page parameter
      $saved = $ticket->restoreInput();
      foreach ($default_values as $name => $value) {
         if (!isset($values[$name])) {
            if (isset($saved[$name])) {
               $values[$name] = $saved[$name];
            } else {
               $values[$name] = $value;
            }
         }
      }

      if (!$ticket_template) {
         echo "<form method='post' name='helpdeskform' action='".
               $CFG_GLPI["root_doc"]."/front/tracking.injector.php' enctype='multipart/form-data'>";
      }


      $delegating = User::getDelegateGroupsForUser($values['entities_id']);

      if (count($delegating)) {
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>".__('This ticket concerns me')." ";

         $rand   = Dropdown::showYesNo("nodelegate", $values['nodelegate']);

         $params = array ('nodelegate' => '__VALUE__',
                          'rand'       => $rand,
                          'right'      => "delegate",
                          '_users_id_requester'
                                       => $values['_users_id_requester'],
                          '_users_id_requester_notif'
                                       => $values['_users_id_requester_notif'],
                          'use_notification'
                                       => $values['_users_id_requester_notif']['use_notification'],
                          'entity_restrict'
                                       => $_SESSION["glpiactive_entity"]);

         Ajax::UpdateItemOnSelectEvent("dropdown_nodelegate".$rand, "show_result".$rand,
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownDelegationUsers.php",
                                       $params);

         if ($CFG_GLPI['use_check_pref'] && $values['nodelegate']) {
            echo "</th><th>".__('Check your personnal information');
         }

         echo "</th></tr>";
         echo "<tr class='tab_bg_1'><td colspan='2' class='center'>";
         echo "<div id='show_result$rand'>";

         $self = new Ticket();
         if ($values["_users_id_requester"] == 0) {
            $values['_users_id_requester'] = Session::getLoginUserID();
         } else {
            $values['_right'] = "delegate";
         }

         $self->showActorAddFormOnCreate(CommonITILActor::REQUESTER, $values);
         echo "</div>";
         if ($CFG_GLPI['use_check_pref'] && $values['nodelegate']) {
            echo "</td><td class='center'>";
            User::showPersonalInformation(Session::getLoginUserID());
         }
         echo "</td></tr>";

         echo "</table></div>";
         echo "<input type='hidden' name='_users_id_recipient' value='".Session::getLoginUserID()."'>";

      } else {
         // User as requester
         $values['_users_id_requester'] = Session::getLoginUserID();
         if ($CFG_GLPI['use_check_pref']) {
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr><th>".__('Check your personnal information')."</th></tr>";
            echo "<tr class='tab_bg_1'><td class='center'>";
            User::showPersonalInformation(Session::getLoginUserID());
            echo "</td></tr>";
            echo "</table></div>";
         }
      }


      echo "<input type='hidden' name='_from_helpdesk' value='1'>";
      echo "<input type='hidden' name='requesttypes_id' value='".RequestType::getDefault('helpdesk').
           "'>";


      // Load ticket template if available :
      $tt = $ticket->getTicketTemplateToUse($ticket_template, $values['type'],
                                          $values['itilcategories_id'],
                                          $_SESSION["glpiactive_entity"]);

      // Predefined fields from template : reset them
      if (isset($values['_predefined_fields'])) {
         $values['_predefined_fields']
                        = Toolbox::decodeArrayFromInput($values['_predefined_fields']);
      } else {
         $values['_predefined_fields'] = array();
      }

      // Store predefined fields to be able not to take into account on change template
      $predefined_fields = array();

      if (isset($tt->predefined) && count($tt->predefined)) {
         foreach ($tt->predefined as $predeffield => $predefvalue) {
            if (isset($values[$predeffield]) && isset($default_values[$predeffield])) {
               // Is always default value : not set
               // Set if already predefined field
               // Set if ticket template change
               if (($values[$predeffield] == $default_values[$predeffield])
                   || (isset($values['_predefined_fields'][$predeffield])
                       && ($values[$predeffield] == $values['_predefined_fields'][$predeffield]))
                   || (isset($values['_tickettemplates_id'])
                       && ($values['_tickettemplates_id'] != $tt->getID()))) {
                  $values[$predeffield]            = $predefvalue;
                  $predefined_fields[$predeffield] = $predefvalue;
               }
            } else { // Not defined options set as hidden field
               echo "<input type='hidden' name='$predeffield' value='$predefvalue'>";
            }
         }

      } else { // No template load : reset predefined values
         if (count($values['_predefined_fields'])) {
            foreach ($values['_predefined_fields'] as $predeffield => $predefvalue) {
               if ($values[$predeffield] == $predefvalue) {
                  $values[$predeffield] = $default_values[$predeffield];
               }
            }
         }
      }

      if (($CFG_GLPI['urgency_mask'] == (1<<3))
          || $tt->isHiddenField('urgency')) {
         // Dont show dropdown if only 1 value enabled or field is hidden
         echo "<input type='hidden' name='urgency' value='".$values['urgency']."'>";
      }

      // Display predefined fields if hidden
      if ($tt->isHiddenField('itemtype')) {
         echo "<input type='hidden' name='itemtype' value='".$values['itemtype']."'>";
         echo "<input type='hidden' name='items_id' value='".$values['items_id']."'>";
      }
      if ($tt->isHiddenField('locations_id')) {
         echo "<input type='hidden' name='locations_id' value='".$values['locations_id']."'>";
      }
      echo "<input type='hidden' name='entities_id' value='".$_SESSION["glpiactive_entity"]."'>";
      echo "<div class='center'><table class='tab_cadre_fixe'>";

      echo "<tr><th>".__('Describe the incident or request')."</th><th>";
      if (Session::isMultiEntitiesMode()) {
         echo "(".Dropdown::getDropdownName("glpi_entities", $_SESSION["glpiactive_entity"]).")";
      }
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Type'), $tt->getMandatoryMark('type'))."</td>";
      echo "<td>";
      Ticket::dropdownType('type', array('value'     => $values['type'],
                                       'on_change' => 'submit()'));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Category'),
                          $tt->getMandatoryMark('itilcategories_id'))."</td>";
      echo "<td>";

      $condition = "`is_helpdeskvisible`='1'";
      switch ($values['type']) {
         case Ticket::DEMAND_TYPE :
            $condition .= " AND `is_request`='1'";
            break;

         default: // Ticket::INCIDENT_TYPE :
            $condition .= " AND `is_incident`='1'";
      }
      $opt = array('value'     => $values['itilcategories_id'],
                   'condition' => $condition,
                   'on_change' => 'submit()');

      if ($values['itilcategories_id'] && $tt->isMandatoryField("itilcategories_id")) {
         $opt['display_emptychoice'] = false;
      }

      ITILCategory::dropdown($opt);
      echo "</td></tr>";


      if ($CFG_GLPI['urgency_mask'] != (1<<3)) {
         if (!$tt->isHiddenField('urgency')) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".sprintf(__('%1$s%2$s'), __('Urgency'), $tt->getMandatoryMark('urgency')).
                 "</td>";
            echo "<td>";
            Ticket::dropdownUrgency(array('value' => $values["urgency"]));
            echo "</td></tr>";
         }
      }

      if (empty($delegating)
          && NotificationTargetTicket::isAuthorMailingActivatedForHelpdesk()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Inform me about the actions taken')."</td>";
         echo "<td>";
         if ($values["_users_id_requester"] == 0) {
            $values['_users_id_requester'] = Session::getLoginUserID();
         }
         $_POST['value']            = $values['_users_id_requester'];
         $_POST['field']            = '_users_id_requester_notif';
         $_POST['use_notification'] = $values['_users_id_requester_notif']['use_notification'];
         include (GLPI_ROOT."/ajax/uemailUpdate.php");

         echo "</td></tr>";
      }

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] != 0) {
         if (!$tt->isHiddenField('itemtype')) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".sprintf(__('%1$s%2$s'), __('Hardware type'),
                                $tt->getMandatoryMark('itemtype'))."</td>";
            echo "<td>";
            Ticket::dropdownMyDevices($values['_users_id_requester'], $_SESSION["glpiactive_entity"],
                                    $values['itemtype'], $values['items_id']);
            Ticket::dropdownAllDevices("itemtype", $values['itemtype'], $values['items_id'], 0,
                                     $values['_users_id_requester'],
                                     $_SESSION["glpiactive_entity"]);
            echo "<span id='item_ticket_selection_information'></span>";

            echo "</td></tr>";
         }
      }

      if (!$tt->isHiddenField('locations_id')) {
         echo "<tr class='tab_bg_1'><td>";
         printf(__('%1$s%2$s'), __('Location'), $tt->getMandatoryMark('locations_id'));
         echo "</td><td>";
         Location::dropdown(array('value'  => $values["locations_id"]));
         echo "</td></tr>";
      }

      if (!$tt->isHiddenField('name')
          || $tt->isPredefinedField('name')) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".sprintf(__('%1$s%2$s'), __('Title'), $tt->getMandatoryMark('name'))."</td>";
         echo "<td><input type='text' maxlength='250' size='80' name='name'
                    value=\"".$values['name']."\"></td></tr>";
      }

      if (!$tt->isHiddenField('content')
          || $tt->isPredefinedField('content')) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".sprintf(__('%1$s%2$s'), __('Description'), $tt->getMandatoryMark('content')).
              "</td>";
// * Changed by plugin surveyticket
// * Added by plugin surveyticket
$psTicketTemplate = new PluginSurveyticketTicketTemplate();
$psSurvey = new PluginSurveyticketSurvey();
$plugin_surveyticket_surveys_id = 0;
$a_tickettemplates = current($psTicketTemplate->find("`tickettemplates_id`='".$tt->fields['id']."'
                                                      AND `type`='".$values['type']."'
                                                      AND `is_helpdesk`='1'"));

if (isset($a_tickettemplates['plugin_surveyticket_surveys_id'])) {
   echo "<td>";
   $psSurvey = new PluginSurveyticketSurvey();
   $psSurvey->getFromDB($a_tickettemplates['plugin_surveyticket_surveys_id']);
   if ($psSurvey->fields['is_active'] == 1) {
      $plugin_surveyticket_surveys_id = $a_tickettemplates['plugin_surveyticket_surveys_id'];
      $psSurvey = new PluginSurveyticketSurvey();
      $psSurvey->startSurvey($plugin_surveyticket_surveys_id);
   }
} else {
   echo "<td><textarea name='content' cols='80' rows='14'>".$values['content']."</textarea>";
}
// * End of change
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s (%2$s)'), __('File'), Document::getMaxUploadSize());
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/aide.png' class='pointer' alt='".
             __s('Help')."' onclick=\"window.open('".$CFG_GLPI["root_doc"].
             "/front/documenttype.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";
      echo "&nbsp;";
      Ticket::showDocumentAddButton(60);
      echo "</td>";
      echo "<td><div id='uploadfiles'><input type='file' name='filename[]' value='' size='60'></div>";
      echo "</td></tr>";

      if (!$ticket_template) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>";

         if ($tt->isField('id') && ($tt->fields['id'] > 0)) {
            echo "<input type='hidden' name='_tickettemplates_id' value='".$tt->fields['id']."'>";
            echo "<input type='hidden' name='_predefined_fields'
                   value=\"".Toolbox::prepareArrayForInput($predefined_fields)."\">";
         }
         echo "<input type='submit' name='add' value=\"".__s('Submit message')."\" class='submit'>";
         echo "</td></tr>";
      }

      echo "</table></div>";
      if (!$ticket_template) {
         Html::closeForm();
      }
   }



   function startSurvey($plugin_surveyticket_surveys_id) {
      // If values are saved in session we retrieve it
      if (isset($_SESSION['glpi_plugin_surveyticket_ticket'])) {
         $session = $_SESSION['glpi_plugin_surveyticket_ticket'];
         unset($_SESSION['glpi_plugin_surveyticket_ticket']);
      } else {
         $session = array();
      }
      $psSurveyQuestion = new PluginSurveyticketSurveyQuestion();

      $a_questions = $psSurveyQuestion->find(
              "`plugin_surveyticket_surveys_id`='".$plugin_surveyticket_surveys_id."'",
              "`order`");
      
      echo "<input name='plugin_surveyticket_surveys_id' type='hidden' value='" . $plugin_surveyticket_surveys_id . "'/>";
      foreach ($a_questions as $data) {
         $this->displaySurvey($data['plugin_surveyticket_questions_id'], $plugin_surveyticket_surveys_id, $session);
      }
   }



   function displaySurvey($questions_id, $plugin_surveyticket_surveys_id, $session, $answer_id = 0) {
      global $CFG_GLPI;
      $psQuestion = new PluginSurveyticketQuestion();

      if ($psQuestion->getFromDB($questions_id)) {
      ////////////// Correction du bug : Alignement des questions / réponses à gauche + saut de ligne entre chaque question /////////////
     //////////////                              Titre des questions alignés à gauche                                      /////////////
         echo "<table class='tab_cadre' style='margin: 0;' width='700' >";

         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='3' style='text-align: left;'>";
         if ($plugin_surveyticket_surveys_id == -1) {
            $answer = new PluginSurveyticketAnswer();
            if($answer->getFromDB($answer_id)){
               if ($answer->fields['mandatory']) {
                  echo $psQuestion->fields['name'] . " <span class='red'>&nbsp;*&nbsp;</span>";
               } else {
                  echo $psQuestion->fields['name'] . " ";
               }
            }else{
               echo $psQuestion->fields['name'] . " ";
            }
         } else {
            $surveyquestion = new PluginSurveyticketSurveyQuestion();
            $surveyquestion->getFromDBByQuery("WHERE `plugin_surveyticket_questions_id` = " . $psQuestion->fields['id'] . " AND `plugin_surveyticket_surveys_id` = " . $plugin_surveyticket_surveys_id);
            if ($surveyquestion->fields['mandatory']) {
               echo $psQuestion->fields['name'] . " <span class='red'>&nbsp;*&nbsp;</span>";
            } else {
               echo $psQuestion->fields['name'] . " ";
            }
         }
         Html::showToolTip($psQuestion->fields['comment']);
         echo "</th>";
         echo "</tr>";
     ///////////////////////////////////////           Fin de la correction du bug            /////////////////////////////////////////
         $array       = $this->displayAnswers($questions_id, $session);
         $nb_answer   = $array['count'];
         $tab_answers = $array['answers'];

         echo "</table>";
         echo "<br/><div id='nextquestion".$questions_id."'></div>";
         echo $this->displayLink($questions_id, $session, $psQuestion, $nb_answer, $tab_answers);
      }
     ///////////////////////////////////// Fin de la correction du bug /////////////////////////////////////////////////

   }
   
   function displayLink($questions_id, $session, $psQuestion, $nb_answer, $answers) {
      global $CFG_GLPI;
      //javascript for links between issues
      if ($psQuestion->fields['type'] == PluginSurveyticketQuestion::RADIO || $psQuestion->fields['type'] == PluginSurveyticketQuestion::YESNO) {
         $event = array("click");
         $a_ids = array();
         //table id of all responses 
         for ($i = 0; $i < $nb_answer; $i++) {
            $a_ids[$answers[$i]] = 'question' . $questions_id . "-" . $i;
         }
         $params = array("question" . $questions_id => '__VALUE__',
                         'rand'                     => $questions_id,
                         'myname'                   => "question" . $questions_id);
      } else if (PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type'])) {
         $event  = array("change");
         $a_ids  = "realquestion" . $questions_id;
         $params = array("realquestion" . $questions_id => '__VALUE__',
                         'rand'                         => $questions_id,
                         'myname'                       => "realquestion" . $questions_id);
      } else {
         $event  = array("change");
         $a_ids  = 'question' . $questions_id;
         $params = array("question" . $questions_id => '__VALUE__',
                         'rand'                     => $questions_id,
                         'myname'                   => "question" . $questions_id);
      }
      //script to detect if a change in response to a question
      if (PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type'])) {
         echo "<script type='text/javascript'>";
         Ajax::updateItemJsCode("nextquestion".$questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, $a_ids);
         echo "</script>";
      } elseif ($psQuestion->fields['type'] != PluginSurveyticketQuestion::CHECKBOX) {
         echo "<script type='text/javascript'>";
         if (!is_array($a_ids)) {
            $a_ids = array($a_ids);
         }
         foreach ($a_ids as $key => $a_id) {
//            $params['answer_id'] = $key;
            $params['answer_id'] = '__VALUE__';
            Ajax::updateItemOnEventJsCode($a_id, "nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, array('change'));
         }
         echo "</script>";
         // Link to other issues loading the script
         if (!empty($session)) {
            $params['array'] = ($session);
            echo "<script type='text/javascript'>";
            if ($psQuestion->fields['type'] == PluginSurveyticketQuestion::DROPDOWN) {
               //dropdown load on the issue
               Ajax::updateItemJsCode("nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, "question" . $questions_id);
            } elseif ($psQuestion->fields['type'] == PluginSurveyticketQuestion::RADIO || $psQuestion->fields['type'] == PluginSurveyticketQuestion::YESNO) {
               //load on the selected response 
               $psAnswer = new PluginSurveyticketAnswer();
               $result = $psAnswer->find("`plugin_surveyticket_questions_id` = " . $psQuestion->fields['id']);
               $i = 0;
               foreach ($result as $data) {
                  $params['answer_id'] = $data['id'];
                  if (!empty($session[$questions_id]) && array_key_exists($data['id'], $session[$questions_id])) {
                     Ajax::updateItemJsCode("nextquestion".$questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, $a_ids[$data['id']]);
                  }
                  $i++;
               }
            }
            echo "</script>";
         }
      }
   }

   function displayAnswers($questions_id, $session) {
      $psQuestion = new PluginSurveyticketQuestion();
      $psAnswer = new PluginSurveyticketAnswer();
      $a_answers = $psAnswer->find("`plugin_surveyticket_questions_id`='".$questions_id."'");

      $psQuestion->getFromDB($questions_id);
      $answers = array();
      switch ($psQuestion->fields['type']) {
         case PluginSurveyticketQuestion::DROPDOWN:
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo "<select name='question".$questions_id."' id='question".$questions_id."' >";
            echo "<option>" . Dropdown::EMPTY_VALUE . "</option>";
            foreach ($a_answers as $data_answer) {
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  echo "<option value='" . $data_answer['id'] . "'>" . $psAnswer->getAnswer($data_answer) . "</option>";
               } else {
                  
                  echo "<option selected value='" . $data_answer['id'] . "'>" . $psAnswer->getAnswer($data_answer) . "</option>";
               }
            }
            echo "</select>";
            echo "</td>";
            echo "</tr>";
            break;

         case PluginSurveyticketQuestion::CHECKBOX :
            $i = 0;
            foreach ($a_answers as $data_answer) {
               echo "<tr class='tab_bg_1'>";
               echo "<td width='40' >";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  echo "<input type='checkbox' name='question" . $questions_id . "[]' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' />";
               } else {
                  echo "<input type='checkbox' name='question" . $questions_id . "[]' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' checked />";
               }
               echo "</td>";
               echo "<td>";
               echo $psAnswer->getAnswer($data_answer);
               echo "</td>";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  echo $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], NULL);
               } else {
                  echo $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], $session[$questions_id][$data_answer['id']]);
               }
               echo "</tr>";
               $answers[$i] = $data_answer['id'];
               $i++;
            }
            break;

         case PluginSurveyticketQuestion::RADIO :
         case PluginSurveyticketQuestion::YESNO :
            $i = 0;
            foreach ($a_answers as $data_answer) {
               echo "<tr class='tab_bg_1'>";
               echo "<td width='40'>";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  echo "<input type='radio' name='question" . $questions_id . "' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' />";
               } else {
                  echo "<input type='radio' name='question" . $questions_id . "' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' checked/>";
               }
               echo "</td>";
               echo "<td>";
               echo $psAnswer->getAnswer($data_answer);
               echo "</td>";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  echo $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], NULL);
               } else {
                  echo $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], $session[$questions_id][$data_answer['id']]);
               }
               echo "</tr>";
               $answers[$i] = $data_answer['id'];
               $i++;
            }
            break;

         case PluginSurveyticketQuestion::DATE :
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            $data_answer = current($a_answers);
             
            if (empty($session) || (empty($session[$questions_id])) || $session[$questions_id] == "NULL") {
               Html::showDateTimeField("question" . $questions_id, array('rand' => "question" . $questions_id));
            } else {
               Html::showDateTimeField("question" . $questions_id, array('rand' => "question" . $questions_id, 'value' => $session[$questions_id]));
            }
            echo '<input type="hidden" name="realquestion' . $questions_id . '" id="realquestion' . $questions_id . '" value="' . $data_answer['id'] . '" />';
            echo "</td>";
            echo "</tr>";
            break;

         case PluginSurveyticketQuestion::INPUT :
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            $data_answer = current($a_answers);
            if (empty($session) || empty($session[$questions_id])) {
               echo '<input type="text" name="question' . $questions_id . '" id="question' . $questions_id . '" value="" size="100" />';
            } else {
               echo '<input type="text" name="question' . $questions_id . '" id="question' . stripcslashes($questions_id) . '" value="' . stripcslashes($session[$questions_id]) . '" size="100" />';
            }
            echo '<input type="hidden" name="realquestion' . $questions_id . '" id="realquestion' . $questions_id . '" value="' . $data_answer['id'] . '" />';
            echo "</td>";
            echo "</tr>";
            break;

        //////////////////////////////////// Correction du bug : Nouveau type de question (Texte long) //////////////////////////////////
         case PluginSurveyticketQuestion::TEXTAREA :
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            $data_answer = current($a_answers);
            if (empty($session) || empty($session[$questions_id])) {
               echo '<textarea name="question' . $questions_id . '"  cols="90" rows="4"></textarea>';
            } else {
               echo '<textarea name="question' . $questions_id . '"  cols="90" rows="4" >' . stripcslashes($session[$questions_id]) . '</textarea>';
            }
            echo '<input type="hidden" name="realquestion' . $questions_id . '" id="realquestion' . $questions_id . '" value="' . $data_answer['id'] . '" />';
            echo "</td>";
            echo "</tr>";
            break;
       ////////////////////////////////////////////////// Fin de la correction du bug ///////////////////////////////////////////////////
      }
      return array("count" => count($a_answers), 'answers' => $answers);
   }



function displayAnswertype($type, $name, $session) {
      echo "<td>";
      if ($type != '') {
         switch ($type) {

            case 'shorttext':
               if ($session == NULL) {
                  echo "<input type='text' name='" . $name . "' value='' size='71'/>";
               } else {
                  echo "<input type='text' name='" . $name . "' value='" . stripcslashes($session) . "' size='71'/>";
               }
               break;

            case 'longtext':
               if ($session == NULL) {
                  echo "<textarea name='" . $name . "' cols='100' rows='4'></textarea>";
               } else {
                  echo '<textarea name="' . $name . '" cols="100" rows="4">'.stripcslashes($session).'</textarea>';
               }
               break;

            case 'date':
               if ($session == NULL) {
                  echo Html::showDateTimeField($name, array("display" => false));
               } else {
                  echo Html::showDateTimeField($name, array("display" => false, 'value' => $session));
               }
               break;

            case 'number':
               if ($session == NULL) {
                  echo Dropdown::showNumber($name, array("display" => false));
               } else {
                  echo Dropdown::showNumber($name, array("display" => false, 'value' => $session));
               }
               break;
         }
      }
      echo "</td>";
   }


   function displayOK() {
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th align='center'>";
      echo "<input type='submit' class='submit' value='".__('Post')."'/>";
      echo "</th>";
      echo "</tr>";

      echo "</table>";
      Html::closeForm();
   }

 static function preAddTicket(Ticket $ticket) {
      self::setSessions($ticket->input);
      if (self::checkMandatoryFields($ticket)) {
         //Recovery of the survey to put in the content of the ticket
         $psQuestion  = new PluginSurveyticketQuestion();
         $psAnswer    = new PluginSurveyticketAnswer();
         $description = '';
         foreach ($ticket->input as $question => $answer) {
            if (preg_match("/^question/", $question) && !preg_match("/^realquestion/", $question)) {
               $psQuestion->getFromDB(str_replace("question", "", $question));

               if (is_array($answer)) {
                  // Checkbox
                  $description .= _n('Question', 'Questions', 1, 'surveyticket') . " : " . $psQuestion->fields['name'] . "\n";
                  foreach ($answer as $answers_id) {
                     if ($psAnswer->getFromDB($answers_id)) {
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $psAnswer->fields['name'] . "\n";
                        $qid = str_replace("question", "", $question);
                        if (isset($ticket->input["text-" . $qid . "-" . $answers_id])
                           AND $ticket->input["text-" . $qid . "-" . $answers_id] != '') {
                           $description .= "Texte : " . $ticket->input["text-" . $qid . "-" . $answers_id] . "\n";
                        }
                     }
                  }
                  $description .= "\n";
                  unset($ticket->input[$question]);
               } else {
                  $real = 0;
                  if (isset($ticket->input['realquestion' . (str_replace("question", "", $question))]) && $ticket->input['realquestion' . (str_replace("question", "", $question))] != '') {
                     $realanswer = $answer;
                     $answer     = $ticket->input['realquestion' . str_replace("question", "", $question)];
                     $real       = 1;
                  }
                  $description .= "===========================================================================\n";
                  $description .= _n('Question', 'Questions', 1, 'surveyticket') . " : " . $psQuestion->fields['name'] . "\n";
                  //check if it is an id
                  if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && $psAnswer->getFromDB($answer)) {
                     if ($real == 1) {
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $realanswer . "\n";
                     } else {
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $psAnswer->fields['name'] . "\n";
                     }
                     $qid = str_replace("question", "", $question);
                     if (isset($ticket->input["text-" . $qid . "-" . $answer])
                        AND $ticket->input["text-" . $qid . "-" . $answer] != '') {
                        $description .= __('Text', 'surveyticket') . " : " . $ticket->input["text-" . $qid . "-" . $answer] . "\n";
                     }
                     $description .= "\n";
                     unset($ticket->input[$question]);
                  } else {
                     $description .= __('Text', 'surveyticket') . " : " . str_replace('\r', "", $answer) . "\n";
                     $description .= "\n";
                     unset($ticket->input[$question]);
                  }
               }
            }
            if ($description != '') {
               $ticket->input['content'] = $description;
            }
         }
      } else {
         $ticket->input =array();
      }

      return $ticket;
   }
  
   static function emptyTicket(Ticket $ticket) {
      if (!empty($_POST)) {
         self::setSessions($_POST);
      }if (!empty($_REQUEST) && !empty($_REQUEST['_tickettemplates_id'])) {
         $ticket         = new Ticket();
         $ticketTemplate = $ticket->getTicketTemplateToUse(false, $_REQUEST['type'], $_REQUEST['itilcategories_id'], $_REQUEST['entities_id']);
         if ($_REQUEST['_tickettemplates_id'] == $ticketTemplate->fields['id']) {
            self::setSessions($_REQUEST);
         }
      }
   }

   static function setSessions($input) {
      foreach ($input as $question => $answer) {
         if (preg_match("/^question/", $question) && !preg_match("/^realquestion/", $question)) {
            $psAnswer = new PluginSurveyticketAnswer();
            $psQuestion = new PluginSurveyticketQuestion();
            $qid = str_replace("question", "", $question);
            $psQuestion->getFromDB($qid);

            if (is_array($answer)) {
               foreach ($answer as $val) {
                  if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && $psAnswer->getFromDB($val)) {
                     if (isset($input["text-" . $qid . "-" . $val])
                        AND $input["text-" . $qid . "-" . $val] != '') {
                        $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$val] = $input["text-" . $qid . "-" . $val];
                     } else {
                        $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$val] = str_replace('\r', "", $val);
                     }
                  } else {
                     $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$val] = $val;
                  }
               }
            } else {
               if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && $psAnswer->getFromDB($answer)) {
                  if (isset($input["text-" . $qid . "-" . $answer])
                     AND $input["text-" . $qid . "-" . $answer] != '') {
                     $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$answer] = $input["text-" . $qid . "-" . $answer];
                  } else {
                     $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$answer] = str_replace('\r', "", $answer);
                  }
               } else {
                  $_SESSION['glpi_plugin_surveyticket_ticket'][$qid] = $answer;
               }
            }
         }
      }
   }
   
   static function checkMandatoryFields($ticket) {
         $msg     = array();
         $checkKo = false;
         $surveyquestion = new PluginSurveyticketSurveyQuestion();
         $a_questions    = $surveyquestion->find("`plugin_surveyticket_surveys_id`='" . $ticket->input['plugin_surveyticket_surveys_id'] . "'", "`order`");
         foreach ($a_questions as $data) {
            $reponse = self::checkQuestion(array('msg'=> $msg, 'checkKo' => $checkKo, 'data' => $data, 'ticket' => $ticket->input));
            $msg = $reponse['msg'];
            $checkKo = $reponse['checkKo'];
         }
         if ($checkKo) {
            Session::addMessageAfterRedirect(sprintf(__("Mandatory questions are not filled. Please correct: %s", 'surveyticket'), implode(', ', $msg)), false, ERROR);
            return false;
         }
         return true;
   }
   
   static function checkQuestion($param) {
      $data    = $param['data'];
      $msg     = $param['msg'];
      $checkKo = $param['checkKo'];
      $ticket  = $param['ticket'];
      
      $psQuestion = new PluginSurveyticketQuestion();
      $psQuestion->getFromDB($data['plugin_surveyticket_questions_id']);
      if (isset($data['mandatory']) && $data['mandatory']) {
         if (!isset($ticket['question' . $psQuestion->fields['id']]) || empty($ticket['question' . $psQuestion->fields['id']]) || $ticket['question' . $psQuestion->fields['id']] == '-----'){
            $msg[]   = $psQuestion->fields['name'];
            $checkKo = true;
         }
      }
      if (isset($ticket['question' . $psQuestion->fields['id']])) {
         $psAnswer = new PluginSurveyticketAnswer();
         if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && !is_array($ticket['question' . $psQuestion->fields['id']])) {
            if ($psAnswer->getFromDB($ticket['question' . $psQuestion->fields['id']])) {
               if ($psAnswer->fields['link'] > 0) {
                  $reponse = self::checkQuestion(array('msg'     => $msg,
                        'checkKo' => $checkKo,
                        'ticket'  => $ticket,
                        'data'    => array('plugin_surveyticket_questions_id'     => $psAnswer->fields['link'],
                           'mandatory'                            => $psAnswer->fields['mandatory'],
                           'old_plugin_surveyticket_questions_id' => $data['plugin_surveyticket_questions_id'])));
                  $msg     = $reponse['msg'];
                  $checkKo = $reponse['checkKo'];
               }
            }
         }
      }
      return array('msg' => $msg, 'checkKo' => $checkKo);
   }



   static function showFormHelpdesk($ID, $ticket_template=false) {

      $ticketdisplay = "";
      ob_start();
      $ticket = new Ticket();
      $ticket->showFormHelpdesk($ID, $ticket_template);
      $ticketdisplay = ob_get_contents();
      ob_end_clean();

      $ticketdisplay = str_replace("/front/tracking.injector.php",
              "/plugins/surveyticket/front/displaysurvey.form.php", $ticketdisplay);

      $split = explode("<td><textarea name='content' cols='80' rows='14'></textarea>", $ticketdisplay);

      echo $split[0];
      if (isset($split[1])) {
         echo "<td height='120'>";
//         $psSurvey = new PluginSurveyticketSurvey();
//         $psSurvey->startSurvey();
         echo $split[1];
      }
   }


}

?>
