<?php

/**
 * Motd (Message Of The Day)
 *
 * @version 0.1 - 24.01.2011
 * @author Georges DICK
 * @website http://georgesdick.com
 * @licence GNU GPL
 *
 **/
 
/**
 *
 * Usage: Similar to http://mail4us.net/myroundcube/
 *
 **/
 
class folder_maintenance extends rcube_plugin
{

  function init(){
   
    if(file_exists("./plugins/folder_maintenance/config/config.inc.php"))
      $this->load_config('config/config.inc.php');
    else
      $this->load_config('config/config.inc.php.dist');         
  
//    $this->include_script('folder_maintenance.js');
    $this->add_texts('localization/', false);
    $this->register_action('plugin.folder_maintenance', array($this, 'folder_maintenance_startup'));        
    $this->add_hook('template_object_folder_maintenance_message', array($this, 'folder_maintenance_html_folder_maintenance_message'));
    $this->add_hook('template_object_folder_maintenance_disable', array($this, 'folder_maintenance_html_disable'));
    $this->register_action('plugin.folder_maintenance_disable', array($this, 'folder_maintenance_disable'));    
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
    $rcmail = rcmail::get_instance();
    $le_user = $rcmail->user->data['username'];
    $content = 'Coucou a ' . $le_user . ' dans le nouveau plugin.<br />Liste :<br />';
    $page_size = $rcmail->config->get ('pagesize');
    $content .= 'La page fait ' . $page_size . ' messages<br />';
    $rcmail->imap_connect();
    $list_boxes = $rcmail->imap->list_mailboxes();
    $today = time();
    $ninetydays = $today - (86400 * 90);
    foreach ($list_boxes as $folder) {
      $nb_msg = $rcmail->imap->messagecount($folder);
      $content .= $folder . ':' . $nb_msg . ' messages<br />';
      if ($nb_msg > 0) {
        $i = $nb_old_msg = 0;
        for ($num_page = $msg_cour = 0; $msg_cour < $nb_msg; $msg_cour += $page_size, $num_page++) {
          $headers = $rcmail->imap->list_headers($folder,$num_page);
          foreach ($headers as $le_header) {
              if ($le_header->timestamp < $ninetydays) {
	      $nb_old_msg++;
	      }
            $i++;
            }
          }
        $content .= 'Total : ' . $i . ' dont ' . $nb_old_msg . ' vieux';
        }
      $content .= '<hr />';
      }
    $folder_maintenance  = '<fieldset><legend>' . $this->gettext('folder_maintenance') . '</legend>';
    $folder_maintenance .= $content;
    $folder_maintenance .= '</fieldset>';  
    $args['content'] = $folder_maintenance;
    return $args;
  }

  function folder_maintenance_html_disable($args){
    $html  = '<br />';
    $html .= '<form name="f" method="post" action="./?_action=plugin.folder_maintenance_disable">';
    $html .= '<table width="100%"><tr><td align="right">';
    $html .= $this->gettext('disablefolder_maintenance') . '&nbsp;' . '<input name="_folder_maintenancedisable" value="1" onclick="document.forms.f.submit()" type="checkbox" />&nbsp;';
    $html .= '</td></tr></table>';
    $html .= '</form>';
    $args['content'] = $html;
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

  function folder_maintenance_disable(){
    if($_POST['_folder_maintenancedisable'] == 1){
      $rcmail = rcmail::get_instance();    
      $a_prefs = $rcmail->user->get_prefs();
      $a_prefs['nofolder_maintenance'] = date ('omdHi');
      $rcmail->user->save_prefs($a_prefs);
      $rcmail->output->redirect(array('_action' => '', '_mbox' => 'INBOX'));
    }
    return;
  }

}

?>