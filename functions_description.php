<?Php
class description
{
	private $dependcheck;
	private $video;
	function __construct()
	{
		require_once 'tools/dependcheck.php';
		$this->dependcheck=new dependcheck;
		require_once 'tools/video.php';
		$this->video=new video;

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
	public function snapshots($file)
	{
		$positions=$this->video->snapshotsteps($file,4);
		if(!file_exists($snapshotdir=dirname($file).'/snapshots'))
			mkdir($snapshotdir,0777,true);
		return $this->video->snapshots($file,$positions,$snapshotdir);
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
	public function description($screenshots,$description)
	{
		$screens=''; //Lag variabelen screens for å unngå warning
		foreach ($screenshots as $key=>$screenshot) //Lag screenshots
		{
			$screens .= "[url={$screenshot['image']}][img]{$screenshot['thumbnail']}[/img][/url]";
		}
		return $description."\n".$screens; //Sett sammen banner, beskrivelse og screenshots
	}
}
