<?Php
class upload
{
	public $ch;
	public $ftp;
	public $torrent_file_dir;
	public $torrent_auto_dir;
	public $site;
	public $scriptdir;
	public $error;
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
		$this->scriptdir=dirname(__FILE__).'/';
		if(!file_exists($this->scriptdir.'uploads'))
			mkdir($this->scriptdir.'uploads');
		
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

	/*Create torrent file, piece length is specified in megabytes
	Information from http://torrentinvites.org/f29/piece-size-guide-167985/ :
	so lets assume we leave 100kb of space for the list of files and whatnot in .torrent. the piece hashes is a list of 20-byte SHA-1 hashes for each peace, so thats about 46000 pieces we can store in torrent. doing the division to keep the .torrent under 1 MB:
	1 mb piece size = max of about 45 GB torrent
	4 mb piece size = max of 180 GBs
	8 mb piece size = max of 360 GBs
	*/	
	function buildtorrent($path,$torrentfile,$tracker='http://localhost',$piecelength=4)
	{
		if (!file_exists($torrentfile))
		{
			echo "Creating torrent\n";

			$piecelength=$piecelength*pow(1024,2); //Convert piece length to bytes
			$cmd=sprintf('buildtorrent -p1 -l %d -a %s "%s" "%s" 2>&1',$piecelength,$tracker,$path,$torrentfile);
			echo shell_exec($cmd);

			if (!file_exists($torrentfile))
			{
				$this->error="Torrent creation failed\n$cmd\n";
				return false;
			}
		}
		else
			echo "Torrent is already created\n";
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
		$filename=str_replace(array('æ','ø','å','Æ','Ø','Å','ä','ö','Ä','Ö','#'),array('e','o','a','E','O','A','a','o','A','O',''),$filename);
		$filename=preg_replace('/[^\x20-\x7E]/','', $filename); //Remove other non printable characters
		return $filename;
	}
	function buildupload($template,$torrentfile,$title,$description,$mediainfo='')
	{
		if(file_exists($templatefile=$this->scriptdir.'templates/'.$template.'.json'))
			$template_topic=json_decode(file_get_contents($templatefile),true);
		else
			trigger_error($templatefile." not found",E_USER_ERROR);
		if(file_exists($templatefile=$this->scriptdir.'templates/site_'.$this->site['name'].'.json'))
			$template_site=json_decode(file_get_contents($templatefile),true);
		else
			trigger_error($templatefile." not found",E_USER_ERROR);
		$postdata=array_merge($template_site,$template_topic);
		$postdata=str_replace(array('--title--','--description--','--mediainfo--'),array($title,$description,$mediainfo),$postdata);
		$key=array_search('--torrentfile--',$postdata);
		if($key!==false)
			$postdata[$key]=new CURLfile($torrentfile);
		else
			trigger_error("Invalid template, torrent file missing",E_USER_ERROR);
		return $postdata;
	}
	function sendupload($postdata) //Upload the torrent to the site. Additional fields can be specified with the last argument
	{
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
	public function upload($template,$torrentfile,$title,$description,$mediainfo)
	{
		return $this->sendupload($this->buildupload($template,$torrentfile,$title,$description,$mediainfo));
	}

	
	function uploadhandler($upload,$release)
	{
		//Håndter feilet opplasting
		if(preg_match('^Mislykket opplasting.*\<div class="contentbox"\>(.*)\</div\>^sU',$upload,$result) || preg_match('^Feilmelding:\</b\>(.+)\</p\>^',$upload,$result))
		{	
			$this->error='Error: '.utf8_encode(trim($result[1]));
			return false;
		}
		elseif(strpos($upload,'En torrent med lignende innhold')!==false)
		{
			$this->error='Allerede lastet opp';
			return false;
		}
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
			return $torrentfile;
		}
		file_put_contents($this->scriptdir."uploads/upload_$release.txt",$upload);
	}
}
	
