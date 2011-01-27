/* Show folder_maintenance plugin script */

if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {
    var tab = $('<span>').attr('id', 'settingstabpluginfolder_maintenance').addClass('tablink');
    
    var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.folder_maintenance_step').html(rcmail.gettext('folder_maintenance', 'folder_maintenance')).appendTo(tab);
    button.bind('click', function(e){ return rcmail.command('plugin.folder_maintenance', this) });
    
    // add button and register command
    rcmail.add_element(tab, 'tabs');
    rcmail.register_command('plugin.folder_maintenance', function(){ rcmail.goto_url('plugin.folder_maintenance') }, true);
  })

}

function check_uncheck (box_name) {
//alert ("check_uncheck de " + box_name);
//rcmail.http_post('plugin.archive', '_uid='+uids+'&_mbox='+urlencode(rcmail.env.mailbox), lock);
}

function val_form () {
//rcmail.http_post('plugin.archive', '_uid='+uids+'&_mbox='+urlencode(rcmail.env.mailbox), lock);
//document.forms.folder_maintenance_clean.submit();
param_string = '';
for (i = 0; ; i++) {
  if (document.forms.folder_maintenance_clean.elements[i].id == 'submit')
    break;
  if (document.forms.folder_maintenance_clean.elements[i].checked == true) {
    param_string += document.forms.folder_maintenance_clean.elements[i].id + '=' + urlencode(document.forms.folder_maintenance_clean.elements[i].value) + '&';
    }
  }
rcmail.addEventListener('plugin.folder_maintenance_callback', folder_maintenance_callback);
alert ("AV post param_string : " + param_string);
rcmail.http_post('plugin.folder_maintenance_clean', param_string);
}

function folder_maintenance_callback (response)
{
alert ('appel de callback');
$('#mainscreen').html(response.form);
/*
  // Some init values
  button_name = my_list[0];
  max_days = my_list[1];

  // Table titles
  folder_name = my_list[2];
  total_messages = my_list[3];
  old_messages = my_list[4];
  cleanup = my_list[5];
  
alert ('max_days : ' + max_days);
*/
}