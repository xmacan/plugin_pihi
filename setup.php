<?php

function plugin_pihi_install ()	{
    api_plugin_register_hook('pihi', 'poller_bottom', 'pihi_poller_bottom', 'setup.php');
    api_plugin_register_hook('pihi', 'top_header_tabs', 'pihi_show_tab', 'setup.php');
    api_plugin_register_hook('pihi', 'top_graph_header_tabs', 'pihi_show_tab', 'setup.php');
    api_plugin_register_hook('pihi', 'config_arrays', 'pihi_config_arrays', 'setup.php');
    api_plugin_register_hook('pihi', 'config_form','pihi_config_form', 'setup.php');
    api_plugin_register_hook('pihi', 'api_device_save', 'pihi_api_device_save', 'setup.php');

    // muze zapinat pihi adminovi a davam moznost ho pridavat ostatnim
    api_plugin_register_realm('pihi', 'pihi.php,', 'Plugin PiHi - view', 1);
    pihi_setup_database();
}


function pihi_setup_database()	{
/*
    $data = array();
    //$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false,'auto_increment' => true);
    $data['columns'][] = array('name' => 'host_id', 'type' => "int(11)", 'NULL' => false);
    $data['columns'][] = array('name' => 'days', 'type' => "int(4)", 'default' => '30', 'NULL' => false);
    $data['type'] = 'MyISAM';
    $data['comment'] = 'pihi data';
    api_plugin_db_table_create ('pihi', 'plugin_pihi_setting', $data);
*/

    $data = array();
    $data['columns'][] = array('name' => 'host_id', 'type' => "int(11)", 'NULL' => false);
    $data['columns'][] = array('name' => 'duration', 'type' => "decimal(6,2)", 'NULL' => true);
    $data['columns'][] = array('name' => 'date', 'type' => "datetime", 'NULL' => false);
    $data['type'] = 'MyISAM';
    $data['comment'] = 'pihi data';
    api_plugin_db_table_create ('pihi', 'plugin_pihi_data', $data);

    api_plugin_db_add_column('pihi', 'host', array('name' => 'pihi_days', 'type' => 'int(2)', 'NULL' => false, 'default' => '0', 'after' => 'disabled'));


}


function plugin_pihi_uninstall ()	{

        if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_pihi_data'")) > 0 )	{
                db_execute("DROP TABLE `plugin_pihi_data`");
        }

        if (sizeof(db_fetch_assoc("SHOW TABLES LIKE 'plugin_pihi_setting'")) > 0 )	{
                db_execute("DROP TABLE `plugin_pihi_setting`");
        }

}


function plugin_pihi_version()	{
    global $config;
    $info = parse_ini_file($config['base_path'] . '/plugins/pihi/INFO', true);
    return $info['info'];
}



function plugin_pihi_check_config () {
	return true;
}


function pihi_poller_bottom () {
    global $config;
	
    list($micro,$seconds) = explode(" ", microtime());
    $start = $seconds + $micro;

    $now = time();

    $poller_interval = read_config_option("poller_interval");

    $in = '';
    $list_of_hosts = db_fetch_assoc ('SELECT id,pihi_days FROM host where pihi_days > 0');
    if (count($list_of_hosts) > 0)	{
	foreach ($list_of_hosts as $host)	{
	    $in .= $host['id'] . ',';

	    // delete old data
	    if (date('H') == 23 && date('i') > 53)	{
		$host['pihi_days'];
		db_execute('DELETE FROM plugin_pihi_data WHERE host_id=' . $host['id'] . ' AND date < date_sub(now(), interval ' . $host['pihi_days'] . ' day)');
	    }    
	}
	$in = substr($in,0,-1);
    
	db_execute ("INSERT INTO plugin_pihi_data (host_id,duration,date) 
		     SELECT id,round(cur_time,3),now() FROM host WHERE id IN ($in)");
    }
        
    list($micro,$seconds) = explode(" ", microtime());
    $total_time = $seconds + $micro - $start;
         
    /* log statistics */
    cacti_log('PIHI STATS: hosts: ' . count($list_of_hosts) . '. Duration: ' . substr($total_time,0,5));
}



function pihi_show_tab () {
	global $config;
	if (api_user_realm_auth('pihi.php')) {
		$cp = false;
		if (basename($_SERVER['PHP_SELF']) == 'pihi.php')
		$cp = true;
		print '<a href="' . $config['url_path'] . 'plugins/pihi/pihi.php"><img src="' . $config['url_path'] . 'plugins/pihi/images/tab_pihi' . ($cp ? '_down': '') . '.gif" alt="pihi" align="absmiddle" border="0"></a>';
	}
}


function pihi_config_form () {
        global $fields_host_edit;
        $fields_host_edit2 = $fields_host_edit;
        $fields_host_edit3 = array();
        foreach ($fields_host_edit2 as $f => $a) {
                $fields_host_edit3[$f] = $a;
                if ($f == 'disabled') {
                        $fields_host_edit3['pihi_spacer'] = array(
                                'friendly_name' => __('Plugin Ping History', 'pihi'),
                                'method' => 'spacer',
                                'collapsible' => true
                        );
                        $fields_host_edit3['pihi_days'] = array(
                                'friendly_name' => __('Ping history setting', 'pihi'),
                                'method' => 'drop_array',
                                'array' =>  array(
                                        '0' => __('Disabled', 'pihi'),
                                        '1' => __('Enabled, last day', 'pihi'),
                                        '3' => __('Enabled, last 3 days', 'pihi'),
                                        '7' => __('Enabled, last week', 'pihi'),
                                        '30' => __('Enabled, last month', 'pihi'),
                                ),
                                'description' => __('How log store ping history?', 'pihi'),
                                'value' => '|arg1:pihi_days|',
                                //	'on_change' => 'changeNotify()',
                                'default' => '0',
                                'form_id' => false
                        );
                }
        }
        $fields_host_edit = $fields_host_edit3;
}


/////////////////////
/*
       global $fields_host_edit;
        $fields_host_edit2 = $fields_host_edit;
        $fields_host_edit3 = array();
        foreach ($fields_host_edit2 as $f => $a) {
                $fields_host_edit3[$f] = $a;
                if ($f == 'disabled') {
                        $fields_host_edit3['thold_mail_spacer'] = array(
                                'friendly_name' => __('Device Up/Down Notification Settings', 'thold'),
                                'method' => 'spacer',
                                'collapsible' => true
                        );
                        $fields_host_edit3['thold_send_email'] = array(
                                'friendly_name' => __('Threshold Up/Down Email Notification', 'thold'),
                                'method' => 'drop_array',
                                'array' =>  array(
                                        '0' => __('Disabled', 'thold'),
                                        '1' => __('Global List', 'thold'),
                                        '2' => __('List Below', 'thold'),
                                        '3' => __('Global and List Below', 'thold')
                                ),
                                'description' => __('Which Notification List(s) of should be notified about Device Up/Down events?', 'thold'),
                                'value' => '|arg1:thold_send_email|',
                                'on_change' => 'changeNotify()',
                                'default' => '0',
                                'form_id' => false
                        );
                        $fields_host_edit3['thold_host_email'] = array(
                                'friendly_name' => __('Notification List', 'thold'),
                                'description' => __('Additional Email address, separated by commas for multiple Emails.', 'thold'),
                                'method' => 'drop_sql',
                                'sql' => 'SELECT id,name FROM plugin_notification_lists ORDER BY name',
                                'value' => '|arg1:thold_host_email|',
                                'default' => '',
                                'none_value' => 'None'
                        );
                }
        }
        $fields_host_edit = $fields_host_edit3;
*/
////////////////////

function pihi_api_device_save($save) {
        global $config;


        if (isset_request_var('pihi_days')) {
                $days = form_input_validate(get_nfilter_request_var('pihi_days'), 'pihi_days', '^[0-9]{1,2}$', true, 3);
	} 

        $enabled = db_fetch_cell('SELECT * FROM host WHERE id = ' . $save['id'] . ' and pihi_days > 0') > 0  ? true : false;

	if (!$enabled && $days > 0)	{	//enable pihi
	    if ($save['availability_method'] != 1 && $save['availability_method'] != 3 && $save['availability_method'] != 4)	{
		raise_message('pihi_save');
	    }
	    else	{
        	db_execute('UPDATE host SET pihi_days=' . $days . ' where id = ' . $save['id']);
//        	db_execute('INSERT INTO plugin_pihi_setting (host_id,days) VALUES (' . $save['id'] . ',' . $days . ')');
	    }
	}
	elseif ($enabled && $days == 0)	{	// disable pihi
            db_execute('UPDATE host SET pihi_days=0 where id = ' . $save['id']);
//            db_execute('DELETE FROM plugin_pihi_setting where host_id =' .  $save['id']);
            db_execute('DELETE FROM  plugin_pihi_data where host_id =' .  $save['id']);
	}
	elseif ($enabled && $days > 0)	{	// maybe change history

	    if ($save['availability_method'] != 1 && $save['availability_method'] != 3 && $save['availability_method'] != 4)	{
		raise_message('pihi_save');
	    }
	    else	{
        	db_execute('UPDATE host SET pihi_days=' . $days . ' where id = ' . $save['id']);
    	    }
	} 

        return $save;
}

function pihi_config_arrays () {
        global $messages;

        $messages['pihi_save'] = array(
                'message' => __('If you enable pihi you have to select availability to ping/ping or snmp/ping and snmp', 'pihi'),
                'type' => 'error'
        );
}

