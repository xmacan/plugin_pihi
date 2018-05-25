<?php

function plugin_pihi_install ()	{
//    api_plugin_register_hook('topx', 'poller_output', 'topx_poller_output', 'setup.php');
    api_plugin_register_hook('pihi', 'poller_bottom', 'pihi_poller_bottom', 'setup.php');
    api_plugin_register_hook('pihi', 'top_header_tabs', 'pihi_show_tab', 'setup.php');
    api_plugin_register_hook('pihi', 'top_graph_header_tabs', 'pihi_show_tab', 'setup.php');


    api_plugin_register_hook('pihi', 'config_form','pihi_config_form', 'setup.php');
    api_plugin_register_hook('pihi', 'api_device_save', 'pihi_api_device_save', 'setup.php');


    // muze zapinat topx adminovi a davam moznost ho pridavat ostatnim
    api_plugin_register_realm('pihi', 'pihi.php,', 'Plugin PiHi - view', 1);

    pihi_setup_database();
}



function pihi_setup_database()	{

    $data = array();
    //$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false,'auto_increment' => true);
    $data['columns'][] = array('name' => 'host_id', 'type' => "int(11)", 'NULL' => false);
    $data['columns'][] = array('name' => 'days', 'type' => "int(4)", 'default' => '30', 'NULL' => false);
    $data['type'] = 'MyISAM';
    $data['comment'] = 'pihi data';
    api_plugin_db_table_create ('pihi', 'plugin_pihi_setting', $data);

    $data = array();
    //$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false,'auto_increment' => true);
    $data['columns'][] = array('name' => 'host_id', 'type' => "int(11)", 'NULL' => false);
    $data['columns'][] = array('name' => 'duration', 'type' => "decimal(10,5)", 'NULL' => true);
    $data['columns'][] = array('name' => 'date', 'type' => "datetime", 'NULL' => false);
    //$data['primary'] = '(host_id,date)';
    $data['type'] = 'MyISAM';
    $data['comment'] = 'pihi data';
    api_plugin_db_table_create ('pihi', 'plugin_pihi_data', $data);

//    db_execute ("INSERT INTO plugin_topx_source (sorting,dt_name,hash,operation,unit,final_operation,final_unit,final_number) values ('desc','ucd/net - Load Average - 1 Minute','9b82d44eb563027659683765f92c9757','load_1min=load_1min','Load','strip','load','2')");
//    db_execute ("ALTER TABLE plugin_pihi_data add index (host_id)");
    // ! mozna jeste udelat pihi_statistika na soucty apod.

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
	
    //list($micro,$seconds) = split(" ", microtime());
    list($micro,$seconds) = explode(" ", microtime());
    $start = $seconds + $micro;

    $now = time();

    $poller_interval = read_config_option("poller_interval");

    // tady ulozit z host do moji tabulky nebo rrd
    $in = '';
    $list_of_hosts = db_fetch_assoc ('select host_id from plugin_pihi_setting');
    if (count($list_of_hosts) > 0)	{
	foreach ($list_of_hosts as $host)	{
	    $in .= $host['host_id'] . ',';
	}
	$in = substr($in,0,-1);
    
	db_execute ("insert into plugin_pihi_data (host_id,duration,date) select id,cur_time,now() from host where id in ($in)");

    }
        
    list($micro,$seconds) = explode(" ", microtime());
    $end = $seconds + $micro;
         
    /* log statistics */
    //$topx_stats = sprintf("Time:%01.4f DT:%s DS:%s CYCLES: %s", $end - $start, $dt_count, $ds_count, $cycles);
    cacti_log('PIHI STATS: hosts: ' . count($list_of_hosts) . '. Duration: ' . ($end-$start));
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
                        $fields_host_edit3['pihi_setting'] = array(
                                'friendly_name' => __('Ping history setting', 'pihi'),
                                'method' => 'drop_array',
                                'array' =>  array(
                                        '0' => __('Disabled', 'pihi'),
                                        '1' => __('Enabled, last day', 'pihi'),
                                        '3' => __('Enabled, last 3 days', 'pihi'),
                                        '7' => __('Enabled, last week', 'pihi'),
                                        '31' => __('Enabled, last month', 'pihi'),
                                ),
                                'description' => __('How log store ping history?', 'pihi'),
                                'value' => '|arg1:pihi_setting|',
                                //'on_change' => 'changeNotify()',
                                'default' => '0',
                                'form_id' => false



                        );
                }
        }
        $fields_host_edit = $fields_host_edit3;
}


function pihi_api_device_save($save) {
        global $config;


        if (isset_request_var('pihi_setting')) {
                $days = form_input_validate(get_nfilter_request_var('pihi_setting',30), 'pihi_setting', '^[0-9]$', true, 3);
	} 



        if (db_fetch_assoc('SELECT * FROM plugin_pihi_setting WHERE host_id = ' . $save['id']))	{
            $sql = 'UPDATE plugin_pihi_setting SET days=' . $days . ' where host_id = ' . $save['id'];
        }
        else	{
            $sql = 'insert into plugin_pihi_setting (host_id,days) values (' . $save['id'] . ',' . $days . ')';
        }

	$result = db_execute($sql);



/*

        if (!isset($result[0]['disabled'])) {
                return $save;
        }

        if ($save['disabled'] != $result[0]['disabled']) {
                if ($save['disabled'] == '') {
                        $sql = 'UPDATE plugin_pihi_setting SET days=' . $days . ' where host_id = ' . $save['id'];
                } else {
                        $sql = 'UPDATE thold_data SET thold_enabled = "off" WHERE host_id=' . $save['id'];
                        plugin_thold_log_changes($save['id'], 'disabled_host');
                }
                $result = db_execute($sql);
        }
*/



        return $save;
}




?>