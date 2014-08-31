<?Php
class upload
{
	public $ch;
	public $ftp;
	public $torrent_file_dir;
	public $torrent_auto_dir;
	public $site;
	function __construct()
	{
		$this->ch=curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE,'cookies.txt');
		curl_setopt($this->ch, CURLOPT_COOKIEJAR,'cookies.txt');
		require 'config.php';
		if(isset($ftp))
			$this->ftp=$ftp;
		$this->site=$site;
		$this->torrent_file_dir=$torrent_file_dir;
		$this->torrent_auto_dir=$torrent_auto_dir;
		
	}
	function login()
	{
		echo "Logging in\n";
		curl_setopt($this->ch,CURLOPT_URL,$this->site['url']."/takelogin.php");
		curl_setopt($this->ch,CURLOPT_POST, 1);
		curl_setopt($this->ch,CURLOPT_POSTFIELDS,array("username" => $this->site['username'], "password" => $this->site['password']));
		curl_setopt($this->ch,CURLOPT_REFERER,$this->site['url']."/hei.php");	
		return curl_exec($this->ch);
	}
	function ftp_upload_torrent($torrentfile)
	{
		$conn_id=ftp_connect($this->ftp['host']) or die("Couldn't connect to ftp");
		ftp_login($conn_id, $this->ftp['username'], $this->ftp['password']) or die ("Couldn't log on");
		
		if (ftp_put($conn_id,$this->ftp['path'].basename($torrentfile),$torrentfile,FTP_BINARY))
			echo "successfully uploaded $torrentfile\n";
		else
			echo "There was a problem while uploading $torrentfile\n";
	}
	function cleanfilename($filename) //Remove special charaters from a filename
	{
		$filename=str_replace(array('æ','ø','å','Æ','Ø','Å'),array('e','o','a','E','O','A'),$filename);
		$filename=preg_replace('/[^\x20-\x7E]/','', $filename); //Remove other non printable characters
		return $filename;
	}
	function upload($release,$description,$torrent,$parameters=false) //Upload the torrent to the site. Additional fields can be specified with the last argument
	{
	//Some basic parameters
		$torrentfile=new CURLFile($torrent,'application/x-bittorrent');
		$postdata=array('MAX_FILE_SIZE' => "3145728",
					'name' => $release,
                    'file' => $torrentfile,
                    'nfopos' => "top",
					'infourl' => "",
					'descr' => $description,				   
                    'mediainfo' => "");
	if(is_array($parameters))
		$postdata=array_merge($parameters,$postdata);
	print_r($postdata);

		if($this->site['charset']!='UTF-8')
		{
			$postdata['name']=utf8_decode($postdata['name']);
			$postdata['descr']=utf8_decode($postdata['descr']);
		}
	
	curl_setopt($this->ch,CURLOPT_URL,$this->site['url']."/takeupload.php");
	curl_setopt($this->ch,CURLOPT_POST, 1);
	curl_setopt($this->ch,CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($this->ch,CURLOPT_REFERER,$this->site['url']."/upload.php");	
	
		$upload=curl_exec($this->ch);
	
		if($upload!==false)
			return $upload;
		else
		{
			trigger_error(curl_error($this->ch),E_USER_ERROR);
			return false;
		}
	}
	
	
	function uploadhandler($upload,$release)
	{
		//Håndter feilet opplasting
		if(preg_match('^Mislykket opplasting.*\<div class="contentbox"\>(.*)\</div\>^sU',$upload,$result) || preg_match('^Feilmelding:\</b\>(.+)\</p\>^',$upload,$result))
			die('Feil: '.utf8_encode($result[1])."\n");
		elseif(strpos($upload,'En torrent med lignende innhold')!==false)
			die("Allerede lastet opp\n");
		else
		{
			preg_match('^(download\.php\?id=[0-9]+)\"^',$upload,$file); //Finn torrentfilnavnet
			echo "Laster ned torrent\n";
			$torrentfile=$this->torrent_auto_dir.'/'.$release.'.torrent';
			curl_setopt($this->ch,CURLOPT_HTTPGET,true);
			curl_setopt($this->ch,CURLOPT_URL,$this->site['url']."/".$file[1]);
			curl_setopt($this->ch,CURLOPT_REFERER,$this->site['url']."/uploaded.php");
			$torrent=curl_exec($this->ch);
			file_put_contents($torrentfile,$torrent);
			if(isset($this->ftp['host']))
			{
				echo "Laster opp torrent til seedbox\n";
				$this->ftp_upload_torrent($torrentfile);
			}
		}
		file_put_contents("upload_$release.txt",$upload);	
	}
}
	
