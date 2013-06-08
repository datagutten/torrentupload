<?Php
//ob_start();
$filepath=getcwd();
$scriptpath='/home/upload/';
chdir($scriptpath);
//die("\n".$filepath."\n");
require_once 'tvdb.php';
require_once'mediainfo.php';
require_once 'functions_description.php';
$tvdb=new tvdb;
$desc=new description;

if(isset($path))
	$include=true;
else
{
	if(basename($argv[1])==$argv[1]) //Sjekk om det er angitt en relativ bane
		$path=$filepath.'/'.$argv[1];
	else
		$path=$argv[1];
	$include=false;
	require_once 'dependcheck.php';
	require_once 'config.php';
	require_once 'functions.php';

	
}
depend(array('mplayer','mediainfo'));
$tvdb->apikey=$tvdb_key;
if(!file_exists($path))
	die("Finner ikke filen $path\n");

$info = pathinfo($path);

$release = $info['filename']; //Finn navnet på release utifra path


echo "Lager snapshots\n";
$snapshots=$desc->snapshots($path);
//$snapshots=false;
$description='';

if($snapshots!==false)
{
	foreach ($snapshots as $snapshot)
	{
		$upload=imgur_upload_dupecheck($snapshot,$imgur_key);
		$screenshotdata[]=$upload;
	}
	echo "Laster opp snapshots\n";
}
else
	echo "Klarte ikke å lage snapshots\n";

if(preg_match('^(.+) - (.+)\.^U',$info['basename'],$result))
{
	$serie=$tvdb->hentserie($result[1]);
	if(isset($argv[2]))
		$result[2]=$argv[2]; //Hent tittel fra $argv
	$episodedata=$tvdb->finnepisodenavn($result[2],$serie);
	//var_dump($episodedata);
	//die();
}
elseif(isset($episodeinfo) || $episodeinfo=serieinfo($release)!==false) //Finn serienavnet
	$episodedata=$tvdb->finnepisode($episodeinfo[1],$episodeinfo[2],$episodeinfo[3]); //Hent informasjon om episoden fra tvdb

if(isset($episodedata) && $episodedata!==false)
{
	$banner="http://thetvdb.com/banners/".$episodedata['Series']['banner'];
	$localfile='tvdb_cache/banner/'.basename($banner);
	if(!file_exists($localfile))
		copy($banner,$localfile); //Last ned banner til lokal fil

	$bannerdata=imgur_upload_dupecheck($localfile,$imgur_key); //Last opp på imgur
	
	if($tvdb_cache===false)
		unlink($tmpfile); //Slett fra /tmp
}

	if($episodedata['Episode']['EpisodeName']!='') //Sjekk om episoden har et navn
		$description.="[b]{$episodedata['Episode']['EpisodeName']}[/b]\n";


	if(!is_array($episodedata['Episode']['Overview'])) //Er det ikke noe overview til episoden vil den komme opp som et tomt array
		$description.=$episodedata['Episode']['Overview'];



if(file_exists($info['dirname'].'/common.nfo')) //Se om det ligger en fil med felles info i mappen med releasen
	$description.="\n\n".file_get_contents($info['dirname'].'/common.nfo');
if(file_exists($descriptionfile="description/description_".str_replace(':','',$release).'.txt'))
	$bannerdata=file_get_contents($descriptionfile);
if(!isset($bannerdata)) //Hvis det ikke er funnet noe bannerbilde, sett banner til releasens navn
	$bannerdata=$release;

$mediainfo=mediainfo($path);

$description=description($screenshotdata,$bannerdata,$description."\n".'[pre]'.$mediainfo.'[/pre]'); //All info er klar så beskrivelsen kan settes sammen
if(isset($_SERVER['HTTP_USER_AGENT'])) //Sjekk om scriptet kjøres i nettleser eller på kommandolinje
{
	echo '<br>';
	$showdescription=preg_replace("^\[img\](.*)\[/img\]^U",'<img src="$1" />',$description);
	$showdescription=preg_replace("^\[pre\](.*)\[/pre\]^U",'<tt><nobr>$1</nobr></tt>',$showdescription);
	
	echo nl2br($showdescription);
	echo "<p><textarea>".$description."</textarea></p>";
}
else
{
	echo $description;
	file_put_contents($info['dirname'].'/'.$info['filename'].'.nfo',$description);
	echo "\n";
}
//echo nl2br(ob_get_flush());