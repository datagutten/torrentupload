<?php
require_once $scriptpath.'imgur.php';
require_once $scriptpath.'postget.php';
require_once $scriptpath.'config.php';
require_once $scriptpath.'ftp.php';
function snapshots($file,$times=array(65,300,600,1000))
{
	global $scriptpath;
	$snapshots=array();
	foreach ($times as $time)
	{
		if(!file_exists($snapshotfile=$scriptpath."snapshots/snapshot_".basename($file)."_$time.png"))
		{
			shell_exec($cmd="mplayer -quiet -ss $time -vo png:z=9 -ao null -zoom -frames 1 \"$file\" >/dev/null 2>&1");	
			//die($cmd);
			if(file_exists('00000001.png'))
				rename('00000001.png',$snapshots[]=$snapshotfile);
		}
		else
			$snapshots[]=$snapshotfile;
		
	}
	if(!isset($snapshots))
		$snapshots=false;
return $snapshots; //Returnerer et array med bildenes filnavn
}
function description($screenshotdata,$bannerdata,$description)
{
	if(is_array($bannerdata)) //Hvis bannerdata er et array er bilde funnet på tvdb. 
		$banner="[img]{$bannerdata['upload']['links']['original']}[/img]"; //Lag banner
	else //Ellers er det en tittel i ren tekst
		$banner=$bannerdata;
	$screens=''; //Lag variabelen screens for å unngå warning
	foreach ($screenshotdata as $screenshot) //Lag screenshots
		$screens .= "[url={$screenshot['upload']['links']['original']}][img]{$screenshot['upload']['links']['small_square']}[/img][/url]";
	return $banner."\n".$description."\n".$screens; //Sett sammen banner, beskrivelse og screenshots
}
function sendupload_temp($release,$description,$torrent,$parameters=false) //Send opplastingen til siden
{
	global $site_url;
	//Noen hardkodede paramatere til å begynne med

$postdata=array(
                       'MAX_FILE_SIZE' => "3000000",
                       'file' => '@'.$torrent,
                       'filetype' => "2",
                       'name' => $release,
                       //'#nfo' => $nfo,
                       /*'scenerelease' => $scene,
                       'descr' => $description,
                       'main_cat' => $main_cat,
					   'sub1_cat' => $sub1_cat,
					   'sub3_cat' => $sub3_cat,					   
					   'sub2_cat' => $sub2_cat,*/					   
                       'anonym' => "yes");
	$postadata=array_merge($parameters,$postdata);
	print_r($postdata);
	
	return post("$site_url/takeupload.php",$postdata,'cookies.txt',$site_url."/upload.php");
}
function sendupload($postdata) //Send opplastingen til siden
{
	global $site_url;

/*$postdata=array(                       
                       'file' => '@'.$torrent,
                       'filetype' => "2",
                       'name' => $release,);*/

	print_r($postdata);
	$postdata['anonym']="yes";
	return post("$site_url/takeupload.php",$postdata,'cookies.txt',$site_url."/upload.php");
}

function serieinfo($release) //Henter serie og episodeinfo fra releasenavn
{
	if (preg_match('^(.+?)S*([0-9]*)EP*([0-9]+)^',$release,$serieinfo)) //Finn sesong og episode
	{
		$serieinfo[1]=trim(str_replace('.',' ',$serieinfo[1])); //trim serienavn og erstatt . med mellomrom
		if($serieinfo[2]=='') //Hvis det ikke er oppgitt sesong, sett sesong til 1
			$serieinfo[2]=1;
	
	}
	else
		$serieinfo=false;
	return $serieinfo; //1=serienavn, 2=sesong
}

function upload($release,$description,$torrent)
{
	
		$parameters['scene']='no';
		$parameters['main_cat']=2;
		$parameters['sub1_cat']=9;
		$parameters['sub3_cat']=29; //37=e-bøker
		$parameters['sub2_cat']=20;
		

	return sendupload_temp($release,$description,$torrent,$parameters);

}
function uploadhandler($upload,$release)
{
	global $site_url,$torrent_auto_dir,$ftp_host,$ftp_user,$ftp_password;
	//Håndter feilet opplasting
	if(preg_match('^Mislykket opplasting.*\<p\>(.*)\</p\>^sU',$upload,$result))
		die('Feil: '.$result[1]);
	elseif(strpos($upload,'En torrent med lignende innhold')!==false)
		die("Allerede lastet opp\n");
	else
	{
		preg_match('^(download\.php\?id=[0-9]+)\"^',$upload,$file); //Finn torrentfilnavnet
		echo "Laster ned torrent\n";
		
		file_put_contents("/tmp/$release.torrent",get($site_url."/".$file[1],'cookies.txt',$site_url."/uploaded.php")); //Last ned torrent
		echo "Laster opp torrent til seedbox\n";
		ftp_upload_torrent("/tmp/$release.torrent",$ftp_host,$ftp_user,$ftp_password);
		copy("/tmp/$release.torrent","$torrent_auto_dir/$release.torrent");
		unlink("/tmp/$release.torrent");
	}
	file_put_contents("upload_$release.txt",$upload);	
}

?>