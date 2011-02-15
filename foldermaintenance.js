/**
 * Javascript functions (links to RoundCube AJAX scripts)
 *
 * @file foldermaintenance.js
 * @version 0.9 - 30.01.2011
 * @author Georges DICK
 * @website http://georgesdick.com
 * @licence GNU GPL
 *
 */

// Show foldermaintenance plugin button
if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {
    var tab = $('<span>').attr('id', 'settingstabpluginfoldermaintenance').addClass('tablink');
    
    var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.foldermaintenance_step').html(rcmail.gettext('foldermaintenance', 'foldermaintenance')).appendTo(tab);
    button.bind('click', function(e){ return rcmail.command('plugin.foldermaintenance', this) });
    
    // add button and register command
    rcmail.add_element(tab, 'tabs');
    rcmail.register_command('plugin.foldermaintenance', function(){ rcmail.goto_url('plugin.foldermaintenance') }, true);
  })

}

/**
 *
 * @author Georges DICK
 * @brief Form validation function
 *
 */
function val_form () {
param_string = '';
for (i = 0; ; i++) {
  if (document.forms.foldermaintenance_clean.elements[i].id == 'submit')
    break;
  if (document.forms.foldermaintenance_clean.elements[i].checked == true) {
    param_string += document.forms.foldermaintenance_clean.elements[i].id + '=' + urlencode(document.forms.foldermaintenance_clean.elements[i].value) + '&';
    }
  }
rcmail.addEventListener('plugin.foldermaintenance_callback', foldermaintenance_callback);
rcmail.http_post('plugin.foldermaintenance_clean', param_string);
}

/**
 *
 * @author Georges DICK
 * @brief After maintenance tab form validation (page reload)
 *
 */
function foldermaintenance_callback (response)
{
$('#mainscreen').html(response.form);
}
