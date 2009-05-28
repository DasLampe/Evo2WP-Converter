<?php
define("MYSQL_HOST",	"localhost");
define("MYSQL_USER",	"root");
define("MYSQL_PASS",	"lanocrew-mysql_server");
define("MYSQL_DB",		"andreflemming_de");
define("MYSQL_PREFIX_wp",	"wp_");
define("MYSQL_PREFIX_evo",	"blog_");

//*************************************************************************
//***************DO NOT EDIT AFTER THIS************************************
//*************************************************************************


/*
 * Functions
 */

//Convert Function
function convert2gmt($time)
{
	$time		= explode(" ", $time);
	$time[1]	= explode(":", $time[1]);
	$time[1][0] -= 2;
	if($time[1][0] < 0)
	{
		$time[1][0] = 24 + $time[1][0];
	}
	if($time[1][0] > 0 && $time[1][0] < 10)
	{
		$time[1][0] = 0 . $time[1][0];
	}
	
	$time_gmt				= $time[0]." ".$time[1][0].":".$time[1][1].":".$time[1][2];

	return $time_gmt;
}

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

/*
 * Posts
 */

//Read posts
$sql		= "SELECT post_ID, post_creator_user_ID, post_datestart,
					post_datemodified, post_status, post_title, post_urltitle,
					post_main_cat_ID, post_comment_status, post_content
				FROM ".MYSQL_PREFIX_evo."items__item";
$result		= mysql_query($sql);
while($row	= mysql_fetch_assoc($result))
{
	/*post_status Values (Table: wp_posts)
	 * publish in evo published
	 * draf
	 * pending
	 */
	
	//Convert post_staus
	if($row['post_status'] == "published")
	{
		$row['post_status'] = "publish";	
	}
	
	//Convert time 
	$starttime_gmt	= convert2gmt($row['post_datestart']);
	$edittime_gmt	= convert2gmt($row['post_datemodified']);
	
	//Masking special character
	$row['post_content'] = str_replace("'", "\'", $row['post_content']);

	
	$insert	= "INSERT INTO ".MYSQL_PREFIX_wp."posts
				(ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, comment_status, post_name, post_modified, post_modified_gmt)
				VALUES
				('".$row['post_ID']."', '".$row['post_creator_user_ID']."', '".$row['post_datestart']."', '".$starttime_gmt."', '".$row['post_content']."', '".$row['post_title']."', '".$row['post_status']."', '".$row['post_comment_status']."', '".$row['post_urltitle']."', '".$row['post_datemodified']."', '".$edittime_gmt."')";
	mysql_query($sql);
}

echo 'Import Posts successful!';
?>