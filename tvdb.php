<?Php
class tvdb
{
	public $apikey;
	private $ch;
	private $http_status;
	private $linebreak="\n";
	function __construct($apikey)
	{
		$this->ch=curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,1);	
		curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,1);
		$this->apikey=$apikey;
	}
	public function get($url)
	{

		curl_setopt($this->ch, CURLOPT_URL,$url);
		$data=curl_exec($this->ch);		
		$this->http_status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

		if($this->http_status!=200)
			return false;
		else
			return json_decode(json_encode(simplexml_load_string($data)),true);
	}
	
	public function findseries($search)
	{
		$key=$this->apikey;
		if($search=='')
			die('getseries was called without specifying any series');
		if(!is_numeric($search))
		{	
			$search=str_replace('its',"it's",$search);
			$seriesinfo=$this->get($url='http://www.thetvdb.com/api/GetSeries.php?language=all&seriesname='.urlencode($search));
			if($seriesinfo===false)
			{
				echo "Error connecting to TheTVDB".$this->linebreak;
				return false;
			}
			if(!isset($seriesinfo['Series']['seriesid']))
			{
				echo "Series not found on TheTVDB".$this->linebreak;
				return false;
			}
			$id=$seriesinfo['Series']['seriesid'];
		}
		else
			$id=$search;

		if(is_numeric($id)) //Hvis id er funnet, hent episoder
		{	
			$episoder=$this->get("http://www.thetvdb.com/api/$key/series/$id/all/no");
			if($episoder===false && $this->http_status==404)
				if(!$episoder=$this->get($url="http://www.thetvdb.com/api/$key/series/$id/all"))
					die("Finner ikke informasjon om serien".$this->linebreak);

			if ($episoder['Series']['SeriesName']=='')
				$episoder=$this->get("http://www.thetvdb.com/api/$key/series/$id/all/en");
				
			return $episoder;
		}
	}
	public function finnepisode($serie,$sesong,$episode) //Finn informasjon om en episode
	{
		if (!is_array($serie))
			$serie=$this->findseries($serie);
		
		if(is_array($serie))
		{
			foreach ($serie['Episode'] as $episodedata) //Gå gjennom alle episoder i alle sesonger til riktig episode blir funnet
				if ($episodedata['SeasonNumber']==$sesong && $episodedata['EpisodeNumber']==$episode)
					break;

			$return=array('Episode'=>$episodedata,'Series'=>$serie['Series']);
		}
		else
			$return=false;
	return $return;
	}
	public function banner($serie)
	{
		if(!is_array($serie))
		{
			$serie=urlencode(str_replace('.',' ',$serie));
			$xml=$this->get("http://www.thetvdb.com/api/GetSeries.php?seriesname=$serie&language=all");
		}
	
		if(isset($xml['Series']['banner']))
			$banner="http://thetvdb.com/banners/{$xml['Series']['banner']}";
		else
			$banner=false;

		return $banner;
	}
	public function finnepisodenavn($find,$episoder)
	{
		$found=false;
		//print_r($episoder);
		foreach ($episoder['Episode'] as $episode)
		{
			if(is_array($episode['EpisodeName'])) //Skip episodes with no name
				continue;
			if(stripos($episode['EpisodeName'],$find)!==false)
			{
				$found=true;
				break;
			}
		}
		if($found)
			$return=array('Episode'=>$episode,'Series'=>$episoder['Series']);
		else
			$return=false;
		return $return;
	}

}