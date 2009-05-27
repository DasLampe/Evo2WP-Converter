<?php
define("MYSQL_HOST",	"localhost");
define("MYSQL_USER",	"root");
define("MYSQL_PASS",	"lanocrew-mysql_server");
define("MYSQL_DB",		"andreflemming_de");
define("MYSQL_PREFIX_wp",	"wp_");
define("MYSQL_PREFIX_evo",	"blog_");

//Connect to Database
$dbcon = mysql_connect( MYSQL_HOST, MYSQL_USER, MYSQL_PASS );
mysql_select_db( MYSQL_DB, $dbcon );

/*
 * Categories
 */

//Read categories
$sql		= "SELECT cat_parent_ID, cat_ID, cat_name, cat_urlname
				FROM ".MYSQL_PREFIX_evo."categories";
$result		= mysql_query($sql);
while($row	= mysql_fetch_assoc($result))
{
	//Insert categories
	$insert	= "INSERT INTO ".MYSQL_PREFIX_wp."terms
				(term_id, slug, name)
				VALUES
				('".$row['cat_ID']."', '".$row['cat_urlname']."', '".$row['cat_name']."')";
	mysql_query($insert);
	
	$insert	= "INSERT INTO ".MYSQL_PREFIX_wp."term_taxonomy
				(term_taxonomy_id, term_id, taxonomy, parent)
				VALUES
				('".$row['cat_ID']."', '".$row['cat_ID']."', 'category', '".$row['cat_parent_ID']."')";
	mysql_query($insert);
}

echo 'Import categories successful!';
?>