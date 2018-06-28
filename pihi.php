<?php

chdir('../../');
include_once('./include/auth.php');
include_once('./include/global_arrays.php');

set_default_action();

$poller_interval = read_config_option('poller_interval');
$selectedTheme = get_selected_theme();


$ar_age = array ('168' => 'Week', '24' => 'Day', '6' => '6 hours', '1' => '1 hour'); 


/* if the user pushed the 'clear' button */
if (get_request_var('clear_x')) {
    unset($_SESSION['age']);
}

if ( isset_request_var ('age') )
    $_SESSION['age'] = get_filter_request_var ('age');
if (!isset($_SESSION['age']))
    $_SESSION['age'] = 6;

if ( isset_request_var ('host') )
    $_SESSION['host'] = get_filter_request_var ('host');

if ( isset_request_var ('from') )
    $_SESSION['from'] = get_filter_request_var ('from', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/')));


general_header();

echo "<link type='text/css' href='" . $config['url_path'] . "plugins/pihi/themes/common.css' rel='stylesheet'>";


?>

<script type="text/javascript">
<!--

function applyViewAgeFilterChange(objForm) {
	strURL = '?age=' + objForm.age.value;
	strURL = strURL + '&host=' + objForm.host.value;
//	strURL = strURL + '&sort=' + objForm.sort.value;
	document.location = strURL;
}
-->
</script>
<?php

html_start_box('<strong>Ping history</strong>', '100%', $colors['header'], '3', 'center', '');
?>

<tr bgcolor="#<?php print $colors['panel'];?>">
 <td>
  <form name="form_pihi" action="pihi.php">
   <table width="100%" cellpadding="0" cellspacing="0">
    <tr class="navigate_form">
     <td nowrap style='white-space: nowrap;' width="50">
      Age:&nbsp;
     </td>
     <td width="1">
      <select name="age" onChange="applyViewAgeFilterChange(document.form_pihi)">

<?php
foreach ($ar_age as $key=>$value)	{
    if ($_SESSION["age"] == $key)
	echo '<option value=\'' . $key . '\' selected="selected">' . $value . '</option>';
    else    
	echo '<option value=\'' . $key . '\'>' . $value .'</option>';
}
?>
      </select>
     </td>

     
     <td nowrap style='white-space: nowrap;' width='50'>
      &nbsp;Host:&nbsp;
     </td>
     <td width='1'>
      <select name="host" onChange="applyViewAgeFilterChange(document.form_pihi)">

<?php
$hosts = db_fetch_assoc ('select distinct(host_id) as host_id, description from plugin_pihi_setting left join host on host.id=host_id order by description');
foreach ($hosts as $host)	{
    // default host
    if (!isset($_SESSION['host'])) $_SESSION['host'] = $host['host_id'];

    if ($_SESSION['host'] == $host['host_id'])	{
	echo '<option value="' . $host['host_id'] . '" selected="selected">' . $host['description'] . '</option>';
    }
    else
	echo '<option value="' . $host['host_id'] . '">' . $host['description'] . '</option>';
}

?>
     <td nowrap>
      &nbsp;<input type="submit" value="Go" title="Set/Refresh Filters">
      <input type="submit" name="clear_x" value="Clear" title="Clear Filters">
     </td>
    </tr>
  </table>
 </form>
</td>
<td>

<?php

$mins = ($_SESSION['age']*60) + date('i');

if (!isset($_SESSION['from']))
    $_SESSION['from'] = db_fetch_cell ('select now()');


$selected_date = db_fetch_cell ('select min(date) as xdate from plugin_pihi_data where host_id = ' . $_SESSION['host'] . ' and date between date_sub(\'' . $_SESSION['from'] . '\',interval ' . $mins . ' minute) and \'' . $_SESSION['from'] . '\'');

// before
if (db_fetch_cell ('select count(date) from plugin_pihi_data where host_id = ' . $_SESSION['host'] . ' and date < \'' . $_SESSION['from'] . '\''))	{
    $before = db_fetch_cell ('select date_sub(\'' . $_SESSION['from'] . '\',interval ' . $mins . ' minute)');
}

if (db_fetch_cell ('select count(date) from plugin_pihi_data where host_id = ' . $_SESSION['host'] . ' and date > \'' . $_SESSION['from'] . '\''))	{
    $after = db_fetch_cell ('select date_add(\'' . $_SESSION['from'] . '\',interval ' . $mins . ' minute)');
}

echo '<b>';
if (isset($before) && $before > 0) echo '<a href="?from=' . $before . '">&lt;&lt;</a> ';
else echo '&lt;&lt; ';

echo $selected_date;

if (isset($after) && $after > 0) echo '<a href="?from=' . $after . '">&gt;&gt;</a> ';
else echo '&gt;&gt; ';
echo '</b>';


echo '</td></tr>';
html_end_box();



$sql  = 'select duration,date, hour(date) as xhour, minute(date) as xminute from plugin_pihi_data where host_id = ' . $_SESSION['host'] ;
$sql .= ' and date between date_sub(\'' . $_SESSION['from'] . '\',interval ' . $mins . ' minute) and \'' . $_SESSION['from'] . '\'';
$sql .= ' order by date';

$result = db_fetch_assoc ($sql);

if (count($result) > 0)	{    
    $date = '';
    $dura = '';

    $hour = -1;
    $first = true;
    echo '<table class="pihi_table">';
    foreach ($result as $row)	{
	$date .= '"' . substr($row['date'],5,-3) . '",';
	$dura .= $row['duration'] . ',';

	if ($hour != $row['xhour'])
	    echo '<tr><td>' . $row['xhour'] . ' </td>';
    
	if ($first)	{
	    echo '<td>' . $row['xminute'] . '<br/>';
	}
	else
	    echo '<td>';

	echo $row['duration'] > 30 ? '<font color="red"><b>' . $row['duration'] . '</b></font>' : $row['duration'] . '</td>';

	echo '</td>';
	$hour = $row['xhour'];
    }
    echo '</table>';
    
    // displaying graph

    $date = substr($date,0,-1);
    $dura = substr($dura,0,-1);

    print '<div style="background: white;"><canvas  width="800" height="300" id="mychart"></canvas>';
    print '<script type="text/javascript">';
    $title1 = 'Ping history';
    $line_labels = $date;
    $line_values = $dura;


    print <<<EOF
var ctx = document.getElementById("mychart").getContext("2d");
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [$line_labels],
        datasets: [{
            label: '$title1',
            data: [$line_values],
            borderColor: 'rgba(220,220,220,0.5)',
            backgroundColor: 'rgba(220,220,220,0.5)',

        },

        ]
    },
    options: {
        responsive: false,
        tooltipTemplate: "<%= value %>%",
          scales: {
	    yAxes: [{
    		scaleLabel: {
    		    display: true,
    		    labelString: 'Duration [ms]'
    		}
	    }]
	}
    }

    
});

EOF;
print '</script>';

print '</div>';
// end of graph

}
else	{	
    echo 'No data';
}


bottom_footer();
?>