<?Php
chdir(dirname(realpath(__FILE__))); //Change to the script folder
require_once 'config.php';

require_once 'tools/dependcheck.php';
$depend=new dependcheck;
if($depend->depend('buildtorrent')!==true)
	die("Buildtorrent is required to make torrents");
require 'functions_upload.php';
$upload=new upload;

if(isset($argv[1]))
	$path=$argv[1];
else
	$path=$_GET['file'];
if(!isset($argv[2]))
	die("Specify template as the second command line parameter\n");

if(!file_exists($path))
	die("Can not find $path\n");
//$torrent=new torrent($path);

$pathinfo=pathinfo($path);

$release=$pathinfo['filename'];
$basefile=$pathinfo['dirname'].'/'.$pathinfo['filename'];
//$torrent->save($release.'.torrent');

$upload->login();
$torrentfile=$pathinfo['dirname'].'/'.$upload->cleanfilename($pathinfo['filename']).'.torrent';
var_dump($torrentfile);
if (!file_exists($torrentfile))
{
	echo "Creating torrent\n";
	echo shell_exec($cmd="buildtorrent -p1 -L 41941304 -a http://jalla.com \"$path\" \"$torrentfile\" 2>&1");
	if (!file_exists($torrentfile))
		die("Torrent creation failed\n$cmd\n");
}
else
	echo "Torrent $torrentfile is already created\n";
if(!file_exists($basefile.'.txt'))
	trigger_error("Could not find description file",E_USER_ERROR);
else
	$description=file_get_contents($basefile.'.txt');
echo "Uploading torrent\n";

if(file_exists($templatefile="templates/{$argv[2]}.json"))
{
	$template=json_decode(file_get_contents($templatefile),true);
	if(isset($template['descriptionaddon']))
		$description.="\n".$template['descriptionaddon'];
}
else
	die("Invalid template\n");
if(file_exists($basefile.'.mediainfo'))
	$mediainfo=file_get_contents($basefile.'.mediainfo');
else
	$mediainfo='';

//$upload_return=$upload->upload($release,$description,$torrentfile,$template);
$postdata=$upload->buildupload($argv[2],$torrentfile,$release,$description,$mediainfo);
$upload_return=$upload->sendupload($postdata);
//print_r(array_keys($postdata));
//echo $upload_return."\n";
$torrentfile=$upload->uploadhandler($upload_return,$release);
if($torrentfile===false)
	echo $upload->error."\n";

//$upload_return=upload($release,file_get_contents($release.'.nfo'),"$torrent_file_dir/$release.torrent");
//$upload->uploadhandler($upload_return,$release);
//file_put_contents("upload_$release.txt",$upload_return);

echo "\n";
