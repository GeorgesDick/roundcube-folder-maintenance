<?php

/**
 * Folder Maintenance (Manual or automatic old messages cleanup)
 *
 * @version 0.1 - 24.01.2011
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
 
class folder_maintenance extends rcube_plugin
{

  function init(){
   
    if(file_exists("./plugins/folder_maintenance/config/config.inc.php"))
      $this->load_config('config/config.inc.php');
    else
      $this->load_config('config/config.inc.php.dist');         
  
    $this->add_texts('localization/', array('folder_maintenance'));
    $this->register_action('plugin.folder_maintenance_step', array($this, 'folder_maintenance_step'));
    $this->register_action('plugin.folder_maintenance_send_list', array($this, 'folder_maintenance_send_list'));
    $this->register_action('plugin.folder_maintenance_clean', array($this, 'folder_maintenance_clean'));
    $this->include_script('folder_maintenance.js');
    $this->register_action('plugin.folder_maintenance', array($this, 'folder_maintenance_startup'));        
    $this->add_hook('template_object_folder_maintenance_message', array($this, 'folder_maintenance_html_folder_maintenance_message'));
    $this->add_hook('preferences_list', array($this, 'prefs_table'));
    $this->add_hook('preferences_save', array($this, 'save_prefs'));
    $this->add_hook('login_after', array($this, 'login_after'));
  }
  
  function login_after($args){
    $rcmail = rcmail::get_instance();
    $rcmail->output->redirect(array('_action' => 'plugin.folder_maintenance', '_task' => 'mail'));
    die;
  }
  
  function folder_maintenance_startup(){
    $rcmail = rcmail::get_instance();
    if (!strcmp ('geo',$rcmail->user->data['username'])) {
      $skin  = $rcmail->config->get('skin');
      $skin = "default";
      $this->include_stylesheet('skins/' . $skin . '/folder_maintenance.css');
      $rcmail->output->send("folder_maintenance.folder_maintenance");
    }
  else {
      $rcmail->output->redirect(array('_action' => '', '_mbox' => 'INBOX'));
    }
  }

  function folder_maintenance_html_folder_maintenance_message($args){
    $the_list = $this->folder_maintenance_return_list();
    $rcmail = rcmail::get_instance();
    $the_user = $rcmail->user->data['username'];
    $max_days = $rcmail->config->get('folder_maintenance_max_days');
  
    $content = 'Coucou a ' . $the_user . ' dans le nouveau plugin.<hr />';
  
    $return_table[] = $this->gettext('action');
    $return_table[] = $rcmail->config->get('folder_maintenance_max_days');
    // Temporary value for the number of folders
    $return_table[] = 0;
  
    // Table titles
    $return_table[] = $this->gettext('folder_name');
    $return_table[] = $this->gettext('total_messages');
    $return_table[] = $this->gettext('old_messages');
    $return_table[] = $this->gettext('cleanup');
  
    $page_size = $rcmail->config->get ('pagesize');
    $content .= 'La page fait ' . $page_size . ' messages<br />';
  
    $idx_tab = 7;
    $red_ratio = $rcmail->config->get('folder_maintenance_red_ratio');
    for ($i = 0; $i < $the_list[2]; $i++) {
      $folder_name = $the_list[$idx_tab++];
      $nb_tot = $the_list[$idx_tab++];
      $nb_old = $the_list[$idx_tab++];
  
      if (!strcmp ($nb_tot,$this->gettext('empty')))
        $content .= $folder_name . ':' . $nb_tot . '<br />';
      else
        $content .= $folder_name . ':' . $nb_tot . ' messages<br />';
  
      if ($nb_tot > 0) {
        $content .= 'Total : ' . $nb_tot . ' dont ' . $nb_old . ' de plus de ' . $max_days . ' jours';
        }
      $content .= '<hr />';
      }
  
    $folder_maintenance  = '<fieldset><legend>' . $this->gettext('folder_maintenance') . '</legend>';
    $folder_maintenance .= $content;
    $folder_maintenance .= '</fieldset>';  
    $args['content'] = $folder_maintenance;
    return $args;
    }

  function prefs_table($args){
    if ($args['section'] == 'general') {
      $rcmail = rcmail::get_instance();    
      $nofolder_maintenance= $rcmail->config->get('nofolder_maintenance');
    }
    return $args;
  }

  function save_prefs($args){
    if($args['section'] == 'general'){
      $args['prefs']['nofolder_maintenance'] = get_input_value('_nofolder_maintenance', RCUBE_INPUT_POST);
      return $args;
    }
  }

  function folder_maintenance_step()
  {
  $this->register_handler('plugin.body', array($this, 'folder_maintenance_html'));
  rcmail::get_instance()->output->send('plugin');
  }

  function folder_maintenance_html()
  {
  $the_list = $this->folder_maintenance_return_list();

  $rcmail = rcmail::get_instance();
  $user = $rcmail->user;

  $skin = "default";
  $this->include_stylesheet('skins/' . $skin . '/folder_maintenance.css');
    
  $table = new html_table(array('border' => 1, 'cols' => 4, 'cellpadding' => 1));

  $table->add('text', Q($the_list[3]));
  $table->add('text', Q($the_list[4]));
  $table->add('text', Q($the_list[5]));
  $table->add('text', Q($the_list[6]));
  $idx_tab = 7;
  $red_ratio = $rcmail->config->get('folder_maintenance_red_ratio');
  for ($i = 0; $i < $the_list[2]; $i++) {
    $folder_name = $the_list[$idx_tab++];
    $nb_tot = $the_list[$idx_tab++];
    $nb_old = $the_list[$idx_tab++];
    $table->add('text', Q($folder_name));
    $table->add('text', Q($nb_tot));
    if (($nb_old * $red_ratio) > $nb_tot)
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
  return html::tag('h4', null, Q($this->gettext('folders_of') . ' ' . $user->get_username())) . '<form id="folder_maintenance_clean" action="#folder_maintenance_clean">' . $table->show() . '<input type="button" value="'. Q($this->gettext('action')) . '" onClick="javascript:val_form()" id="submit" class="button" /></form>';
  }

  function folder_maintenance_clean()
  {
  $rcmail = rcmail::get_instance();
  $rcmail->imap_connect();
  $list_boxes = $rcmail->imap->list_mailboxes();
  foreach ($list_boxes as $folder) {
    $checkbox_name = '_clean_' . $folder;
// !!! write_log('folder_maintenance', 'test de la checkbox "' . $checkbox_name . '"');
    $checkbox_value = get_input_value($checkbox_name, RCUBE_INPUT_POST);
    if (!strcmp ($checkbox_value,'clean')) {
      write_log('folder_maintenance', 'On doit nettoyer ' . $folder); // !!!
      $this->folder_maintenance_clean_folder ($folder);
      }
    }
  $return_buffer = $this->folder_maintenance_html();
  $rcmail->output->command('plugin.folder_maintenance_callback', array('form' => $return_buffer));
  return;
  }

  function folder_maintenance_clean_folder ($folder_name) {
  write_log('folder_maintenance', 'Nettoyage de ' . $folder_name); // !!!
  $rcmail = rcmail::get_instance();
  $nb_days = $rcmail->config->get('folder_maintenance_max_days');
  $today = time();
  $maxdays = $today - (86400 * $rcmail->config->get('folder_maintenance_max_days'));
  $page_size = $rcmail->config->get ('pagesize');
  $rcmail->imap_connect();
  $nb_msg = $rcmail->imap->messagecount($folder_name);
  if ($nb_msg <= 0) return 0;

  $nb_old_msg = 0;
  for ($num_page = $msg_cour = 0; $msg_cour < $nb_msg; $msg_cour += $page_size, $num_page++) {
    $headers = $rcmail->imap->list_headers($folder_name,$num_page);
    $msg_delete_list = '';
    foreach ($headers as $le_header) {
        if ($le_header->timestamp < $maxdays) {
        $nb_old_msg++;
	if ($nb_old_msg != 1) $msg_delete_list .= ',';
	$msg_delete_list .= $le_header->uid;
if ($nb_old_msg < 5)
write_log('folder_maintenance', 'On vire le message uid : ' . $le_header->uid . ' Sujet : ' . $le_header->subject); // !!!
        }
      }
    }
write_log('folder_maintenance', 'Liste de message uid � virer : ' . $msg_delete_list); // !!!
  return $nb_old_msg;
  }

  function folder_maintenance_return_list()
  {
  $rcmail = rcmail::get_instance();
  $user = $rcmail->user;

  $return_table = array();

  // Some init values
  $return_table[] = $this->gettext('action');
  $return_table[] = $rcmail->config->get('folder_maintenance_max_days');
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
  $list_boxes = $rcmail->imap->list_mailboxes();
  $today = time();
  $maxdays = $today - (86400 * $rcmail->config->get('folder_maintenance_max_days'));
  $max_iterations = $rcmail->config->get('folder_maintenance_max_iterations');
  $folder_number = 0;
  $tot_mess = 0;
  foreach ($list_boxes as $folder) {
    // Dirty hack because of PHP 5.3 memory leak
    if (($max_iterations) && ($tot_mess > $max_iterations)) break;

    $nb_msg = $rcmail->imap->messagecount($folder);
    $return_table[] = $folder;

/* !!! */
    if ($nb_msg > 0) {
      $i = $nb_old_msg = 0;
      $message_list = $rcmail->imap->message_index($folder);
      foreach ($message_list as $message_id) {
        // Dirty hack because of PHP 5.3 memory leak
        if (($max_iterations) && ($tot_mess > $max_iterations)) break;

        $i++;
        $le_header = $rcmail->imap->get_headers($message_id, $folder);
        if ($le_header->timestamp < $maxdays) {
          $nb_old_msg++;
	// Dirty hack to free some memory
	$le_header = NULL;
	unset ($le_header);
	// For some strange reasons, this log helps PHP 5.2 to manage memory
	// DON'T REMOVE IT !!!!!
	if (($tot_mess++ % 10) == 0) // !!!
	  write_log('folder_maintenance', 'Message : ' . $tot_mess); // !!!
        }
      }
    $return_table[] = $i;
    $return_table[] = $nb_old_msg;
    }
  else {
    $return_table[] = $this->gettext('empty');
    $return_table[] = $content = $this->gettext('none');
    }
/* !!! */

/* !!!
    if ($nb_msg > 0) {
      $i = $nb_old_msg = 0;
      for ($num_page = $msg_cour = 0; $msg_cour < $nb_msg; $msg_cour += $page_size, $num_page++) {
        $headers = $rcmail->imap->list_headers($folder,$num_page);
        foreach ($headers as $le_header) {
            if ($le_header->timestamp < $maxdays) {
              $nb_old_msg++;
            }
          $i++;
          }
        }
      $return_table[] = $i;
      $return_table[] = $nb_old_msg;
      }
    else {
      $return_table[] = $this->gettext('empty');
      $return_table[] = $content = $this->gettext('none');
      }
!!! */

    $folder_number++;
//    if ($folder_number > 4) break; // !!!
    }
    $return_table[2] = $folder_number;
  return $return_table;
  }

}

?>