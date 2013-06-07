<?php
require_once 'imgur.php';
require_once 'postget.php';
require_once 'config.php';
require_once 'ftp.php';

function snapshots($file,$times=array(65,300,600,1000))
{
	$snapshots=array();
	foreach ($times as $time)
	{
		if(!file_exists($snapshotfile="snapshots/snapshot_".basename($file)."_$time.png"))
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
?>