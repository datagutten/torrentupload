<?Php
chdir(dirname(realpath(__FILE__))); //Bytt til mappen scriptet ligger i så relative filbaner blir riktige
require_once 'config.php';
require_once 'functions.php';
require_once 'ftp.php';
require_once 'dependcheck.php';
depend('buildtorrent');
require 'functions_upload.php';
$upload=new upload;


if(isset($argv[1]))
	$path=$argv[1];
else
	$path=$_GET['file'];

if(!file_exists($path))
	die("Finner ikke filen $path\n");

require_once 'descriptionmaker.php';

echo "Logger inn\n";
post($site_url."/takelogin.php",array("username" => $username, "password" => $password),'cookies.txt',$site_url."/hei.php");

if (!file_exists("$torrent_file_dir/$release.torrent"))
{
	echo "Lager torrent\n";
	echo shell_exec($cmd="buildtorrent -p1 -L 41941304 -a http://jalla.com \"$path\" \"$torrent_file_dir/$release.torrent\" 2>&1");
}
else
	echo "Torrent eksisterer\n";

if (!file_exists("$torrent_file_dir/$release.torrent"))
	die("Oppretting av torrent feilet\n$cmd\n");

echo "Laster opp torrent\n";
$upload_return=upload($release,$description,"$torrent_file_dir/$release.torrent");
$upload->uploadhandler($upload_return,$release);
//file_put_contents("upload_$release.txt",$upload_return);

echo "\n";
