<?Php
class imgur
{
    private $api_key;
    private $api_secret;
	private $ch;
    function __construct($api_key, $api_secret)
    {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
		$this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Authorization: Client-Id '.$this->api_key));
    }
	
	private function request($url,$type="GET",$postfields=false)
    {
        if ($postfields!==false)
		{
            curl_setopt($this->ch,CURLOPT_POST,true);
			curl_setopt($this->ch,CURLOPT_POSTFIELDS, $postfields);
		}
		else
			curl_setopt($this->ch,CURLOPT_HTTPGET,true);
		
		curl_setopt($this->ch, CURLOPT_URL, $url);
        
		if (($data = curl_exec($this->ch))===false)
            throw new Exception(curl_error($this->ch));
		return $data;
    }
    public function upload($image) //Husk @ foran filnavnet
    {
		$json=$this->request("https://api.imgur.com/3/upload","POST",array('image'=>$image));
		$array=json_decode($json,true);
		if($array['status']!=200)
			die("Feil under opplasting: ".$array['data']['error']."\n");
		return $array;
    }
	public function upload_dupecheck($file)
	{
		$image=file_get_contents($file);
		$md5=md5($image);
		if(file_exists("imgur_md5/$md5")) //Sjekk om filen allerede er lastet opp
			$data=json_decode(file_get_contents("imgur_md5/$md5"),true); //Returner lagrede opplysninger
		else
		{
			echo "Laster opp til imgur\n";
			if(!file_exists('imgur_md5'))
				mkdir('imgur_md5');
			$data=$this->upload($image); //Ellers skal filen lastes opp og nye opplysninger lagres og returneres
			file_put_contents("imgur_md5/$md5",json_encode($data));
		}	
		return $data;
	}
	public function thumbnail($link,$size) //http://api.imgur.com/models/image
	{
		$pathinfo=pathinfo($link);
		return str_replace('.'.$pathinfo['extension'],$size.'.'.$pathinfo['extension'],$link); //Lag link til thumbnail
	}
}


?>