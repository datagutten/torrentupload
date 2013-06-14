<?Php
chdir(dirname(realpath(__FILE__))); //Change to the script folder
require_once 'config.php';
require 'torrent-rw/Torrent.php';

require 'tools/dependcheck.php';
$depend=new dependcheck;
if($depend->depend('buildtorrent')!==true)
	die("Buildtorrent is required to make torrents");
require 'functions_upload.php';
$upload=new upload;


if(isset($argv[1]))
	$path=$argv[1];
else
	$path=$_GET['file'];

if(!file_exists($path))
	die("Can not find $path\n");
$torrent=new torrent($path);

$pathinfo=pathinfo($path);

$release=$pathinfo['filename'];
$basefile=$pathinfo['dirname'].'/'.$pathinfo['filename'];
//$torrent->save($release.'.torrent');

$upload->login();

if (!file_exists("$torrent_file_dir/$release.torrent") && !file_exists($basefile.'.torrent'))
{
	echo "Creating torrent\n";
	echo shell_exec($cmd="buildtorrent -p1 -L 41941304 -a http://jalla.com \"$path\" \"$torrent_file_dir/$release.torrent\" 2>&1");
	if (!file_exists("$torrent_file_dir/$release.torrent"))
		die("Torrent creation failed\n$cmd\n");
}
else
	echo "Torrent is already created\n";
//die($release."\n");


echo "Uploading torrent\n";

//$postfields['descr']='[imgw]'.$poster."[/imgw]\n[url=$cleanlink]{$releasename}[/url]\n\nRippet fra bokhylla.no";
//$postfields['descr']=utf8_decode($postfields['descr']);

//Some site specific fields
$postfields['nfopos']='top';
$postfields['scene']='no';
$postfields['main_cat']=6; //6=Tidsskrift
$postfields['sub1_cat']=0; //N/A
$postfields['sub3_cat']=37; //37=e-bøker, 38=Blader
$postfields['sub2_cat']=0; //N/A
$upload_return=$upload->upload($release,file_get_contents($basefile.'.nfo'),$basefile.'.torrent',$postfields);
echo $upload_return."\n";
echo $upload->uploadhandler($upload_return,$release);


//$upload_return=upload($release,file_get_contents($release.'.nfo'),"$torrent_file_dir/$release.torrent");
//$upload->uploadhandler($upload_return,$release);
//file_put_contents("upload_$release.txt",$upload_return);

echo "\n";
