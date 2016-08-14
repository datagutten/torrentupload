<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Templatemaker</title>
</head>

<body>
<form method="post" target="">
<?Php
if(empty($_POST))
{
	echo "<p>Change the form target of the upload page on your tracker to the location of this script. Then submit the form and select which fields to include in the template and insert the placeholders in the correct fields.<br />You need to make two templates: One generic site template named site_NameOfYourSite (Need to be the same as in config.php) and another for each type of torrent you want to upload</p>";
}
elseif(strpos($_SERVER['HTTP_REFERER'],'github.quad.local')===false)
{
	foreach($_POST as $field=>$value)
	{
		echo "<p>\n";
		echo $field;
		?>
        
        <input type="text" name="<?Php echo $field; ?>" value="<?Php echo $value; ?>">
        <input type="checkbox" name="checked_<?Php echo $field; ?>">
        </p>
        <?Php
	}
	?>
    <p>
        Template name: 
        <input type="text" name="templatename">
		<input type="submit" value="Submit">
        </form>
            <p>Placeholders for templates:</p>
    <p>--title--</p>
    <p>--description--</p>
    <p>--torrentfile--</p>
    <p>--mediainfo--</p>

         <?Php
}
else
{
	print_r($_POST);
	foreach($_POST as $field=>$value)
	{
		if(substr($field,0,7)=='checked' || !isset($_POST['checked_'.$field]))
			continue;
		$template[$field]=$value;
	}
	if(!file_exists($templatedir=dirname(__FILE__).'/templates/'))
		mkdir($templatedir);
	file_put_contents($templatedir.basename($_POST['templatename']).'.json',json_encode($template));
}

?>
</p>

</body>
</html>