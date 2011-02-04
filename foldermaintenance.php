<?php

/**
 * Folder Maintenance (Manual or automatic old messages cleanup)
 *
 * @uses	rcube_plugin
 * @file foldermaintenance.php
 * @version 0.9 - 30.01.2011
 * @author Georges DICK
 * @website http://georgesdick.com
 * @licence GNU GPL
 *
 **/
 
/**
 *
 * Usage: At your own risk !
 *
 **/
 
class foldermaintenance extends rcube_plugin
{
/**
 *
 * @author Georges DICK
 * @brief Init function (action and hooks registrations)
 *
 */
  function init(){
   
    if(file_exists("./plugins/foldermaintenance/config/config.inc.php"))
      $this->load_config('config/config.inc.php');
    else
      $this->load_config('config/config.inc.php.dist');         
  
    $this->add_texts('localization/', array('foldermaintenance'));
    $this->register_action('plugin.foldermaintenance_step', array($this, 'foldermaintenance_step'));
    $this->register_action('plugin.foldermaintenance_send_list', array($this, 'foldermaintenance_send_list'));
    $this->register_action('plugin.foldermaintenance_clean', array($this, 'foldermaintenance_clean'));
    $this->include_script('foldermaintenance.js');
    $this->register_action('plugin.foldermaintenance', array($this, 'foldermaintenance_startup'));        
//    $this->add_hook('template_object_foldermaintenance_message', array($this, 'foldermaintenance_html_foldermaintenance_message'));
    $this->add_hook('preferences_list', array($this, 'prefs_table'));
    $this->add_hook('preferences_save', array($this, 'save_prefs'));
    $this->add_hook('login_after', array($this, 'login_after'));
  }
  
 /**
 *
 * @author Georges DICK
 * @brief First function called (after a successfull login)
 *
 */
  function login_after($args){
    $rcmail = rcmail::get_instance();
/* !!!
    // You can set an account name as debug account, if you want to test this plugin
    if (!strcmp ('debug_account_name',$rcmail->user->data['username'])) {// !!!
!!! */
    $folder_list = $rcmail->config->get('foldermaintenance_startup_folders');
    $folder_array = explode (',', $folder_list);
    foreach ($folder_array as $folder) {
      $this->foldermaintenance_clean_folder ($folder);
    }
/* !!!
      }
!!! */
  }
  
 /**
 *
 * @author Georges DICK
 * @brief Prefs load function
 *
 */
  function prefs_table($args){
    if ($args['section'] == 'general') {
      $rcmail = rcmail::get_instance();    
      $nofoldermaintenance= $rcmail->config->get('nofoldermaintenance');
    }
    return $args;
  }

 /**
 *
 * @author Georges DICK
 * @brief Prefs save function
 *
 */
  function save_prefs($args){
    if($args['section'] == 'general'){
      $args['prefs']['nofoldermaintenance'] = get_input_value('_nofoldermaintenance', RCUBE_INPUT_POST);
      return $args;
    }
  }

 /**
 *
 * @author Georges DICK
 * @brief Function called by a click on the Maintenance tab (preferences screen)
 *
 */
  function foldermaintenance_step()
  {
  $this->register_handler('plugin.body', array($this, 'foldermaintenance_html'));
  rcmail::get_instance()->output->send('plugin');
  }

 /**
 *
 * @author Georges DICK
 * @brief Preferences screen maintenance tab display
 *
 */
  function foldermaintenance_html()
  {
  $the_list = $this->foldermaintenance_return_list();

  $rcmail = rcmail::get_instance();
  $user = $rcmail->user;

  $skin = "default";
  $this->include_stylesheet('skins/' . $skin . '/foldermaintenance.css');
    
  $table = new html_table(array('border' => 1, 'cols' => 4, 'cellpadding' => 1));

  $table->add('title', Q($the_list[3]));
  $table->add('title', Q($the_list[4]));
  $table->add('title', Q($the_list[5]));
  $table->add('title', Q($the_list[6]));
  $idx_tab = 7;
  $red_ratio = $rcmail->config->get('foldermaintenance_red_ratio');
  for ($i = 0; $i < $the_list[2]; $i++) {
    $folder_name = $the_list[$idx_tab++];
    $nb_tot = $the_list[$idx_tab++];
    $nb_old = $the_list[$idx_tab++];
    $table->add('text', Q($folder_name));
    $table->add('text', Q($nb_tot));
    $page_size = $rcmail->config->get ('pagesize');
    if ($nb_old > $page_size)
      $table->add('oversize', Q('> ' . $page_size));
    else if (($nb_old * $red_ratio) > $nb_tot)
      $table->add('quotafull', Q($nb_old));
    else
      $table->add('text', Q($nb_old));
    if ($nb_old > 0) {
      $checkbox_name = '_clean_' . $folder_name;
      $check_to_clean = new html_checkbox(array('name' => $checkboxname, 'id' => $checkbox_name, 'value' => 'clean'));
      $button_text = $check_to_clean->show();
      }
    else
      $button_text = '';
    $table->add('button', $button_text);
    }
  return html::tag('h4', null, Q($this->gettext('folders_of') . ' ' . $user->get_username())) . '<form id="foldermaintenance_clean" action="#foldermaintenance_clean">' . $table->show() . '<input type="button" value="'. Q($this->gettext('action')) . '" onClick="javascript:val_form()" id="submit" class="button" /></form>';
  }

 /**
 *
 * @author Georges DICK
 * @brief Preferences screen maintenance tab cleaning function (form analysis and execution)
 *
 */
  function foldermaintenance_clean()
  {
  $rcmail = rcmail::get_instance();
  $rcmail->imap_connect();
  $list_boxes = $rcmail->imap->list_mailboxes();
  foreach ($list_boxes as $folder) {
    $checkbox_name = '_clean_' . $folder;
// write_log('foldermaintenance', 'test de la checkbox "' . $checkbox_name . '"'); // !!!
    $checkbox_value = get_input_value($checkbox_name, RCUBE_INPUT_POST);
    if (!strcmp ($checkbox_value,'clean')) {
//      write_log('foldermaintenance', 'On doit nettoyer ' . $folder); // !!!
      $this->foldermaintenance_clean_folder ($folder);
      }
    }
  $return_buffer = $this->foldermaintenance_html();
  $rcmail->output->command('plugin.foldermaintenance_callback', array('form' => $return_buffer));
  $rcmail->imap->close();
  return;
  }

 /**
 *
 * @author Georges DICK
 * @brief Folder cleaning
 * @param folder_name The name of the folder to clean
 *
 */
  function foldermaintenance_clean_folder ($folder_name) {
  write_log('foldermaintenance', 'Nettoyage de ' . $folder_name); // !!!
  $rcmail = rcmail::get_instance();
  $nb_days = $rcmail->config->get('foldermaintenance_max_days');
  $today = time();
  $maxdays = $today - (86400 * $rcmail->config->get('foldermaintenance_max_days'));
  $page_size = $rcmail->config->get ('pagesize');
  $rcmail->imap_connect();
  $nb_msg = $rcmail->imap->messagecount($folder_name);
  if ($nb_msg <= 0) return 0;
  $i = $nb_old_msg = 0;

  $rcmail->imap->set_mailbox($folder_name);
  $message_list = $rcmail->imap->message_index($folder_name, 'Date:*', 'ASC');
  foreach ($message_list as $message_id) {
    if ($i++ > $page_size) break;
    $le_header = $rcmail->imap->get_headers($message_id, $folder_name, false);
    if ($le_header->timestamp < $maxdays) {
      $nb_old_msg++;
      $msg_uid = $rcmail->imap->get_uid($message_id,$folder_name);
      if ($nb_old_msg != 1) $msg_delete_list .= ',';
        $msg_delete_list .= $msg_uid;
//      if ($nb_old_msg < 10) // !!!
        write_log('foldermaintenance', 'On vire le message id : ' . $message_id . ' uid : ' . $msg_uid . ' Sujet : ' . $le_header->subject); // !!!
      }
    }
  if ($nb_old_msg) {
write_log('foldermaintenance', 'Liste de message uid à virer : "' . $msg_delete_list . '"'); // !!!
    $delete_result = $rcmail->imap->delete_message($msg_delete_list,$folder_name);
write_log('foldermaintenance', 'delete_message rend ' . $delete_result); // !!!
    $rcmail->imap->expunge($folder_name);
    $rcmail->imap->clear_cache();
    }
  else { // !!!
write_log('foldermaintenance', 'Aucun message à nettoyer'); // !!!
    } // !!!
  $rcmail->imap->close();
  return $nb_old_msg;
  }

 /**
 *
 * @author Georges DICK
 * @brief Folder list construction
 *
 */
  function foldermaintenance_return_list()
  {
// write_log('foldermaintenance', '----------entree dans foldermaintenance_return_list'); // !!!
  $rcmail = rcmail::get_instance();
  $user = $rcmail->user;

  $return_table = array();

  // Some init values
  $return_table[] = $this->gettext('action');
  $return_table[] = $rcmail->config->get('foldermaintenance_max_days');
  // Temporary value for the number of folders
  $return_table[] = 0;

  // Table titles
  $return_table[] = $this->gettext('folder_name');
  $return_table[] = $this->gettext('total_messages');
  $return_table[] = $this->gettext('old_messages');
  $return_table[] = $this->gettext('cleanup');
  
  // From here, populating the real thing
  $page_size = $rcmail->config->get ('pagesize');
  $rcmail->imap_connect();
  $rcmail->imap->clear_cache();
  $list_boxes = $rcmail->imap->list_mailboxes();
  $today = time();
  $maxdays = $today - (86400 * $rcmail->config->get('foldermaintenance_max_days'));
  $folder_number = 0;
  $tot_mess = 0;
  foreach ($list_boxes as $folder) {
    $folder_number++;
    $nb_msg = $rcmail->imap->messagecount($folder);
// write_log('foldermaintenance', $folder_number . ' Dossier ' . $folder . ' ' . $nb_msg . ' messages'); // !!!
    $rcmail->imap->set_mailbox($folder);
    $return_table[] = $folder;
    if ($nb_msg <= 0) {
      $return_table[] = $this->gettext('empty');
      $return_table[] = $content = $this->gettext('none');
      continue;
      }
    $i = $nb_old_msg = 0;
    $message_list = $rcmail->imap->message_index($folder, 'Date:*', 'ASC');
    foreach ($message_list as $message_id) {
      if ($i++ > $page_size) break;
      $msg_uid = $rcmail->imap->get_uid($message_id,$folder);
      $le_header = $rcmail->imap->get_headers($message_id, $folder, false, false);
//if (!strcmp ($folder,'INBOX')) // !!!
// write_log('foldermaintenance', 'id ' . $message_id . ' uid ' . $msg_uid . ' timestamp : ' . $le_header->timestamp); // !!!
      if (($msg_uid > 0) && ($le_header->timestamp < $maxdays)) {
	$nb_old_msg++;
//if (!strcmp ($folder,'INBOX')) // !!!
// write_log('foldermaintenance', '================boite ' . $folder . ' old id ' . $message_id . ' uid ' . $msg_uid . ' timestamp : ' . $le_header->timestamp . ' Sujet ' . $le_header->subject); // !!!
        }
      }
    $return_table[] = $nb_msg;
    $return_table[] = $nb_old_msg;
//    if ($folder_number > 3) break; // !!!
    }

    $return_table[2] = $folder_number;
  $rcmail->imap->close();
  return $return_table;
  }

}

?>
