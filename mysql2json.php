#!/usr/bin/php

<?php

/*
##########################################
#
# Work: output MySQL table rows as JSON; write to file
#
##########################################
*/

/*
#####################################################
# Set up logging, data source, and db connection
#####################################################
*/

$date_time = date("Ymd.His");
$log_file = "../../logs/tasks/" . basename(__FILE__) . ".${date_time}.log";
$fh = fopen($log_file, 'w');
$date_time = date("l dS F Y h:i:s A");
fwrite($fh, "$date_time: begin\n\n");
$hostName = "localhost";
$userName = "[MySQL account name]";
$pw = "[MySQL account pword]";
if(!($link=mysql_pconnect($hostName, $userName, $pw)))
{
	fwrite($fh, "error connecting to host");
	exit(4);
}  
$set_names_utf8_query = "SET NAMES 'utf8'";
$result = mysql_query($set_names_utf8_query, $link);
if (!$result) fwrite($fh, mysql_errno($link) . ":" . mysql_error($link) . "\n");  

/*
#####################################################
# Map local schema to platform schema where possible
#####################################################
*/

$select_query = "SELECT count(*) FROM lc_stage.loc_gov_bib_raw";
$result = mysql_query($select_query, $link);
if (!$result) fwrite($fh, mysql_errno($link) . ":" . mysql_error($link) . "\n");	
$row = mysql_fetch_row($result);
$num_rows = $row[0];

$batch_size = 100000;
$lower_bound = 0;
$upper_bound = $batch_size;
$count = 0;
$fcount = 0;
while ($lower_bound < $num_rows)
{
	$fcount++;
	$json_file = "/var/lc_ingestion/data/loc_gov_json/loc_gov_$fcount.json";
	$fh_json = fopen($json_file, 'w');	
	
	// Need to add in Marc505 in loc_gov_bib_raw schema
	$select_query = "SELECT
	*,
	uuid() as id,
	Marc245A as title,
	Marc245ASort as title_sort,
	concat_ws('%%', Marc100, Marc110, Marc111, Marc700, Marc710, Marc711) as creator,
	Marc260B as publisher,
	MarcMaterialFormat as format,
	LangFull as language,
	Marc050 as call_num,
	concat_ws('%%', Marc600, Marc610 ,Marc611, Marc630, Marc648, Marc650, Marc651, Marc653, Marc654, Marc655, Marc656, Marc657, Marc690, Marc691, Marc692, Marc693, Marc695) as subject,
	concat_ws('%%', Marc500, Marc504) as description,
	Marc001 as id_inst,
	Marc020 as id_isbn,
	Marc010 as id_lccn,
	Marc035A as id_oclc,
	'item' as resource_type
	FROM lc_stage.loc_gov_bib_raw
	WHERE RecordID >= $lower_bound and RecordID < $upper_bound
	";
	
	$sth = mysql_query($select_query, $link);
	$rows = array();
	while($r = mysql_fetch_assoc($sth)) 
	{
		$count++;
		// Test for format type: if "collection" => change default "item" value
		// for key resource_type to "collection"
		foreach($r as $key => $value)
		{
			if ($key == "format")
			{
				if (preg_match("/collection/", $value))
				{
					$r['resource_type'] = 'collection';
				}
			}
			elseif ($key == "creator" || $key == "subject" || $key == "description")
			{
				// Do some cleanup of concatenation separator
				$value = preg_replace("/%{3,}/", "%%", $value);
				$value = preg_replace("/^%+/", "", $value);
				$value = preg_replace("/%+$/", "", $value);
				$r[$key] = $value;
			}
		}	
		if ($count % 50000 == 0) echo "now processing record no. $count\n";
		fwrite($fh_json, json_encode($r) . "\n");
	}
	$lower_bound += $batch_size;
	$upper_bound += $batch_size;	
}
$date_time = date("l dS F Y h:i:s A");
fwrite($fh, "$date_time: end\n\n");