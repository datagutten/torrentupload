<?Php
class description
{
	private $dependcheck;
	private $video;
	public $imagehost;
	public $error;
	function __construct()
	{
		require 'config.php';
		require_once 'tools/dependcheck.php';
		$this->dependcheck=new dependcheck;
		require_once 'tools/video.php';
		$this->video=new video;
		require 'imagehost/loader.php';
		$this->imagehost=new $config['image_host'];
	}
	public function serieinfo($release) //Henter serie og episodeinfo fra releasenavn
	{
		if (preg_match('^(.+?)S*([0-9]*)EP*([0-9]+)^i',$release,$serieinfo)) //Try to get season and episode info from the release name
		{
			$serieinfo[1]=trim(str_replace('.',' ',$serieinfo[1])); //trim serienavn og erstatt . med mellomrom
			if($serieinfo[2]=='') //Hvis det ikke er oppgitt sesong, sett sesong til 1
				$serieinfo[2]=1;
		}
		else
			$serieinfo=false;
		return $serieinfo; //1=serienavn, 2=sesong
	}

	//Create snapshots from video file
	public function snapshots($file,$snapshotdir=false)
	{
		$positions=$this->video->snapshotsteps($file,4); //Calcuate snapshot positions
		if(empty($snapshotdir)) //Create snapshot directory in video folder if other folder is not specified
			$snapshotdir=dirname($file).'/snapshots';
		if(!file_exists($snapshotdir))
			mkdir($snapshotdir,0777,true);
		return $this->video->snapshots($file,$positions,$snapshotdir);
	}

	//Upload snapshots using imagehost class
	function upload_snapshots($snapshots)
	{
		if(empty($snapshots))
			return false;
		if(empty($this->imagehost))
			return false;
		foreach ($snapshots as $key=>$snapshot)
		{
			$upload=$this->imagehost->upload($snapshot);
			if($upload===false)
			{
				$this->error=$this->imagehost->error;
				return false;
			}
			$snapshotlinks[$key]=$upload;
		}
		return $snapshotlinks;
	}
	function snapshots_bbcode($snapshotlinks)
	{
		$bbcode='';
		foreach ($snapshotlinks as $screenshot) //Lag screenshots
		{
			if(method_exists($this->imagehost,'bbcode'))
				$bbcode.=$this->imagehost->bbcode($screenshot);
			else
				$bbcode.=sprintf('[img]%s[/img]',$screenshot);
		}
		return $bbcode;
	}

	public function mediainfo($path)
	{
		if($this->dependcheck->depend('mediainfo')!==true)
		{
			echo "Could not find mediainfo\n";
			return false;	
		}
		$info=shell_exec("mediainfo --Output=XML \"$path\" 2>&1");
		//die($info);
		$xml=simplexml_load_string($info);
		$xml=json_decode(json_encode($xml),true);
		if(!isset($xml['File']))
			die("Kunne ikke hente mediainfo\n");
		foreach ($xml['File']['track'] as $data)
		{
			$output[]=$data['@attributes']['type'];
			$outputkeys[]='header';
			foreach ($data as $key=>$value)
			if(array_search($key,array('@attributes','Unique_ID','Complete_name','Encoding_settings','Color_primaries','Transfer_characteristics','Matrix_coefficients'))===false)
			{
				$output[]=$value;
				$outputkeys[]=$key;
				$keylengths[]=strlen($key);
			}
		}
		
		$maxlen=max($keylengths); //Finn den lengste key
		$mediainfo='';
		foreach ($output as $key=>$value)
		{
			if ($outputkeys[$key]!='header')
				$mediainfo.=str_pad($outputkeys[$key],$maxlen+5).": $value\n";
			else
				$mediainfo.= "\n[b]".$value."[/b]\n";	
		}
		return $mediainfo;
	}
	public function simplemediainfo($path)
	{
		if($this->dependcheck->depend('mediainfo')!==true)
		{
			echo "Could not find mediainfo\n";
			return false;	
		}
		$info=shell_exec($cmd="mediainfo \"$path\" 2>&1");
		$info=preg_replace("/Complete name.+\n/",'',$info);
		$info=preg_replace("/Unique ID.+\n/",'',$info);
		return $info;
	}
}
