<?Php
//$path='/mnt/video/How its made/S15/Release/ferdig encode/How its made S15E01.mkv';
function mediainfo($path)
{
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
	{
	$mediainfo.= $outputkeys[$key];
	$mediainfo.= spaces($maxlen-strlen($outputkeys[$key]));
	$mediainfo.= ": $value\n";
	}
	else
		$mediainfo.= "\n[b]".$value."[/b]\n";

	
}
return $mediainfo;
}
//echo mediainfo($path);
function spaces($num)
{
	$spaces='';
	for ($i=1; $i<=$num; $i++)
	{
		$spaces.=' ';
	}
	return $spaces;
}