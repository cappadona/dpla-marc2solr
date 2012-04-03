#!/usr/bin/php

<?php

/*
##########################################
#
# Work: parses Harvard raw MARC21 bib data and writes it to lc_raw.harvard_edu_bib_data_raw
#
# Notes: 
# 1. MySQL table structure in bib-data table closely mirrors the original MARC data structure 
# (though some tags and subfields have been ignored), with minimal formatting performed.  
# No new data layers added here.
#	2. Formatting:
# --- '%%' used as iteration separator when multiple instances of tag occur in same record
# --- All single-digit subfields have been suppressed (for example, '6' for linkage to vernacular forms and '5')
# --- No attempt has been made to change punctuation (for example, all author-field terminal periods have been retained)
# --- To separate subject components (as in Hollis opac), double-hyphen ("--") is used
#
##########################################
*/

require 'File/MARC.php';

/*
#####################################################
# Initialize dataset vars
#####################################################
*/
  
$data_id = "harvard_edu_bib_data_20120224";
$data_source = "harvard_edu";

/*
#####################################################
# Set up logging, data source, and db connection
#####################################################
*/

// Note that largest log-file size PHP can open is 2 GB (2,147,483,647 bytes)
$data_file = $argv[1];
$process_number = $argv[2];
date_default_timezone_set('America/New_York');
$date_time = date("Ymd.His");
$log_file = "../../../logs/tasks/marc21_bib_data_parse.process_${process_number}.${date_time}.log";
$fh = fopen($log_file, 'w');
$date_time = date("l dS F Y h:i:s A");
fwrite($fh, "$date_time: begin\n\n");
fwrite($fh, "data_file: [$data_file]\n");
fwrite($fh, "log_file: [$log_file]\n");
$hostName = "[MySQL host]";
$userName = "[MySQL account name]";
$pw = "[MySQL account password]";		
if(!($link=mysql_pconnect($hostName, $userName, $pw)))
{
	fwrite($fh, "error connecting to host");
	exit(4);
}  
$set_names_utf8_query = "SET NAMES 'utf8'";
$result = mysql_query($set_names_utf8_query, $link);
if (!$result) fwrite($fh, mysql_errno($link) . ":" . mysql_error($link) . "\n");
$date_time = date("l dS F Y h:i:s A");
fwrite($fh, "$date_time: begin\n\n");

function cleanup($input)
{
	// Clean up artifacts left over from multi-value separation ("%%") and LC subject parse formatting ("--")
	$cleaned_tag = preg_replace("/^%%/", "", $input);	
	$cleaned_tag = preg_replace("/%%$/", "", $cleaned_tag);	
	$cleaned_tag = preg_replace("/\s*(%%)\s*/", "$1", $cleaned_tag);	
	$cleaned_tag = preg_replace("/^\s--\s/", "", $cleaned_tag);	
	$cleaned_tag = preg_replace("/(%%)\s*--\s*/", "$1", $cleaned_tag);	
	// Clean up terminal white space and puncutation left at end of Marc245A
	$cleaned_tag = preg_replace("/[\s\/;\.:,]*$/", "$1", $cleaned_tag);	
	$cleaned_tag = trim($cleaned_tag);
	return $cleaned_tag;
}

function normalize($input)
{
	// Normalize authors and titles for uniform-title aggregation:
	// 1. remove all white space, periods, commas 
	// 2. lowercase everything
	$input_normalized = strtolower(preg_replace("/[\s\.,]+/", "", $input));
	return $input_normalized;
}

function return_language($language_code) 
{
	$language = "";
	switch ($language_code) 
	{
		case "ach":
		$language = "Acholi";
		break;
		case "afa":
		$language = "Afro-asiatic";
		break;
		case "afh":
		$language = "Afrihili";
		break;
		case "afr":
		$language = "Afrikaans";
		break;
		case "ajm":
		$language = "Aljamia";
		break;
		case "akk":
		$language = "Akkadian";
		break;
		case "alb":
		$language = "Albanian";
		break;
		case "ale":
		$language = "Aleut";
		break;
		case "alg":
		$language = "Algonquian languages";
		break;
		case "amh":
		$language = "Amharic";
		break;
		case "ang":
		$language = "Anglo-Saxon (ca. 600-1100)";
		break;
		case "apa":
		$language = "Apache";
		break;
		case "ara":
		$language = "Arabic";
		break;
		case "arc":
		$language = "Aramaic";
		break;
		case "arm":
		$language = "Armenian";
		break;
		case "arn":
		$language = "Araucanian";
		break;
		case "arp":
		$language = "Arapaho";
		break;
		case "arw":
		$language = "Arawak";
		break;
		case "asm":
		$language = "Assamese";
		break;
		case "ath":
		$language = "Athapascan languages";
		break;
		case "ava":
		$language = "Avaric";
		break;
		case "ave":
		$language = "Avesta";
		break;
		case "awa":
		$language = "Awadhi";
		break;
		case "aym":
		$language = "Aymara";
		break;
		case "aze":
		$language = "Azerbaijani";
		break;
		case "bak":
		$language = "Bashkir";
		break;
		case "bal":
		$language = "Baluchi";
		break;
		case "bam":
		$language = "Bambara";
		break;
		case "baq":
		$language = "Basque";
		break;
		case "bat":
		$language = "Baltic";
		break;
		case "bej":
		$language = "Beja";
		break;
		case "bel":
		$language = "Belorussian";
		break;
		case "bem":
		$language = "Bemba";
		break;
		case "ben":
		$language = "Bengali";
		break;
		case "ber":
		$language = "Berber languages";
		break;
		case "bho":
		$language = "Bhojpuri";
		break;
		case "bla":
		$language = "Blackfoot";
		break;
		case "bra":
		$language = "Braj";
		break;
		case "bre":
		$language = "Breton";
		break;
		case "bul":
		$language = "Bulgarian";
		break;
		case "bur":
		$language = "Burmese";
		break;
		case "cad":
		$language = "Caddo";
		break;
		case "cai":
		$language = "Central American Indian";
		break;
		case "cam":
		$language = "Cambodian";
		break;
		case "car":
		$language = "Carib";
		break;
		case "cat":
		$language = "Catalan";
		break;
		case "cau":
		$language = "Caucasian";
		break;
		case "chb":
		$language = "Chibcha";
		break;
		case "che":
		$language = "Chechen";
		break;
		case "chi":
		$language = "Chinese";
		break;
		case "chn":
		$language = "Chinook Jargon";
		break;
		case "cho":
		$language = "Choctaw";
		break;
		case "chr":
		$language = "Cherokee";
		break;
		case "chu":
		$language = "Church Slavic";
		break;
		case "chv":
		$language = "Chuvash";
		break;
		case "chy":
		$language = "Cheyenne";
		break;
		case "cop":
		$language = "Coptic";
		break;
		case "cor":
		$language = "Cornish";
		break;
		case "cre":
		$language = "Cree";
		break;
		case "crp":
		$language = "Creoles and Pidgins";
		break;
		case "cus":
		$language = "Cushitic";
		break;
		case "cze":
		$language = "Czech";
		break;
		case "cze":
		$language = "Bohemian (Czech)";
		break;
		case "dak":
		$language = "Dakota";
		break;
		case "dan":
		$language = "Danish";
		break;
		case "del":
		$language = "Delaware";
		break;
		case "din":
		$language = "Dinka";
		break;
		case "din":
		$language = "Denca (Dinka)";
		break;
		case "doi":
		$language = "Dogri";
		break;
		case "doi":
		$language = "Kangri (Dogri)";
		break;
		case "doi":
		$language = "Kongri (Dogri)";
		break;
		case "dra":
		$language = "Dravidian";
		break;
		case "dua":
		$language = "Duala";
		break;
		case "dum":
		$language = "Dutch, Middle (ca. 1050-1350)";
		break;
		case "dum":
		$language = "Middle Dutch (Dutch, Middle)";
		break;
		case "dut":
		$language = "Dutch";
		break;
		case "dut":
		$language = "Flemish (Dutch)";
		break;
		case "dut":
		$language = "Netherlandic (Dutch)";
		break;
		case "efi":
		$language = "Efik";
		break;
		case "efi":
		$language = "Ibidio (Efik)";
		break;
		case "egy":
		$language = "Egyptian";
		break;
		case "egy":
		$language = "Demotic (Egyptian)";
		break;
		case "egy":
		$language = "Hieratic (Egyptian)";
		break;
		case "egy":
		$language = "Hieroglyphics (Egyptian)";
		break;
		case "elx":
		$language = "Elamite";
		break;
		case "elx":
		$language = "Anzanite (Elamite)";
		break;
		case "elx":
		$language = "Susian (Elamite)";
		break;
		case "eng":
		$language = "English";
		break;
		case "enm":
		$language = "English, Middle (ca. 1100-1500)";
		break;
		case "enm":
		$language = "Middle English (English, Middle)";
		break;
		case "esk":
		$language = "Eskimo";
		break;
		case "esk":
		$language = "Eskimoan (Eskimo)";
		break;
		case "esk":
		$language = "Greenlandic (Eskimo)";
		break;
		case "esk":
		$language = "Yupik (Eskimo)";
		break;
		case "esp":
		$language = "Esperanto";
		break;
		case "est":
		$language = "Estonian";
		break;
		case "eth":
		$language = "Ethiopic";
		break;
		case "eth":
		$language = "Ge'ez (Ethiopic)";
		break;
		case "ewe":
		$language = "Ewe";
		break;
		case "fan":
		$language = "Fang";
		break;
		case "fan":
		$language = "Fan (Fang)";
		break;
		case "far":
		$language = "Faroese";
		break;
		case "fin":
		$language = "Finnish";
		break;
		case "fiu":
		$language = "Finno-Ugrian";
		break;
		case "fiu":
		$language = "Udmurt (Finno-Ugrian)";
		break;
		case "fiu":
		$language = "Votyak (Finno-Ugrian)";
		break;
		case "fon":
		$language = "Fon";
		break;
		case "fre":
		$language = "French";
		break;
		case "fri":
		$language = "Frisian";
		break;
		case "frm":
		$language = "French, Middle (ca. 1500-1700)";
		break;
		case "frm":
		$language = "Middle French (French, Middle ca. 1500-1700)";
		break;
		case "fro":
		$language = "French, Old (ca. 842-1500)";
		break;
		case "fro":
		$language = "Old French (ca. 842-1500)";
		break;
		case "gaa":
		$language = "Ga";
		break;
		case "gae":
		$language = "Gaelic (Scots)";
		break;
		case "gae":
		$language = "Scots Gaelic; use Gaelic (Scots)";
		break;
		case "gal":
		$language = "Galla";
		break;
		case "gem":
		$language = "Germanic";
		break;
		case "gem":
		$language = "Lallans (Germanic)";
		break;
		case "gem":
		$language = "Lowlands Scots (Germanic)";
		break;
		case "gem":
		$language = "Middle Scots (Germanic)";
		break;
		case "gem":
		$language = "Old Swedish (Germanic)";
		break;
		case "gem":
		$language = "Pennsylvania Dutch (Germanic)";
		break;
		case "gem":
		$language = "Swedish, Old (Germanic)";
		break;
		case "geo":
		$language = "Georgian";
		break;
		case "ger":
		$language = "German";
		break;
		case "gmh":
		$language = "German, Middle High (ca. 1050-1500)";
		break;
		case "gmh":
		$language = "Middle High German (German, Middle High)";
		break;
		case "goh":
		$language = "German, Old High (ca. 750-1050)";
		break;
		case "goh":
		$language = "Old High German (ca. 750-1050)";
		break;
		case "gon":
		$language = "Gondi";
		break;
		case "got":
		$language = "Gothic";
		break;
		case "grc":
		$language = "Greek, Ancient (to 1453)";
		break;
		case "grc":
		$language = "Ancient Greek (Greek, Ancient)";
		break;
		case "grc":
		$language = "Biblical Greek (Greek, Ancient)";
		break;
		case "grc":
		$language = "Byzantine Greek (Greek, Ancient)";
		break;
		case "grc":
		$language = "Classical Greek (Greek, Ancient)";
		break;
		case "grc":
		$language = "Greek, Biblical (Greek, Ancient)";
		break;
		case "grc":
		$language = "Greek, Byzantine (Greek, Ancient)";
		break;
		case "grc":
		$language = "Greek, Classical (Greek, Ancient)";
		break;
		case "grc":
		$language = "Greek, Hellenistic (Greek, Ancient)";
		break;
		case "grc":
		$language = "Greek, Medieval (Greek, Ancient)";
		break;
		case "grc":
		$language = "Greek, Patristic (Greek, Ancient)";
		break;
		case "grc":
		$language = "Hellenistic Greek (Greek, Ancient)";
		break;
		case "grc":
		$language = "Medieval Greek (Greek, Ancient)";
		break;
		case "grc":
		$language = "Patristic Greek (Greek,Ancient)";
		break;
		case "gre":
		$language = "Greek, Modern (1453- )";
		break;
		case "gua":
		$language = "Guarani";
		break;
		case "guj":
		$language = "Gujarati";
		break;
		case "hai":
		$language = "Haida";
		break;
		case "hau":
		$language = "Hausa";
		break;
		case "haw":
		$language = "Hawaiian";
		break;
		case "heb":
		$language = "Hebrew";
		break;
		case "heb":
		$language = "Ancient Hebrew (Hebrew)";
		break;
		case "heb":
		$language = "Modern Hebrew (Hebrew)";
		break;
		case "her":
		$language = "Herero";
		break;
		case "him":
		$language = "Himachali";
		break;
		case "hin":
		$language = "Hindi";
		break;
		case "hun":
		$language = "Hungarian";
		break;
		case "hun":
		$language = "Magyar (Hungarian)";
		break;
		case "hup":
		$language = "Hupa";
		break;
		case "ice":
		$language = "Icelandic";
		break;
		case "ilo":
		$language = "Ilocano";
		break;
		case "ilo":
		$language = "Iloko (Ilocano)";
		break;
		case "inc":
		$language = "Indic";
		break;
		case "ind":
		$language = "Indonesian";
		break;
		case "ine":
		$language = "Indo-European";
		break;
		case "ine":
		$language = "Irish, Old (Indo-European)";
		break;
		case "ine":
		$language = "Old Irish (Indo-European)";
		break;
		case "int":
		$language = "Interlingua";
		break;
		case "ira":
		$language = "Iranian";
		break;
		case "iri":
		$language = "Irish";
		break;
		case "iri":
		$language = "Erse (Irish)";
		break;
		case "iri":
		$language = "Gaelic (Irish)";
		break;
		case "iro":
		$language = "Iroquoian languages";
		break;
		case "iro":
		$language = "Cayuga (Iroquoian languages)";
		break;
		case "iro":
		$language = "Oneida (Iroquoian languages)";
		break;
		case "iro":
		$language = "Onondaga (Iroquoian languages)";
		break;
		case "iro":
		$language = "Seneca (Iroquoian languages)";
		break;
		case "iro":
		$language = "Tuscarora (Iroquoian languages)";
		break;
		case "ita":
		$language = "Italian";
		break;
		case "ita":
		$language = "Milanese (Italian)";
		break;
		case "jav":
		$language = "Javanese";
		break;
		case "jpn":
		$language = "Japanese (use for related languages)";
		break;
		case "jpr":
		$language = "Judaeo-Persian";
		break;
		case "jrb":
		$language = "Judaeo-Arabic";
		break;
		case "kaa":
		$language = "Karakalpak";
		break;
		case "kac":
		$language = "Kachin";
		break;
		case "kam":
		$language = "Kamba";
		break;
		case "kan":
		$language = "Kannada";
		break;
		case "kan":
		$language = "Canarese (Kannada)";
		break;
		case "kan":
		$language = "Kanarese (Kannada)";
		break;
		case "kar":
		$language = "Karen";
		break;
		case "kas":
		$language = "Kashmiri";
		break;
		case "kau":
		$language = "Kanuri";
		break;
		case "kaz":
		$language = "Kazakh";
		break;
		case "kha":
		$language = "Khasi";
		break;
		case "kho":
		$language = "Khotanese";
		break;
		case "kho":
		$language = "Saka (Khotanese)";
		break;
		case "kik":
		$language = "Kikuyu";
		break;
		case "kin":
		$language = "Kinyarwanda";
		break;
		case "kin":
		$language = "Ruanda (Kinyarwanda)";
		break;
		case "kir":
		$language = "Kirghiz";
		break;
		case "kok":
		$language = "Konkani";
		break;
		case "kon":
		$language = "Kongo";
		break;
		case "kon":
		$language = "Congo (Kongo)";
		break;
		case "kor":
		$language = "Korean (use for related lang. and dial.)";
		break;
		case "kpe":
		$language = "Kpelle";
		break;
		case "kpe":
		$language = "Guerze (Kpelle)";
		break;
		case "kro":
		$language = "Kru";
		break;
		case "kru":
		$language = "Kurukh";
		break;
		case "kur":
		$language = "Kurdish";
		break;
		case "kut":
		$language = "Kutenai";
		break;
		case "lad":
		$language = "Ladino";
		break;
		case "lad":
		$language = "Judaeo-Spanish (Ladino)";
		break;
		case "lad":
		$language = "Sephardic (Ladino)";
		break;
		case "lah":
		$language = "Lahnda";
		break;
		case "lah":
		$language = "Panjabi (Western); use Lahnda";
		break;
		case "lam":
		$language = "Lamba";
		break;
		case "lan":
		$language = "Langue d'oc";
		break;
		case "lan":
		$language = "Occitan, Modern (post-1500; use Langue d'oc)";
		break;
		case "lan":
		$language = "Provencal, Modern (post-1500) use Langue d'oc";
		break;
		case "lao":
		$language = "Lao";
		break;
		case "lap":
		$language = "Lapp";
		break;
		case "lat":
		$language = "Latin";
		break;
		case "lav":
		$language = "Latvian";
		break;
		case "lav":
		$language = "Lettish (Latvian)";
		break;
		case "lit":
		$language = "Lithuanian";
		break;
		case "lol":
		$language = "Lolo (Bantu)";
		break;
		case "lol":
		$language = "Mongo (Lolo--Bantu)";
		break;
		case "lub":
		$language = "Luba";
		break;
		case "lug":
		$language = "Luganda";
		break;
		case "lug":
		$language = "Ganda (Luganda)";
		break;
		case "lui":
		$language = "Luiseno";
		break;
		case "mac":
		$language = "Macedonian";
		break;
		case "mag":
		$language = "Magahi (Central, Northern or Southern)";
		break;
		case "mag":
		$language = "Central Magahi (Magahi)";
		break;
		case "mag":
		$language = "Northern Magahi (Magahi)";
		break;
		case "mag":
		$language = "Southern Magahi (Magahi)";
		break;
		case "mai":
		$language = "Maithili";
		break;
		case "mal":
		$language = "Malayalam";
		break;
		case "man":
		$language = "Mandingo";
		break;
		case "mao":
		$language = "Maori";
		break;
		case "map":
		$language = "Malayo-Polynesian";
		break;
		case "map":
		$language = "Iai (Malayo-Polynesian)";
		break;
		case "map":
		$language = "Chamorro (Malayo-Polynesian)";
		break;
		case "map":
		$language = "Javanese, Old (Malayo-Polynesian)";
		break;
		case "map":
		$language = "Kawi (Malayo-Polynesian)";
		break;
		case "map":
		$language = "Nguna (Malayo-Polynesian)";
		break;
		case "map":
		$language = "Old Javanese (Malayo-Polynesian)";
		break;
		case "map":
		$language = "Tongan (Malayo-Polynesian)";
		break;
		case "mar":
		$language = "Marathi";
		break;
		case "mas":
		$language = "Masai";
		break;
		case "max":
		$language = "Manx";
		break;
		case "may":
		$language = "Malay";
		break;
		case "men":
		$language = "Mende";
		break;
		case "mic":
		$language = "Micmac";
		break;
		case "mis":
		$language = "Miscellaneous";
		break;
		case "mis":
		$language = "Ainu (miscellaneous)";
		break;
		case "mis":
		$language = "Etruscan";
		break;
		case "mla":
		$language = "Malagasy";
		break;
		case "mla":
		$language = "Madagascan (Malagasy)";
		break;
		case "mlt":
		$language = "Maltese";
		break;
		case "mno":
		$language = "Manobo";
		break;
		case "moh":
		$language = "Mohawk";
		break;
		case "mol":
		$language = "Moldavian";
		break;
		case "mon":
		$language = "Mongol";
		break;
		case "mon":
		$language = "Mongolian (Mongol)";
		break;
		case "mos":
		$language = "Mossi";
		break;
		case "mos":
		$language = "Mole (Mossi)";
		break;
		case "mos":
		$language = "More (Mossi)";
		break;
		case "mul":
		$language = "Multilingual";
		break;
		case "mul":
		$language = "Polyglot (Multilingual)";
		break;
		case "mus":
		$language = "Muskogee";
		break;
		case "mus":
		$language = "Creek (Muskogee)";
		break;
		case "mwr":
		$language = "Marwari";
		break;
		case "myn":
		$language = "Mayan languages";
		break;
		case "myn":
		$language = "Chontal of Tabasco (Mayan languages)";
		break;
		case "myn":
		$language = "Chorti (Mayan languages)";
		break;
		case "myn":
		$language = "Jacalteca (Mayan languages)";
		break;
		case "myn":
		$language = "Kekchi (Mayan languages)";
		break;
		case "myn":
		$language = "Kiche (Mayan languages)";
		break;
		case "myn":
		$language = "Lacandon Maya (Mayan languages)";
		break;
		case "myn":
		$language = "Mopan Maya (Mayan languages)";
		break;
		case "myn":
		$language = "Quiche (Mayan languages)";
		break;
		case "myn":
		$language = "Tzeltal (Mayan languages)";
		break;
		case "myn":
		$language = "Tzotzil (Mayan languages)";
		break;
		case "nah":
		$language = "Nahuatlan";
		break;
		case "nah":
		$language = "Aztec (Nahuatlan)";
		break;
		case "nai":
		$language = "North American Indian";
		break;
		case "nai":
		$language = "Beothuk (North American Indian)";
		break;
		case "nav":
		$language = "Navajo";
		break;
		case "nep":
		$language = "Nepali";
		break;
		case "new":
		$language = "Newari";
		break;
		case "nic":
		$language = "Niger-Congo";
		break;
		case "nic":
		$language = "Akan (Niger-Congo)";
		break;
		case "nic":
		$language = "Ashanti (Niger-Congo)";
		break;
		case "nic":
		$language = "Bantu (Niger-Congo)";
		break;
		case "nic":
		$language = "Nyanga";
		break;
		case "nic":
		$language = "Sudanic Group (Niger-Congo)";
		break;
		case "nor":
		$language = "Norwegian";
		break;
		case "nor":
		$language = "Dano-Norwegian (Norwegian)";
		break;
		case "nor":
		$language = "Landsmaal (Norwegian)";
		break;
		case "nor":
		$language = "Riksmaal (Norwegian)";
		break;
		case "nso":
		$language = "Northern Sotho";
		break;
		case "nso":
		$language = "Sotho, Northern (Northern Sotho)";
		break;
		case "nub":
		$language = "Nubian";
		break;
		case "nya":
		$language = "Nyanja";
		break;
		case "nya":
		$language = "Cewa (Nyanja)";
		break;
		case "nya":
		$language = "Chewa (Nyanja)";
		break;
		case "nya":
		$language = "ChiChewa (Nyanja)";
		break;
		case "nym":
		$language = "Nyamwezi";
		break;
		case "nyo":
		$language = "Nyoro";
		break;
		case "oji":
		$language = "Ojibwa";
		break;
		case "oji":
		$language = "Algonkin (Ojibwa)";
		break;
		case "oji":
		$language = "Chippewa (Ojibwa)";
		break;
		case "oji":
		$language = "Ottawa (Ojibwa)";
		break;
		case "oji":
		$language = "Salteaux (Ojibwa)";
		break;
		case "ori":
		$language = "Oriya";
		break;
		case "osa":
		$language = "Osage";
		break;
		case "oss":
		$language = "Ossetic";
		break;
		case "ota":
		$language = "Ottoman Turkish";
		break;
		case "ota":
		$language = "Osmanli (Ottoman Turkish)";
		break;
		case "oto":
		$language = "Otomian languages";
		break;
		case "oto":
		$language = "Chichimeca-Jonaz (Otomian languages)";
		break;
		case "oto":
		$language = "Matlatzinca (Otomian languages)";
		break;
		case "oto":
		$language = "Mazahua (Otomian languages)";
		break;
		case "oto":
		$language = "Ocuiltec (Otomian languages)";
		break;
		case "oto":
		$language = "Othomi (Otomian languages)";
		break;
		case "oto":
		$language = "Otomi (Otomian languages)";
		break;
		case "oto":
		$language = "Pame (Otomian languages)";
		break;
		case "paa":
		$language = "Papuan-Australian";
		break;
		case "paa":
		$language = "Kewa (Papuan-Australian)";
		break;
		case "pal":
		$language = "Pahlavi";
		break;
		case "pal":
		$language = "Middle Persian (Pahlavi)";
		break;
		case "pal":
		$language = "Pehlevi (Pahlavi)";
		break;
		case "pal":
		$language = "Persian, Middle (Pahlavi)";
		break;
		case "pan":
		$language = "Panjabi";
		break;
		case "pan":
		$language = "Punjabi (Panjabi)";
		break;
		case "peo":
		$language = "Persian, Old (ca. 600 B.C.-400 B.C.)";
		break;
		case "peo":
		$language = "Old Persian (Persian, Old)";
		break;
		case "per":
		$language = "Persian, Modern";
		break;
		case "per":
		$language = "Farsi (Persian, Modern)";
		break;
		case "pli":
		$language = "Pali";
		break;
		case "pol":
		$language = "Polish";
		break;
		case "por":
		$language = "Portuguese";
		break;
		case "pra":
		$language = "Prakrit";
		break;
		case "pro":
		$language = "Provencal (to 1500); (before 4/84 included all Occitan)";
		break;
		case "pro":
		$language = "Occitan, Old (to 1500; use Provencal)";
		break;
		case "pro":
		$language = "Old Provencal (to 1500; use Provencal)";
		break;
		case "pro":
		$language = "Provencal, Old (to 1500); use Provencal";
		break;
		case "pus":
		$language = "Pushto";
		break;
		case "pus":
		$language = "Pashto (Pushto)";
		break;
		case "pus":
		$language = "Afghan (Pushto)";
		break;
		case "que":
		$language = "Quechua";
		break;
		case "que":
		$language = "Kechua (Quechua)";
		break;
		case "raj":
		$language = "Rajasthani";
		break;
		case "roa":
		$language = "Romance";
		break;
		case "roa":
		$language = "Anglo-Norman (Romance)";
		break;
		case "roa":
		$language = "Gallegan (Romance)";
		break;
		case "roh":
		$language = "Romansh (Rhaeto-Romance)";
		break;
		case "roh":
		$language = "Rhaeto-Romance";
		break;
		case "roh":
		$language = "Ladin (Rhaeto-Romance)";
		break;
		case "roh":
		$language = "Raeto-Romance (Rhaeto-Romance)";
		break;
		case "roh":
		$language = "Rumansh (Rhaeto-Romance)";
		break;
		case "roh":
		$language = "Sursilvan (Rhaeto-Romance)";
		break;
		case "rom":
		$language = "Romany";
		break;
		case "rom":
		$language = "Gipsy (Romany)";
		break;
		case "rom":
		$language = "Gypsy (Romany)";
		break;
		case "rum":
		$language = "Romanian";
		break;
		case "rum":
		$language = "Rumanian (Romanian)";
		break;
		case "run":
		$language = "Rundi";
		break;
		case "run":
		$language = "Kirundi (Rundi)";
		break;
		case "rus":
		$language = "Russian";
		break;
		case "sad":
		$language = "Sandawe";
		break;
		case "sag":
		$language = "Sango";
		break;
		case "sai":
		$language = "South American Indian";
		break;
		case "sal":
		$language = "Salishan Languages";
		break;
		case "sal":
		$language = "Bella Coola (Salishan languages)";
		break;
		case "sal":
		$language = "Comox (Salishan languages)";
		break;
		case "sal":
		$language = "Halkomelem (Salishan languages)";
		break;
		case "sal":
		$language = "Lillooet (Salishan languages)";
		break;
		case "sal":
		$language = "Ntlakyapamuk (Salishan languages)";
		break;
		case "sal":
		$language = "Okinagan (Salishan languages)";
		break;
		case "sal":
		$language = "Salish (Salishan languages)";
		break;
		case "sal":
		$language = "Seechelt (Salishan languages)";
		break;
		case "sal":
		$language = "Shuswap (Salishan languages)";
		break;
		case "sal":
		$language = "Squawmish (Salishan languages)";
		break;
		case "sal":
		$language = "Straits Salish (Salishan languages)";
		break;
		case "sal":
		$language = "Thompson (Salishan languages)";
		break;
		case "sam":
		$language = "Samaritan Aramaic";
		break;
		case "san":
		$language = "Sanskrit";
		break;
		case "san":
		$language = "Vedic (Sanskrit)";
		break;
		case "scc":
		$language = "Serbo-Croatian (Cyrillic)";
		break;
		case "scr":
		$language = "Serbo-Croatian (Roman)";
		break;
		case "scr":
		$language = "Croatian (Serbo-Croatian/Roman)";
		break;
		case "sel":
		$language = "Selkup";
		break;
		case "sel":
		$language = "Ostiak Samoyed (Selkup)";
		break;
		case "sem":
		$language = "Semitic";
		break;
		case "shn":
		$language = "Shan";
		break;
		case "sho":
		$language = "Shona";
		break;
		case "sho":
		$language = "Mashona (Shona)";
		break;
		case "sid":
		$language = "Sidamo";
		break;
		case "sio":
		$language = "Siouan languages";
		break;
		case "sio":
		$language = "Biloxi (Siouan languages)";
		break;
		case "sio":
		$language = "Chiwere (Siouan languages)";
		break;
		case "sio":
		$language = "Crow (Siouan languages)";
		break;
		case "sio":
		$language = "Hidatsa";
		break;
		case "sio":
		$language = "Mandan (Siouan languages)";
		break;
		case "sio":
		$language = "Ofogoula (Siouan languages)";
		break;
		case "sio":
		$language = "Tutelo (Siouan languages)";
		break;
		case "sio":
		$language = "Winnebago (Siouan languages)";
		break;
		case "sit":
		$language = "Sino-Tibetan";
		break;
		case "sla":
		$language = "Slavic";
		break;
		case "sla":
		$language = "Old Russian (Slavic)";
		break;
		case "sla":
		$language = "Russian, Old (Slavic)";
		break;
		case "slo":
		$language = "Slovak";
		break;
		case "slv":
		$language = "Slovenian";
		break;
		case "snd":
		$language = "Sindhi";
		break;
		case "snh":
		$language = "Sinhalese";
		break;
		case "sog":
		$language = "Sogdian";
		break;
		case "som":
		$language = "Somali";
		break;
		case "son":
		$language = "Songhai";
		break;
		case "spa":
		$language = "Spanish";
		break;
		case "spa":
		$language = "Castilian (Spanish)";
		break;
		case "srr":
		$language = "Serer";
		break;
		case "ssa":
		$language = "Sub-Saharan African";
		break;
		case "ssa":
		$language = "Bushman (Sub-Saharan African)";
		break;
		case "ssa":
		$language = "Hottentot (Sub-Saharan African)";
		break;
		case "ssa":
		$language = "Nandi (Sub-Saharan African)";
		break;
		case "sso":
		$language = "Southern Sotho";
		break;
		case "sso":
		$language = "Sotho, Southern (Southern Sotho)";
		break;
		case "sso":
		$language = "SeSotho Group (Southern Sotho)";
		break;
		case "sso":
		$language = "Sesuto (Southern Sotho)";
		break;
		case "sso":
		$language = "Sotho (Southern Sotho)";
		break;
		case "suk":
		$language = "Sukuma";
		break;
		case "sun":
		$language = "Sundanese";
		break;
		case "sus":
		$language = "Susu";
		break;
		case "sux":
		$language = "Sumerian";
		break;
		case "swa":
		$language = "Swahili";
		break;
		case "swe":
		$language = "Swedish";
		break;
		case "syr":
		$language = "Syriac";
		break;
		case "syr":
		$language = "Neo-Syriac (Syriac)";
		break;
		case "tag":
		$language = "Tagalog";
		break;
		case "tag":
		$language = "Filipino (Tagalog)";
		break;
		case "tag":
		$language = "Pilipino (Tagalog)";
		break;
		case "taj":
		$language = "Tajik";
		break;
		case "taj":
		$language = "Tadzhik (Tajik)";
		break;
		case "tam":
		$language = "Tamil";
		break;
		case "tar":
		$language = "Tatar";
		break;
		case "tel":
		$language = "Telugu";
		break;
		case "tem":
		$language = "Temne";
		break;
		case "tem":
		$language = "Timne (Temne)";
		break;
		case "ter":
		$language = "Tereno";
		break;
		case "tha":
		$language = "Thai";
		break;
		case "tha":
		$language = "Siamese (Thai)";
		break;
		case "tib":
		$language = "Tibetan";
		break;
		case "tig":
		$language = "Tigre";
		break;
		case "tir":
		$language = "Tigrina";
		break;
		case "tli":
		$language = "Tlingit";
		break;
		case "tsi":
		$language = "Tsimshian";
		break;
		case "tsw":
		$language = "Tswana";
		break;
		case "tsw":
		$language = "Sechuana (Tswana)";
		break;
		case "tuk":
		$language = "Turkmen";
		break;
		case "tuk":
		$language = "Turkoman (Turkmen)";
		break;
		case "tur":
		$language = "Turkish";
		break;
		case "tut":
		$language = "Turko-Tataric";
		break;
		case "twi":
		$language = "Twi";
		break;
		case "uga":
		$language = "Ugaritic";
		break;
		case "uig":
		$language = "Uigur";
		break;
		case "ukr":
		$language = "Ukrainian";
		break;
		case "ukr":
		$language = "Ruthenian (Ukrainian)";
		break;
		case "umb":
		$language = "Umbundu";
		break;
		case "umb":
		$language = "Mbundu (Bengela District; Umbundu)";
		break;
		case "umb":
		$language = "Nano (Umbundu)";
		break;
		case "und":
		$language = "Undetermined";
		break;
		case "urd":
		$language = "Urdu";
		break;
		case "uzb":
		$language = "Uzbek";
		break;
		case "vie":
		$language = "Vietnamese";
		break;
		case "vie":
		$language = "Annamese (Vietnamese)";
		break;
		case "vot":
		$language = "Votic";
		break;
		case "vot":
		$language = "Vote (Votic)";
		break;
		case "vot":
		$language = "Votian (Votic)";
		break;
		case "vot":
		$language = "Votish (Votic)";
		break;
		case "wak":
		$language = "Wakashan languages";
		break;
		case "wak":
		$language = "Bella Bella (Wakashan languages)";
		break;
		case "wak":
		$language = "Haisla (Wakashan languages)";
		break;
		case "wak":
		$language = "Heiltsuk (Wakashan languages)";
		break;
		case "wak":
		$language = "Kwakiutl (Wakashan languages)";
		break;
		case "wak":
		$language = "Nitinat (Wakashan languages)";
		break;
		case "wak":
		$language = "Nootka (Wakashan languages)";
		break;
		case "wal":
		$language = "Walamo";
		break;
		case "was":
		$language = "Washo";
		break;
		case "wel":
		$language = "Welsh";
		break;
		case "wen":
		$language = "Wendic";
		break;
		case "wen":
		$language = "Sorbian languages (Wendic)";
		break;
		case "wen":
		$language = "Sorbic (Wendic)";
		break;
		case "wen":
		$language = "Wendish (Wendic)";
		break;
		case "wol":
		$language = "Wolof";
		break;
		case "xho":
		$language = "Xhosa";
		break;
		case "xho":
		$language = "Isi-Xosa (Xhosa)";
		break;
		case "xho":
		$language = "Kafir (Xhosa)";
		break;
		case "xho":
		$language = "Xosa (Xhosa)";
		break;
		case "yao":
		$language = "Yao (Bantu)";
		break;
		case "yid":
		$language = "Yiddish";
		break;
		case "yid":
		$language = "Judaeo-German (Yiddish)";
		break;
		case "yor":
		$language = "Yoruba";
		break;
		case "zap":
		$language = "Zapotec";
		break;
		case "zen":
		$language = "Zenaga";
		break;
		case "zul":
		$language = "Zulu";
		break;
		case "zun":
		$language = "Zuni";
		break;
	}
	return $language;
}

$bibrecords = new File_MARC($data_file, File_MARC::SOURCE_FILE);
// Iterate through the retrieved records
$count = 0;
while ($record = $bibrecords->next()) 
{
	$count++;	
  fwrite($fh, "\n########## $count. ##########\n\n");	
  //if ($count % 50 == 0) exit;
	if ($count % 1000 == 0)
	{ 
		$date_time = date("l dS F Y h:i:s A");
		fwrite($fh, "$date_time: now processing record no. $count\n");
	}
  // Initialize vars for each record
  $vars = array('data', 'marc_ldr', 'marc_material_format', 'type_of_record', 'bibliographic_level', 'marc_001', 'marc_008_year', 'marc_008_lang', 'lang_full', 'marc_010', 'marc_020', 'marc_035_a', 'marc_050', 'marc_090', 'marc_100', 'marc_110', 'marc_111', 'marc_130', 'marc_240_a', 'marc_245_a', 'marc_245_b', 'marc_245_c', 'marc_245_num_non_filing_chars', 'marc_245_a_sort', 'marc_246_a', 'marc_250', 'marc_260', 'marc_260_a', 'marc_260_b', 'marc_260_c', 'marc_300_pages', 'marc_300_other', 'marc_300_dim', 'marc_440', 'marc_500', 'marc_504', 'marc_505', 'marc_600', 'marc_610', 'marc_611', 'marc_630', 'marc_648', 'marc_650', 'marc_651', 'marc_653', 'marc_654', 'marc_655', 'marc_656', 'marc_657', 'marc_658', 'marc_662', 'marc_690', 'marc_691', 'marc_692', 'marc_693', 'marc_695', 'marc_700', 'marc_710', 'marc_711', 'marc_730', 'marc_856');	
  foreach ($vars as $var)
 	{
 		$$var = "";
 	}  

  /*
  #####################################################
  # Get leader (LDR) -- get type of record and bibliographic level
  #####################################################
  */
  
  $marc_ldr = $record->getLeader();
  fwrite($fh, "marc_ldr: [$marc_ldr]\n");
	if (preg_match("/\d{5}[a-z]([a-z])([a-z])/", $marc_ldr, $match))
	{
		$ldr_06_indicator = $match[1];
		$ldr_07_indicator = $match[2];
		fwrite($fh, "ldr_06_indicator: [$ldr_06_indicator]\n");
		fwrite($fh, "ldr_07_indicator: [$ldr_07_indicator]\n");
		switch ($ldr_06_indicator)
		{
		    case	"a":
			 $type_of_record = "language material";
			 break;
		    case	"c":
			 $type_of_record = "notated music";
			 break;
		    case	"d":
			 $type_of_record = "manuscript notated musicl";
			 break;
		    case	"e":
			 $type_of_record = "cartographic material";
			 break;
		    case	"f":
			 $type_of_record = "manuscript cartographic material";
			 break;
		    case	"g":
			 $type_of_record = "projected medium";
			 break;
		    case	"i":
			 $type_of_record = "non-musical sound recording";
			 break;
		    case	"j":
			 $type_of_record = "musical sound recording";
			 break;
		    case	"k":
			 $type_of_record = "two_dimensional non-projectable graphic";
			 break;
		    case	"m":
			 $type_of_record = "computer file";
			 break;
		    case	"o":
			 $type_of_record = "kit";
			 break;
		    case	"p":
			 $type_of_record = "mixed materials";
			 break;
		    case	"r":
			 $type_of_record = "three-dimensional artifact or naturally occurring object";
			 break;
		    case	"t":
			 $type_of_record = "manuscript language material";
			 break;
			 default:
	 			break;
	 	}
		switch ($ldr_07_indicator)
		{
		    case	"a":
			 $bibliographic_level = "monographic component part";
			 break;
		    case	"b":
			 $bibliographic_level = "serial component part";
			 break;
		    case	"c":
			 $bibliographic_level = "collection";
			 break;
		    case	"d":
			 $bibliographic_level = "subunit";
			 break;
		    case	"i":
			 $bibliographic_level = "integrating resource";
			 break;
		    case	"m":
			 $bibliographic_level = "monograph / item";
			 break;
		    case	"s":
			 $bibliographic_level = "serial";
			 break;
	 	   default:
	 			break;
		}		
		$marc_material_format = "$type_of_record -- $bibliographic_level";
		fwrite($fh, "marc_material_format: [$marc_material_format]\n");
	}
  
  /*
  #####################################################
  # Get local control number (001) -- no subfields used in MARC; currently capturing only Harvard-formatted numbers
  #####################################################
  */
  
  if ($field_value = $record->getField('001'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		// All Hollis records are 9 digits long
		if (preg_match ("/(\d{9})/", $field_value->formatField(), $match))
		{
			$marc_001 = cleanup($match[1]);
			fwrite($fh, "marc_001: [$marc_001]\n");
		}
	}
	
  /*
  #####################################################
  # Get pub year and language (008) -- offsets 7 and 35 respectively
  #####################################################
  */
  
	if ($field_value = $record->getField('008'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		if (preg_match("/008\s+.{7}(.{4}).{24}(.{3})/", $field_value, $match))
		{
			$marc_008_year = cleanup($match[1]);
			$marc_008_lang = cleanup($match[2]);
			$lang_full = return_language($marc_008_lang);
			fwrite($fh, "marc_008_year: [$marc_008_year]\n");
			fwrite($fh, "marc_008_lang: [$marc_008_lang]\n");
			fwrite($fh, "lang_full: [$lang_full]\n");
		}
	}
	
  /*
  #####################################################
  # Get Library of Congress control number (010) -- subfield a
  #####################################################
  */
  
	if ($field_value = $record->getField('010'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		if ($subfield = $field_value->getSubfield('a'))
		{
			$data = $subfield->getData();
			$marc_010 = cleanup($data);
			fwrite($fh, "marc_010: [$marc_010]\n");
		}
	}
	
  /*
  #####################################################
  # Get ISBN (020) -- subfield a (stripped of non-digits, both 10- and 13-digit variants; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('020'))
	{
		foreach ($field_values as $field_value)
		{
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");
			if ($subfield = $field_value->getSubfield('a'))
			{
				$data = $subfield->getData();
				if (preg_match("/(\d{10,13})/", $data, $match))
				{
					$current_field = $match[1];
				}
			}
			$marc_020 .= "%%$current_field";			
		}
		$marc_020 = cleanup($marc_020);
		fwrite($fh, "marc_020: [$marc_020]\n");
	}
	
  /*
  #####################################################
  # Get OCLC number (035) -- subfield a
  #####################################################
  */
  
	if ($field_value = $record->getField('035'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		if ($subfield = $field_value->getSubfield('a'))
		{
			$data = $subfield->getData();
			$marc_035_a = cleanup($data);
			fwrite($fh, "marc_035_a: [$marc_035_a]\n");
		}
	}
	
  /*
  #####################################################
  # Get LC call number (050) -- all subfields; multi-value
  #####################################################
  */
  
	if ($field_values = $record->getFields('050'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_050 .= "%%$current_field";
		}
		$marc_050 = cleanup($marc_050);
		fwrite($fh, "marc_050: [$marc_050]\n");		
	}
	
  /*
  #####################################################
  # Get LC call number: alternate location ("local call number") (090) -- all subfields
  #####################################################
  */
  	
	if ($field_value = $record->getField('090'))
	{
		fwrite($fh, "field_value: [$field_value]\n");		
		$subfields = $field_value->getSubfields();
		foreach($subfields as $subfield)
		{
			if (preg_match("/\d/", $code = $subfield->getCode())) continue;
			$data = $subfield->getData();
			$marc_090 .= " $data";
		}
		$marc_090 = cleanup($marc_090);		
		fwrite($fh, "marc_090: [$marc_090]\n");
	}
	
  /*
  #####################################################
  # Get personal author (100) -- all subfields
  #####################################################
  */
  
	if ($field_value = $record->getField('100'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		$subfields = $field_value->getSubfields();
		foreach($subfields as $subfield)
		{
			if (preg_match("/[abcdq]/", $code = $subfield->getCode()))
			{
				$data = $subfield->getData();
				$marc_100 .= " $data";
			}
		}
		$marc_100 = cleanup($marc_100);
		fwrite($fh, "marc_100: [$marc_100]\n");	
	}
	
  /*
  #####################################################
  # Get corporate author (110) -- all subfields
  #####################################################
  */
  
	if ($field_value = $record->getField('110'))
	{
		fwrite($fh, "field_value: [$field_value]\n");		
		$subfields = $field_value->getSubfields();
		foreach($subfields as $subfield)
		{
			if (preg_match("/[abcd]/", $code = $subfield->getCode()))
			{
				$data = $subfield->getData();
				$marc_110 .= " $data";
			}
		}
		$marc_110 = cleanup($marc_110);
		fwrite($fh, "marc_110: [$marc_110]\n");
	}
	
  /*
  #####################################################
  # Get meeting author (111) -- all subfields
  #####################################################
  */
  
	if ($field_value = $record->getField('111'))
	{
		fwrite($fh, "field_value: [$field_value]\n");		
		$subfields = $field_value->getSubfields();
		foreach($subfields as $subfield)
		{
			if (preg_match("/[acdeq]/", $code = $subfield->getCode()))
			{
				$data = $subfield->getData();
				$marc_111 .= " $data";
			}
		}
		$marc_111 = cleanup($marc_111);		
		fwrite($fh, "marc_111: [$marc_111]\n");
	}

  /*
  #####################################################
  # Get uniform title (130) -- subfield a
  #####################################################
  */
	
	if ($field_value = $record->getField('130'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		if ($subfield = $field_value->getSubfield('a'))
		{
			$data = $subfield->getData();
			$marc_130 = $data;
			$marc_130 = cleanup($marc_130);
			fwrite($fh, "marc_130: [$marc_130]\n");
		}
	}	
	
  /*
  #####################################################
  # Get uniform title (240) -- subfield a
  #####################################################
  */
	
	if ($field_value = $record->getField('240'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		if ($subfield = $field_value->getSubfield('a'))
		{
			$data = $subfield->getData();
			$marc_240_a = $data;
			$marc_240_a = cleanup($marc_240_a);
			fwrite($fh, "marc_240_a: [$marc_240_a]\n");
		}
	}
		
  /*
  #####################################################
  # Get title (245) -- subfields a, b, and c; also get number of non-filing chars and use to generate sortable title format
  #####################################################
  */
  
	if ($field_value = $record->getField('245'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		if ($subfield = $field_value->getSubfield('a'))
		{
			$marc_245_a = cleanup($subfield->getData());
			fwrite($fh, "marc_245_a: [$marc_245_a]\n");
		}
		if ($subfield = $field_value->getSubfield('b'))
		{
			$marc_245_b = $subfield->getData();
			fwrite($fh, "marc_245_b: [$marc_245_b]\n");
		}	
		if ($subfield = $field_value->getSubfield('c'))
		{
			$marc_245_c = $subfield->getData();
			fwrite($fh, "marc_245_c: [$marc_245_c]\n");
		}
		if ($marc_245_num_non_filing_chars = $field_value->getIndicator(2))
		{
			fwrite($fh, "marc_245_num_non_filing_chars: [$marc_245_num_non_filing_chars]\n");
		}
	}
	// Generate version of title based on truncation according to no. of chars indicated in $marc_245_num_non_filing_chars
	// Set internal encoding to utf-8 (registers as iso-8859-1 otherwise) to ensure that initial filing-char number resolves to correct number of bytes in mb_substr()
	mb_internal_encoding("UTF-8");
	// fwrite($fh, mb_internal_encoding() . "\n");
	// Since upgrading from php 5.1.6 to 5.3.2, mbstring()'s 2nd param may no longer be a string, but must be long
	$marc_245_num_non_filing_chars_as_int = $marc_245_num_non_filing_chars;
	settype($marc_245_num_non_filing_chars_as_int, "integer");
	$marc_245_a_sort = trim(mb_substr($marc_245_a, $marc_245_num_non_filing_chars_as_int));
	fwrite($fh, "marc_245_a_sort: [$marc_245_a_sort]\n");
	
  /*
  #####################################################
  # Get varying form of title (246) -- subfield a; multi-valued
  #####################################################
  */
	
	if ($field_values = $record->getFields('246'))
	{
		foreach ($field_values as $field_value)
		{
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");
			if ($subfield = $field_value->getSubfield('a'))
			{
				$current_field = $subfield->getData();
			}
			$marc_246_a .= "%%$current_field";			
		}
		$marc_246_a = cleanup($marc_246_a);
		fwrite($fh, "marc_246_a: [$marc_246_a]\n");
	}
	
  /*
  #####################################################
  # Get edition (250) -- all subfields
  #####################################################
  */
  
	if ($field_value = $record->getField('250'))
	{
		$subfields = $field_value->getSubfields();
		foreach($subfields as $subfield)
		{
			if (preg_match("/\d/", $code = $subfield->getCode())) continue;
			$data = $subfield->getData();
			$marc_250 .= " $data";
		}
		$marc_250 = cleanup($marc_250);
		fwrite($fh, "marc_250: [$marc_250]\n");
	}
	
  /*
  #####################################################
  # Get imprint (260) -- subfields a, b, c
  #####################################################
  */
  
	if ($field_value = $record->getField('260'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		if ($subfield = $field_value->getSubfield('a'))
		{
			$marc_260_a = $subfield->getData();
			fwrite($fh, "marc_260_a: [$marc_260_a]\n");			
		}
		if ($subfield = $field_value->getSubfield('b'))
		{
			$marc_260_b = $subfield->getData();
			fwrite($fh, "marc_260_b: [$marc_260_b]\n");
		}	
		if ($subfield = $field_value->getSubfield('c'))
		{
			$marc_260_c = $subfield->getData();
			fwrite($fh, "marc_260_c: [$marc_260_c]\n");
		}
		$marc_260 = cleanup("$marc_260_a$marc_260_b$marc_260_c");
		fwrite($fh, "marc_260: [$marc_260]\n");
	}	
	
  /*
  #####################################################
  # Get physical description (300) -- subfields a, b, c
  #####################################################
  */
  
	if ($field_value = $record->getField('300'))
	{
		fwrite($fh, "field_value: [$field_value]\n");
		if ($subfield = $field_value->getSubfield('a'))
		{
			$marc_300_pages = cleanup($subfield->getData());
			fwrite($fh, "marc_300_pages: [$marc_300_pages]\n");
		}
		if ($subfield = $field_value->getSubfield('b'))
		{
			$marc_300_other = cleanup($subfield->getData());
			fwrite($fh, "marc_300_other: [$marc_300_other]\n");			
		}	
		if ($subfield = $field_value->getSubfield('c'))
		{
			$marc_300_dim = cleanup($subfield->getData());
			fwrite($fh, "marc_300_dim: [$marc_300_dim]\n");			
		}
	}	
	
  /*
  #####################################################
  # Get series (440) -- all subfields
  #####################################################
  */
	
	if ($field_values = $record->getFields('440'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_440 .= "%%$current_field";
		}
		$marc_440 = cleanup($marc_440);
		fwrite($fh, "marc_440: [$marc_440]\n");		
	}
		
  /*
  #####################################################
  # Get general note (500) -- all subfields; multi-valued
  #####################################################
  */
	
	if ($field_values = $record->getFields('500'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_500 .= "%%$current_field";
		}
		$marc_500 = cleanup($marc_500);
		fwrite($fh, "marc_500: [$marc_500]\n");		
	}
	
  /*
  #####################################################
  # Get bibliography note (504) -- all subfields; multi-valued
  #####################################################
  */
	
	if ($field_values = $record->getFields('504'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_504 .= "%%$current_field";
		}
		$marc_504 = cleanup($marc_504);
		fwrite($fh, "marc_504: [$marc_504]\n");		
	}
	
  /*
  #####################################################
  # Get formatted contents note (505) -- all subfields; multi-valued
  #####################################################
  */
		
	if ($field_values = $record->getFields('505'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_505 .= "%%$current_field";
		}
		$marc_505 = cleanup($marc_505);
		fwrite($fh, "marc_505: [$marc_505]\n");		
	}

  /*
  #####################################################
  # Get subject personal name (600) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('600'))
	{
		foreach ($field_values as $field_value)
		{
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				// Suppress uniform title instances from here -- these are harvested elsewhere
				if ($subfield->getCode() == 't')
				{
					$current_field = "";
					break;
				}
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_600 .= "$current_field%%";
		}
		$marc_600 = cleanup($marc_600);
		fwrite($fh, "marc_600: [$marc_600]\n");
	}					
	
  /*
  #####################################################
  # Get subject corporate name (610) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('610'))
	{
		foreach ($field_values as $field_value)
		{
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				// Suppress uniform title instances from here -- these are harvested elsewhere
				if ($subfield->getCode() == 't')
				{
					$current_field = "";
					break;
				}
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_610 .= "$current_field%%";
		}
		$marc_610 = cleanup($marc_610);
		fwrite($fh, "marc_610: [$marc_610]\n");
	}					
	
  /*
  #####################################################
  # Get subject meeting name (611) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('611'))
	{
		foreach ($field_values as $field_value)
		{
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				// Suppress uniform title instances from here -- these are harvested elsewhere
				if ($code = $subfield->getCode() == 't')
				{
					$current_field = "";
					break;
				}
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_611 .= "$current_field%%";
		}
		$marc_611 = cleanup($marc_611);
		fwrite($fh, "marc_611: [$marc_611]\n");
	}	

  /*
  #####################################################
  # Get subject uniform title (630) -- subfield a; multi-valued
  #####################################################
  */
		
	if ($field_values = $record->getFields('630'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/[a]/", $code = $subfield->getCode()))
				{
					$data = $subfield->getData();
					$current_field .= " $data";
				}
			}
			$marc_630 .= "%%$current_field";
		}
		$marc_630 = cleanup($marc_630);
		fwrite($fh, "marc_630: [$marc_630]\n");		
	}	
			
  /*
  #####################################################
  # Get subject chronological term (648) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('648'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_648 .= "%%$current_field";
		}
		$marc_648 = cleanup($marc_648);
		fwrite($fh, "marc_648: [$marc_648]\n");		
	}
		
  /*
  #####################################################
  # Get subject topical term (650) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('650'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_650 .= "%%$current_field";
		}
		$marc_650 = cleanup($marc_650);
		fwrite($fh, "marc_650: [$marc_650]\n");		
	}
		
  /*
  #####################################################
  # Get subject geographic name (651) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('651'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_651 .= "%%$current_field";
		}
		$marc_651 = cleanup($marc_651);
		fwrite($fh, "marc_651: [$marc_651]\n");		
	}
		
  /*
  #####################################################
  # Get subject uncontrolled index term (653) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('653'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_653 .= "%%$current_field";
		}
		$marc_653 = cleanup($marc_653);
		fwrite($fh, "marc_653: [$marc_653]\n");		
	}
		
  /*
  #####################################################
  # Get subject faceted topical term (654) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('654'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_654 .= "%%$current_field";
		}
		$marc_654 = cleanup($marc_654);
		fwrite($fh, "marc_654: [$marc_654]\n");		
	}
		
  /*
  #####################################################
  # Get subject genre (655) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('655'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_655 .= "%%$current_field";
		}
		$marc_655 = cleanup($marc_655);
		fwrite($fh, "marc_655: [$marc_655]\n");		
	}
		
  /*
  #####################################################
  # Get subject occupation (656) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('656'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_656 .= "%%$current_field";
		}
		$marc_656 = cleanup($marc_656);
		fwrite($fh, "marc_656: [$marc_656]\n");		
	}
		
  /*
  #####################################################
  # Get subject function (657) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('657'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_657 .= "%%$current_field";
		}
		$marc_657 = cleanup($marc_657);
		fwrite($fh, "marc_657: [$marc_657]\n");		
	}
		
  /*
  #####################################################
  # Get subject curriculum objective (658) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('658'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_658 .= "%%$current_field";
		}
		$marc_658 = cleanup($marc_658);
		fwrite($fh, "marc_658: [$marc_658]\n");		
	}
		
  /*
  #####################################################
  # Get subject hierarchical place name (662) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('662'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_662 .= "%%$current_field";
		}
		$marc_662 = cleanup($marc_662);
		fwrite($fh, "marc_662: [$marc_662]\n");		
	}
		
  /*
  #####################################################
  # Get local subject access field (690) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('690'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_690 .= "%%$current_field";
		}
		$marc_690 = cleanup($marc_690);
		fwrite($fh, "marc_690: [$marc_690]\n");		
	}
		
  /*
  #####################################################
  # Get local subject access field (691) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('691'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_691 .= "%%$current_field";
		}
		$marc_691 = cleanup($marc_691);
		fwrite($fh, "marc_691: [$marc_691]\n");		
	}
		
  /*
  #####################################################
  # Get local subject access field (692) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('692'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_692 .= "%%$current_field";
		}
		$marc_692 = cleanup($marc_692);
		fwrite($fh, "marc_692: [$marc_692]\n");		
	}
		
  /*
  #####################################################
  # Get local subject access field (693) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('693'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_693 .= "%%$current_field";
		}
		$marc_693 = cleanup($marc_693);
		fwrite($fh, "marc_693: [$marc_693]\n");		
	}
		
  /*
  #####################################################
  # Get local subject access field (695) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('695'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_695 .= "%%$current_field";
		}
		$marc_695 = cleanup($marc_695);
		fwrite($fh, "marc_695: [$marc_695]\n");		
	}
			
  /*
  #####################################################
  # Get added entry personal name (700) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('700'))
	{
		foreach ($field_values as $field_value)
		{
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				// Suppress uniform title instances from here -- these are harvested elsewhere
				if ($code = $subfield->getCode() == 't')
				{
					$current_field = "";
					break;
				}
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_700 .= "$current_field%%";
		}
		$marc_700 = cleanup($marc_700);
		fwrite($fh, "marc_700: [$marc_700]\n");
	}					
					
  /*
  #####################################################
  # Get added entry corporate name (710) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('710'))
	{
		foreach ($field_values as $field_value)
		{
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				// Suppress uniform title instances from here -- these are harvested elsewhere
				if ($code = $subfield->getCode() == 't')
				{
					$current_field = "";
					break;
				}
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_710 .= "$current_field%%";
		}
		$marc_710 = cleanup($marc_710);
		fwrite($fh, "marc_710: [$marc_710]\n");
	}	
					
  /*
  #####################################################
  # Get added entry meeting name (711) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('711'))
	{
		foreach ($field_values as $field_value)
		{
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				// Suppress uniform title instances from here -- these are harvested elsewhere
				if ($subfield->getCode() == 't')
				{
					$current_field = "";
					break;
				}
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_711 .= "$current_field%%";
		}
		$marc_711 = cleanup($marc_711);
		fwrite($fh, "marc_711: [$marc_711]\n");
	}					

  /*
  #####################################################
  # Get added entry uniform title (730) -- subfield a; multi-valued
  #####################################################
  */
	
	if ($field_values = $record->getFields('730'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/[a]/", $code = $subfield->getCode()))
				{
					$data = $subfield->getData();
					$current_field .= " $data";					
				}
			}
			$marc_730 .= "%%$current_field";
		}
		$marc_730 = cleanup($marc_730);
		fwrite($fh, "marc_730: [$marc_730]\n");		
	}	
	
  /*
  #####################################################
  # Get electonic access info (856) -- all subfields; multi-valued
  #####################################################
  */
  
	if ($field_values = $record->getFields('856'))
	{
		foreach ($field_values as $field_value)
		{		
			$current_field = "";
			fwrite($fh, "field_value: [$field_value]\n");		
			$subfields = $field_value->getSubfields();
			foreach($subfields as $subfield)
			{
				if (preg_match("/\d/", $code = $subfield->getCode())) continue;
				$data = $subfield->getData();
				$current_field .= " $data";
			}
			$marc_856 .= "%%$current_field";
		}
		$marc_856 = cleanup($marc_856);
		fwrite($fh, "marc_856: [$marc_856]\n");		
	}


	/*
	#####################################################
	# Write out data to bib-data table
	#####################################################
	*/

	// Escape in order to prevent quote problems
  foreach ($vars as $var)
 	{
 		// Check to see if there is any content in current var
 		if ($$var)
 		{
 			$$var = addslashes($$var);
 		}
 	}  
	
	$insert_query = 
	"INSERT INTO lc_raw.harvard_edu_bib_data_raw
	(
		MarcLDR, MarcMaterialFormat, Marc001, Marc008Year, Marc008Lang, LangFull, Marc010, Marc020, Marc035A, Marc050, Marc090, Marc100, Marc110, Marc111, Marc130, Marc240A, Marc245A, Marc245NumNonFilingChars, Marc245ASort, Marc245B, Marc245C, Marc246A, Marc250, Marc260, Marc260A, Marc260B, Marc260C, Marc300Pages, Marc300Other, Marc300Dim, Marc440, Marc500, Marc504, Marc505, Marc600, Marc610, Marc611, Marc630, Marc648, Marc650, Marc651, Marc653, Marc654, Marc655, Marc656, Marc657, Marc658, Marc662, Marc690, Marc691, Marc692, Marc693, Marc695, Marc700, Marc710, Marc711, Marc730, Marc856, DataID, DataSource, RecordCreated
	)
	VALUES
	(		
		'$marc_ldr', '$marc_material_format', '$marc_001', '$marc_008_year', '$marc_008_lang', '$lang_full', '$marc_010', '$marc_020', '$marc_035_a', '$marc_050', '$marc_090', '$marc_100', '$marc_110', '$marc_111', '$marc_130', '$marc_240_a', '$marc_245_a', '$marc_245_num_non_filing_chars', '$marc_245_a_sort', '$marc_245_b', '$marc_245_c', '$marc_246_a', '$marc_250', '$marc_260', '$marc_260_a', '$marc_260_b', '$marc_260_c', '$marc_300_pages', '$marc_300_other', '$marc_300_dim', '$marc_440', '$marc_500', '$marc_504', '$marc_505', '$marc_600', '$marc_610', '$marc_611', '$marc_630', '$marc_648', '$marc_650', '$marc_651', '$marc_653', '$marc_654', '$marc_655', '$marc_656', '$marc_657', '$marc_658', '$marc_662', '$marc_690', '$marc_691', '$marc_692', '$marc_693', '$marc_695', '$marc_700', '$marc_710', '$marc_711', '$marc_730', '$marc_856', '$data_id', '$data_source', now()
	)";
	//fwrite($fh, "insert_query: [$insert_query]\n");
	$result = mysql_query($insert_query, $link);
	if (!$result) fwrite($fh, mysql_errno($link) . ":" . mysql_error($link) . "\n");
	
}

$date_time = date("l dS F Y h:i:s A");
fwrite($fh, "$date_time: end\n\n");

?>