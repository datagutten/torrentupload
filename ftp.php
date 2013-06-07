<?Php
function ftp_upload_torrent($torrent,$host,$username,$password)
{
	$basename=basename($torrent);
	$conn_id=ftp_connect($host) or die("Couldn't connect to ftp");
	ftp_login($conn_id, $username, $password) or die ("Couldn't log on");
	
	if (ftp_put($conn_id, "/home/autoload/$basename", $torrent, FTP_ASCII)) {
	 echo "successfully uploaded $basename\n";
	} else {
	 echo "There was a problem while uploading $basename\n";
	}
}
?>
