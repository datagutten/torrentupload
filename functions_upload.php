<?Php
class upload
{
	public $ch;
	public $ftp;
	public $torrent_file_dir;
	public $torrent_auto_dir;
	public $site_url;
	function __construct()
	{
		$this->ch=curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE,'cookies.txt');
		curl_setopt($this->ch, CURLOPT_COOKIEJAR,'cookies.txt');
		require 'config.php';
		$this->ftp=$ftp;
		$this->site_url=$site_url;
		$this->torrent_file_dir=$torrent_file_dir;
		$this->torrent_auto_dir=$torrent_auto_dir;
		
	}
	function ftp_upload_torrent($torrent)
	{
		$conn_id=ftp_connect($this->ftp['host']) or die("Couldn't connect to ftp");
		ftp_login($conn_id, $this->ftp['username'], $this->ftp['password']) or die ("Couldn't log on");
		
		if (ftp_put($conn_id,$torrent,FTP_ASCII))
			echo "successfully uploaded $torrent\n";
		else
			echo "There was a problem while uploading $torrent\n";
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
	
	
	function upload($release,$description,$torrent)
	{
		$parameters['scene']='no';
		$parameters['main_cat']=2;
		$parameters['sub1_cat']=9;
		$parameters['sub3_cat']=29; //37=e-bøker
		$parameters['sub2_cat']=20;
		
		return sendupload_temp($release,$description,$torrent,$parameters);
	}
	function sendupload($postdata) //Send opplastingen til siden
	{
		$postdata['anonym']="yes";
		return post($this->site_url."/takeupload.php",$postdata,'cookies.txt',$site_url."/upload.php");
	}
	function uploadhandler($upload,$release)
	{
		global $site_url,$torrent_auto_dir;
		//Håndter feilet opplasting
		if(preg_match('^Mislykket opplasting.*\<p\>(.*)\</p\>^sU',$upload,$result))
			die('Feil: '.$result[1]);
		elseif(strpos($upload,'En torrent med lignende innhold')!==false)
			die("Allerede lastet opp\n");
		else
		{
			preg_match('^(download\.php\?id=[0-9]+)\"^',$upload,$file); //Finn torrentfilnavnet
			echo "Laster ned torrent\n";
			$torrentfile=$this->torrent_auto_dir.'/'.$release.'.torrent';
			file_put_contents($torrentfile,get($this->site_url."/".$file[1],'cookies.txt',$site_url."/uploaded.php")); //Last ned torrent
			if(isset($ftp_host))
			{
				echo "Laster opp torrent til seedbox\n";
				$this->ftp_upload_torrent($torrentfile);
			}
		}
		file_put_contents("upload_$release.txt",$upload);	
	}
}
	
