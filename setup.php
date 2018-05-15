<?php

function plugin_pihi_install ()	{
//    api_plugin_register_hook('topx', 'poller_output', 'topx_poller_output', 'setup.php');
    api_plugin_register_hook('pihi', 'poller_bottom', 'pihi_poller_bottom', 'setup.php');
    api_plugin_register_hook('pihi', 'top_header_tabs', 'pihi_show_tab', 'setup.php');
    api_plugin_register_hook('pihi', 'top_graph_header_tabs', 'pihi_show_tab', 'setup.php');

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


?>