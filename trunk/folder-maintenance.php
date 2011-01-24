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
 
class motd extends rcube_plugin
{

  function init(){
   
    if(file_exists("./plugins/motd/config/config.inc.php"))
      $this->load_config('config/config.inc.php');
    else
      $this->load_config('config/config.inc.php.dist');         
  
    $this->include_script('motd.js');
    $this->add_texts('localization/', false);
    $this->register_action('plugin.motd', array($this, 'motd_startup'));        
    $this->add_hook('template_object_motd_message', array($this, 'motd_html_motd_message'));
    $this->add_hook('template_object_motd_disable', array($this, 'motd_html_disable'));
    $this->add_hook('preferences_list', array($this, 'prefs_table'));
    $this->add_hook('preferences_save', array($this, 'save_prefs'));
    $this->add_hook('login_after', array($this, 'login_after'));
    $this->register_action('plugin.motd_disable', array($this, 'motd_disable'));    
  }
  
  function login_after($args){
    $rcmail = rcmail::get_instance();
    $rcmail->output->redirect(array('_action' => 'plugin.motd', '_task' => 'mail'));
    die;
  }
  
  function motd_startup(){
    $rcmail = rcmail::get_instance();
    $filename = "./plugins/motd/motd/" . $_SESSION['language'] . ".html";
    if(file_exists($filename))
      $datefic = date ('omdHi', filemtime($filename));
    else
      $datefic = 0;
    $monresu = $rcmail->config->get('nomotd') - $datefic;
    if ($monresu < 0) {
     if(!file_exists("plugins/motd/skins/$skin/motd.css"))
        $skin  = $rcmail->config->get('skin');
      $skin = "default";
      $this->include_stylesheet('skins/' . $skin . '/motd.css');
      $rcmail->output->send("motd.motd");
      }
  else {
      $rcmail->output->redirect(array('_action' => '', '_mbox' => 'INBOX'));
    }
  }

  function motd_html_motd_message($args){
    if(file_exists("./plugins/motd/motd/" . $_SESSION['language'] . ".html"))
      $content = file_get_contents("./plugins/motd/motd/" . $_SESSION['language'] . ".html");
    else
      $content = file_get_contents("./plugins/motd/motd/en_US.html.dist");
    $motd  = '<fieldset><legend>' . $this->gettext('motd') . '</legend>';
    $motd .= $content;
    $motd .= '</fieldset>';  
    $args['content'] = $motd;
    return $args;
  }

  function motd_html_disable($args){
    $html  = '<br />';
    $html .= '<form name="f" method="post" action="./?_action=plugin.motd_disable">';
    $html .= '<table width="100%"><tr><td align="right">';
    $html .= $this->gettext('disablemotd') . '&nbsp;' . '<input name="_motddisable" value="1" onclick="document.forms.f.submit()" type="checkbox" />&nbsp;';
    $html .= '</td></tr></table>';
    $html .= '</form>';
    $args['content'] = $html;
    return $args;
  }  

  function prefs_table($args){
    if ($args['section'] == 'general') {
      $rcmail = rcmail::get_instance();    
      $nomotd= $rcmail->config->get('nomotd');
    }
    return $args;
  }

  function save_prefs($args){
    if($args['section'] == 'general'){
      $args['prefs']['nomotd'] = get_input_value('_nomotd', RCUBE_INPUT_POST);
      return $args;
    }
  }

  function motd_disable(){
    if($_POST['_motddisable'] == 1){
      $rcmail = rcmail::get_instance();    
      $a_prefs = $rcmail->user->get_prefs();
      $a_prefs['nomotd'] = date ('omdHi');
      $rcmail->user->save_prefs($a_prefs);
      $rcmail->output->redirect(array('_action' => '', '_mbox' => 'INBOX'));
    }
    return;
  }

}

?>
