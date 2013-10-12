<?Php
require_once 'tvdb.php';
require_once 'functions_description.php';
require_once 'config.php';
require 'imgur.php';
$imgur = new Imgur($api_key, $api_secret);
$tvdb=new tvdb($tvdb_key);
$desc=new description;

if(isset($_SERVER['HTTP_USER_AGENT'])) //Check if the script is running in a browser
{
	$mode='browser';
	if(!isset($_GET['fil']))
		die('Filen det skal lages beskrivelse for må spesifiseres som GET parameter "fil" (descriptionmaker.php?fil=dinfil.mkv)');
	else
		$file=$_GET['fil'];
}
else
{
	$mode='console';
	if(!isset($argv[1]))
		die('Filen det skal lages beskrivelse for må spesifiseres på kommandolinjen (php descriptionmaker.php dinfil.mkv');
	else
		$file=$argv[1];
}

if(!file_exists($file))
	die("Finner ikke filen $file\n");

$info = pathinfo($file);

$release=$info['filename']; //The file name without extension is the name of the release

echo "Lager snapshots\n";
$snapshots=$desc->snapshots($file);

$description='';

if($snapshots!==false)
{
	echo "Laster opp snapshots\n";
	foreach ($snapshots as $key=>$snapshot)
	{
		$upload=$imgur->upload_dupecheck($snapshot,$imgur_key);
		$snapshotlinks[$key]['image']=$upload['data']['link'];
		$snapshotlinks[$key]['thumbnail']=$imgur->thumbnail($upload['data']['link'],'s');
	}
}
else
	echo "Klarte ikke å lage snapshots\n";

if(preg_match('^(.+?) - (.+)^',$release,$result)) //Check if the name is in the style [series] - [episode name]
{
	$serie=$tvdb->hentserie($result[1]);
	if($serie!==false)
	{
		if(isset($argv[2]))
			$result[2]=$argv[2]; //Get the series name from the command line
		$episodedata=$tvdb->finnepisodenavn($result[2],$serie);
	}
}
elseif(isset($episodeinfo) || ($episodeinfo=$desc->serieinfo($release))!==false) //Check if the name contains season and episode number
	$episodedata=$tvdb->finnepisode($episodeinfo[1],$episodeinfo[2],$episodeinfo[3]); //Get information from TheTVDB

$banner='[b]'.$release.'[/b]'; //In case the series is not found or don't have a banner, use the relase name as banner	
if(isset($episodedata) && $episodedata!==false) //The episode is found on TheTVDB, get information
{
	if(!is_array($episodedata['Series']['banner']))
	{
		$bannerimage_tvdb="http://thetvdb.com/banners/".$episodedata['Series']['banner'];
		$imgur_banner=$imgur->upload_dupecheck($bannerimage_tvdb); //Upload the banner to imgur
		$bannerimage=$imgur_banner['data']['link'];
		$banner='[img]'.$bannerimage.'[/img]';
	}
	if($episodedata['Episode']['EpisodeName']!='') //Check if the episode got a name
		$description.="[b]{$episodedata['Episode']['EpisodeName']}[/b]\n";

	if(!is_array($episodedata['Episode']['Overview'])) //If the episode don't got an overview it will be an empty array
		$description.=$episodedata['Episode']['Overview'];
}

if(file_exists($info['dirname'].'/common.nfo')) //Check if there is a file with common information for the series
	$description.="\n\n".file_get_contents($info['dirname'].'/common.nfo');


$mediainfo=$desc->mediainfo($file); //Get mediainfo

$description=$desc->description($snapshotlinks,$banner."\n".$description."\n\nMediainfo:\n".'[pre]'.$mediainfo.'[/pre]');
file_put_contents($info['dirname'].'/'.$info['filename'].'.nfo',$description); //Write the complete description to a file

if($mode=='browser') //Display the description in a browser
{
	echo '<br>';
	$showdescription=preg_replace("^\[img\](.*)\[/img\]^U",'<img src="$1" />',$description);
	$showdescription=preg_replace("^\[pre\](.*)\[/pre\]^U",'<tt><nobr>$1</nobr></tt>',$showdescription);
	
	echo nl2br($showdescription);
	echo "<p><textarea>".$description."</textarea></p>";
}
else //Else display it on a console
	echo $description."\n";

?>