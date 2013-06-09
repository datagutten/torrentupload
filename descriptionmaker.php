<?Php
require_once 'tvdb.php';
require_once 'functions_description.php';
require_once 'config.php';
require 'imgur.php';
$imgur = new Imgur($api_key, $api_secret);

$tvdb=new tvdb($tvdb_key);
$desc=new description;

if(isset($_SERVER['HTTP_USER_AGENT'])) //Sjekk om scriptet kjøres i nettleser eller på kommandolinje
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

$release = $info['filename']; //Finn navnet på release utifra path

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

if(preg_match('^(.+) - (.+)\.^U',$info['basename'],$result))
{
	$serie=$tvdb->hentserie($result[1]);
	if($serie!==false)
	{
		if(isset($argv[2]))
			$result[2]=$argv[2]; //Hent tittel fra $argv
		$episodedata=$tvdb->finnepisodenavn($result[2],$serie);
	}
}
elseif(isset($episodeinfo) || $episodeinfo=$desc->serieinfo($release)!==false) //Finn serienavnet
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


	if($episodedata['Episode']['EpisodeName']!='') //Sjekk om episoden har et navn
		$description.="[b]{$episodedata['Episode']['EpisodeName']}[/b]\n";

	if(!is_array($episodedata['Episode']['Overview'])) //Er det ikke noe overview til episoden vil den komme opp som et tomt array
		$description.=$episodedata['Episode']['Overview'];

}

if(file_exists($info['dirname'].'/common.nfo')) //Se om det ligger en fil med felles info i mappen med releasen
	$description.="\n\n".file_get_contents($info['dirname'].'/common.nfo');
if(file_exists($descriptionfile="description/description_".str_replace(':','',$release).'.txt'))
	$bannerdata=file_get_contents($descriptionfile);
if(!isset($bannerdata)) //Hvis det ikke er funnet noe bannerbilde, sett banner til releasens navn
	$bannerdata=$release;

$mediainfo=$desc->mediainfo($file);

$description=$desc->description($snapshotlinks,$bannerdata,$description."\nMediainfo:\n".'[pre]'.$mediainfo.'[/pre]'); //All info er klar så beskrivelsen kan settes sammen
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