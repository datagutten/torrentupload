<?Php
include 'functions_description.php';
$desc=new description;
$options=getopt('',array('simple'));
end($argv);
$file=$argv[key($argv)];
if(isset($options['simple']))
	echo $desc->simplemediainfo($file);
else
	echo $desc->mediainfo($file);

?>