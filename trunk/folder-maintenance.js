/* Show folder_maintenance plugin script */

if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {
    // <span id="settingstabdefault" class="tablink"><roundcube:button command="preferences" type="link" label="preferences" title="editpreferences" /></span>
    var tab = $('<span>').attr('id', 'settingstabpluginfolder_maintenance').addClass('tablink');
    
    var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.folder_maintenance_step').html(rcmail.gettext('folder_maintenance', 'folder_maintenance')).appendTo(tab);
    button.bind('click', function(e){ return rcmail.command('plugin.folder_maintenance', this) });
    
    // add button and register command
    rcmail.add_element(tab, 'tabs');
    rcmail.register_command('plugin.folder_maintenance', function(){ rcmail.goto_url('plugin.folder_maintenance') }, true);
  })
}