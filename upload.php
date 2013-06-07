<?Php
require_once 'config.php';
require_once 'functions.php';
require_once 'ftp.php';
require_once 'dependcheck.php';
depend('buildtorrent');

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
$upload=upload($release,$description,"$torrent_file_dir/$release.torrent");

//Håndter feilet opplasting
if(preg_match('^Mislykket opplasting.*\<p\>(.*)\</p\>^sU',$upload,$result))
	die('Feil: '.$result[1]);
else
{
	preg_match('^(download\.php.*\.torrent)\"^',$upload,$file); //Finn torrentfilnavnet
	echo "Laster ned torrent\n";
	file_put_contents("$torrent_auto_dir/$release.torrent",get($site_url."/".$file[1],'cookies.txt',$site_url."/uploaded.php")); //Last ned torrent
	//echo "Laster opp torrent til seedbox\n";
	//ftp_upload_torrent("$torrent_auto_dir/$release.torrent",$ftp_host,$ftp_user,$ftp_password));

}

//file_put_contents("upload_$release.txt",$upload);

echo "\n";
