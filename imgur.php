<?Php
function imgur_upload($filename,$imgur_key)
{
	//http://api.imgur.com/examples#uploading_php

    $handle = fopen($filename, "r");
    $data = fread($handle, filesize($filename));

    // $data is file data
    $pvars   = array('image' => base64_encode($data), 'key' => $imgur_key);
	return post('http://api.imgur.com/2/upload.json',$pvars,false,false,false,true); //Returnerer json, bruk json_decode


}
function imgur_upload_dupecheck($file,$imgur_key)
{
	$checkfile=basename($file);
	$md5=md5(file_get_contents($file));
	if(file_exists("imgur/$checkfile.txt"))
		$data=file_get_contents("imgur/$checkfile.txt"); //Hvis filen allerede er lastet opp, returner lagrede opplysninger
	elseif(file_exists("imgur_md5/$md5"))
		$data=file_get_contents("imgur_md5/$md5");
	else
	{
		echo "Laster opp til imgur\n";
		if(!file_exists('imgur_md5'))
			mkdir('imgur_md5');
		$data=imgur_upload($file,$imgur_key); //Ellers skal filen lastes opp og nye opplysninger lagres og returneres
		//var_dump($data);
		//file_put_contents("imgur/$checkfile.txt",$data); 
		file_put_contents("imgur_md5/$md5",$data);
	}	
	return json_decode($data,true);
}
?>