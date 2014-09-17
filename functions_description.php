<?Php
class description
{
	private $dependcheck;
	function __construct()
	{
		require 'tools/dependcheck.php';	
		$this->dependcheck=new dependcheck;
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
	public function snapshots($file,$times=false) //Second argument can be an array with time positions or a number of snapshots to be created
	{
		if($this->dependcheck->depend('mplayer')!==true)
		{
			echo "mplayer er nødvendig for å lage snapshots\n";
			return false;	
		}
		if($this->dependcheck->depend('mediainfo')===true)
		{
			if(!is_array($times))
			{
				if(is_numeric($times))
					$count=$times;
				elseif($times===false)
					$count=4;
				$duration=floor(shell_exec("mediainfo --Inform=\"General;%Duration%\" \"$file\"")/1000);
				$step=floor($duration/($count+1)); //Get the step size
				$times=range($step,$duration,$step); //Make an array with the positions
				array_pop($times); //remove the last position
			}
		}
		else
			$times=array(65,300,600,1000); //mediainfo not found, use default positions
		
		$snapshots=array();
		$basename=basename($file);
		$snapshotdir="snapshots/$basename/";
		if(!file_exists($snapshotdir))
			mkdir($snapshotdir,0777,true);
		foreach ($times as $time)
		{
			
			if(!file_exists($snapshotfile=$snapshotdir.$time.".png"))
			{
				$mplayer_log=shell_exec($cmd="mplayer -quiet -ss $time -vo png:z=9 -ao null -zoom -frames 1 \"$file\" 2>&1");	
				//die($cmd);
				if(file_exists($tmpfile='00000001.png'))
					rename($tmpfile,$snapshots[]=$snapshotfile);
				else
					trigger_error("Failed to create snapshot: $mplayer_log",E_USER_ERROR);
			}
			else
				$snapshots[]=$snapshotfile;
			
		}
		if(!isset($snapshots))
			$snapshots=false;
		return $snapshots; //Returnerer et array med bildenes filnavn
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
		//print_r($xml['File']['track']);
		
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
