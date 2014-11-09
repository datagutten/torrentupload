<?Php
require_once 'tvdb/tvdb.php';
require_once 'functions_description.php';
require_once 'config.php';
require 'someimage/someimage.php';
$imagehost=new someimage;
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
	$options = getopt("",array('tvdb:','nomediainfo','nosnapshots'));
	if(!isset($argv[1]))
		die('Filen det skal lages beskrivelse for må spesifiseres på kommandolinjen (php descriptionmaker.php dinfil.mkv');
	else
		$file=$argv[1];
	if(isset($options['tvdb']))
		$tvdb_id=$options['tvdb'];
	end($argv);
	$file=$argv[key($argv)];
}

if(!file_exists($file))
	die("Finner ikke filen $file\n");

$info = pathinfo($file);

$release=$info['filename']; //The file name without extension is the name of the release
if(is_dir($file))
{
	$dir=scandir($file);
	natsort($dir); //Sort by name to find first episode in a series
	foreach($dir as $subfile)
	{
		$finfo = new finfo(FILEINFO_MIME);
		$mime=$finfo->file($file.'/'.$subfile);
		if(substr($mime,0,5)=='video') //Find first video file
		{
			$file=$file.'/'.$subfile;
			break;
		}
	}
}
$description='';

if(!isset($options['nosnapshots']))
{
	echo "Creating snapshots\n";
	$snapshots=$desc->snapshots($file);

	if($snapshots!==false)
	{
		echo "Uploading snapshots\n";
		foreach ($snapshots as $key=>$snapshot)
		{
			$snapshotlinks[$key]=$imagehost->upload($snapshot);
		}
	}
	else
		echo "Could not create snapshots\n";
}
if(!empty($tvdb_id))
{
	$tvdb_series=$tvdb->findseries($tvdb_id);
	$episodedata=$tvdb_series;
}

if(isset($episodeinfo) || ($episodeinfo=$desc->serieinfo($release))!==false) //Check if the name contains season and episode number
{
	if(isset($tvdb_id))
		$episodeinfo[1]=$tvdb_id;
	$episodedata=$tvdb->finnepisode($episodeinfo[1],$episodeinfo[2],$episodeinfo[3]); //Get information from TheTVDB
}
elseif(preg_match('^(.+?) - (.+)^',$release,$result)) //Check if the name is in the style [series] - [episode name]
{
	if(isset($tvdb_id))
		$result[1]=$tvdb_id;
	$serie=$tvdb->findseries($result[1]);
	if($serie!==false)
	{
		if(isset($argv[2]))
			$result[2]=$argv[2]; //Get the series name from the command line
		$episodedata=$tvdb->finnepisodenavn($result[2],$serie);
	}
}

$banner='[b]'.$release.'[/b]'; //In case the series is not found or don't have a banner, use the relase name as banner	
if(isset($episodedata) && $episodedata!==false) //The episode is found on TheTVDB, get information
{
	$episodelink=$tvdb->link($episodedata);
	if(!empty($episodedata['Series']['banner']))
	{
		$bannerimage_tvdb="http://thetvdb.com/banners/".$episodedata['Series']['banner'];
		$upload_banner=$imagehost->upload($bannerimage_tvdb); //Upload the banner
		$banner='[url='.$episodelink.'][img]'.$upload_banner['image'].'[/img][/url]';
	}
	if(!empty($episodedata['Episode']['EpisodeName'])) //Check if the episode got a name
		$description.="[b]{$episodedata['Episode']['EpisodeName']}[/b]\n";

	if(!empty($episodedata['Episode']['Overview'])) //If the episode don't got an overview it will be an empty array
		$description.=$episodedata['Episode']['Overview'];
	elseif(!empty($episodedata['Series']['Overview'])) //Use overview for the series
		$description.=$episodedata['Series']['Overview'];

}

if(file_exists($info['dirname'].'/common.nfo')) //Check if there is a file with common information for the series
	$description.="\n\n".file_get_contents($info['dirname'].'/common.nfo');


if(!isset($options['nomediainfo']) && ($mediainfo=$desc->mediainfo($file))!==false)
	$description.="\nMediainfo:\n".'[pre]'.$mediainfo.'[/pre]';

else
	$mediainfo='';

if(isset($snapshotlinks))
	$description=$desc->description($snapshotlinks,$banner."\n".$description."\n");
else
	$description=$banner."\n".$description."\n";

file_put_contents($info['dirname'].'/'.$info['filename'].'.txt',$description); //Write the complete description to a file
file_put_contents($info['dirname'].'/'.$info['filename'].'.mediainfo',$desc->simplemediainfo($file)); //Write mediainfo to a file

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