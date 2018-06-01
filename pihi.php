<?php

chdir('../../');
include_once('./include/auth.php');
include_once('./include/global_arrays.php');

set_default_action();

$poller_interval = read_config_option('poller_interval');
$selectedTheme = get_selected_theme();




$ar_age = array ("month" => "Last month", "week" => "Last week", "yesterday" => "Yesterday", "today" => "Today"); 


/* if the user pushed the 'clear' button */
if (get_request_var('clear_x')) {
    unset($_SESSION["age"]);
}

if ( isset_request_var ('age') )
    $_SESSION["age"] = get_request_var ('age');
if (!isset($_SESSION["age"]))
    $_SESSION["age"] = "today";


if ( isset_request_var ('host') )
    $_SESSION["host"] = get_request_var ('host');
if (!isset($_SESSION["host"]))
    $_SESSION["host"] = "";


general_header();

//print "<link type='text/css' href='" . $config["url_path"] . "plugins/topx/themes/common.css' rel='stylesheet'>\n";
//print "<link type='text/css' href='" . $config["url_path"] . "plugins/topx/themes/" . $selectedTheme . ".css' rel='stylesheet'>\n";

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

html_start_box("<strong>Ping history</strong>", "100%", $colors["header"], "3", "center", "");


?>

<tr bgcolor="#<?php print $colors["panel"];?>">
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
	echo "<option value=\"$key\" selected=\"selected\">$value</option>\n";
    else    
	echo "<option value=\"$key\">$value</option>\n";
}
?>

      </select>
     </td>

     
     <td nowrap style='white-space: nowrap;' width="50">
      &nbsp;Host:&nbsp;
     </td>
     <td width="1">
      <select name="host" onChange="applyViewAgeFilterChange(document.form_pihi)">

<?php
$hosts = db_fetch_assoc ('select distinct(host_id) as host_id, description from plugin_pihi_setting left join host on host.id=host_id order by description');
foreach ($hosts as $host)	{
    if ($_SESSION['host'] == $host['host_id'])
	echo '<option value="' . $host['host_id'] . '" selected="selected">' . $host['description'] . '</option>';
    else
	echo '<option value="' . $host['host_id'] . '">' . $host['description'] . '</option>';
}

/*

      </select>
     </td>
     <td nowrap style='white-space: nowrap;' width="20">
      &nbsp;Order:&nbsp;
     </td>
     <td width="1">
      <select name="sort" onChange="applyViewAgeFilterChange(document.form_topx)">
<?php
foreach ($ar_sort as $key=>$value)	{
    if ($_SESSION["sort"] == $key)
	echo "<option value=\"$key\" selected=\"selected\">$value</option>\n";
    else
	echo "<option value=\"$key\">$value</option>\n";
}
?>
      </select>
     </td>

*/
?>
     <td nowrap>
      &nbsp;<input type="submit" value="Go" title="Set/Refresh Filters">
      <input type="submit" name="clear_x" value="Clear" title="Clear Filters">
     </td>
    </tr>
  </table>
 </form>
</td>
</tr>

<?php

html_end_box();


// tady zjistit, jake vsechny typy mam (cpu, hdd, ...)
$result = db_fetch_assoc ('select duration,date, hour(date) as xhour, minute(date) as xminute from plugin_pihi_data where host_id = ' . $_SESSION['host'] . ' order by date');

echo 'select duration,date, hour(date) as xhour minute(date) as xminute from plugin_pihi_data where host_id = ' . $_SESSION['host'] . ' order by date';
//!!! tady potom jeste bude limit nebo spis select date between


if (count($result) > 0)	{    
    $hour = -1;
    $first = true;
    echo '<table class="pihi_table">';
    foreach ($result as $row)	{
	if ($hour != $row['xhour'])
	    echo '<tr><td>' . $row['xhour'] . ' </td>';
    
	if ($first)
	    echo '<td>' . $row['xminute'] . '<br/>';
	else
	    echo '<td>';
	
	echo  $row['duration'] . '</td>';
	$hour = $row['xhour'];
    }
    echo '</table>';
}
else	{	
    echo "No data";
}


bottom_footer();
?>
