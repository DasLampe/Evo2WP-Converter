<?php
/** ///////////////////////////////////////////////////////////////
 * Evo2WP-Converter
 * Author:		DasLampe
 * Contact:		andre@lano-crew.org
 * Homepage:	http://andre.lano-crew.org
 * License: 	CreativCommons (BY-NC-SA) 
 * Version: 	1.0.1
 * 
 * Features:
 * - Import Categories
 * - Import Comments (no Trackback!)
 * - Import Post with Maincategorie
 * 
 * Known bugs:
 * None
 * 
 * Bug Report: andre@lano-crew.org
 /////////////////////////////////////////////////////////////////*/
define("MYSQL_HOST",		"");
define("MYSQL_USER",		"");
define("MYSQL_PASS",		"");
define("MYSQL_DB",			"");
define("MYSQL_PREFIX_wp",	"wp_");
define("MYSQL_PREFIX_evo",	"evo_");

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
echo '<br>';

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

	//Insert: wp_posts
	$insert	= "INSERT INTO ".MYSQL_PREFIX_wp."posts
				(ID, post_author, post_date, post_date_gmt, post_content, post_title, post_status, comment_status, post_name, post_modified, post_modified_gmt)
				VALUES
				('".$row['post_ID']."', '".$row['post_creator_user_ID']."', '".$row['post_datestart']."', '".$starttime_gmt."', '".$row['post_content']."', '".$row['post_title']."', '".$row['post_status']."', '".$row['post_comment_status']."', '".$row['post_urltitle']."', '".$row['post_datemodified']."', '".$edittime_gmt."')";
	mysql_query($insert);
	
	//Update: wp_term_taxonomy
	$update	= "UPDATE ".MYSQL_PREFIX_wp."term_taxonomy SET
				count = count+1
				WHERE term_id = '".$row['post_main_cat_ID']."'";
	mysql_query($update);
	
	//Insert: wp_term_relationships
	$insert	= "INSERT INTO ".MYSQL_PREFIX_wp."term_relationships
				(object_id, term_taxonomy_id)
				VALUES
				('".$row['post_ID']."', '".$row['post_main_cat_ID']."')";
	mysql_query($insert);
}

echo 'Import Posts successful!';
echo '<br>';

/**
 * Comments
 */
//Read Comments

$sql		= "SELECT comment_ID, comment_post_ID, comment_status, comment_author_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_content
				FROM ".MYSQL_PREFIX_evo."comments
				WHERE comment_type = 'comment'";
$result		= mysql_query($sql);

while($row	= mysql_fetch_assoc($result))
{
	//Convert Status
	if($row['comment_status'] == "published")
	{
		$row['comment_status'] = 1;
	}
	
	//Convert time
	$comment_date_gmt	= convert2gmt($row['comment_date']);
	
	//Set Comment Author Details
	if(empty($row['comment_author']) && !empty($row['comment_author_ID']))
	{
		$author_sql		= "SELECT user_idmode, user_firstname, user_lastname, user_nickname, user_email, user_url
							FROM ".MYSQL_PREFIX_evo."users
							WHERE user_ID = '".$row['comment_author_ID']."'";
		$author_result	= mysql_query($author_sql);
		$author_row		= mysql_fetch_assoc($author_result);
		
		
		//Set comment_author name by Modus
		switch($author_row['user_idmode'])
		{
			case 'firstname':
				$row['comment_author']	= $author_row['user_firstname'];
				break;
			case 'lastname':
				$row['comment_author']	= $author_row['user_lastname'];
				break;
			case 'nickname':
				$row['comment_author']	= $author_row['user_nickname'];
				break;
		}
		
		//Set comment_author_email
		$row['comment_author_email']	= $author_row['user_email'];
		
		//Set comment_author_url
		$row['comment_author_url']		= $author_row['user_url'];		
	}
	
	//Insert: wp_comments
	$insert	= "INSERT INTO ".MYSQL_PREFIX_wp."comments
				(comment_ID, comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_date_gmt, comment_content, comment_approved, user_id)
				VALUES
				('".$row['comment_ID']."', '".$row['comment_post_ID']."', '".$row['comment_author']."', '".$row['comment_author_email']."', '".$row['comment_author_url']."', '".$row['comment_author_IP']."', '".$row['comment_date']."', '".$comment_date_gmt."', '".$row['comment_content']."', '".$row['comment_status']."', '".$row['comment_author_ID']."')";
	mysql_query($insert);

	//Update: wp_posts
	$update	= "UPDATE ".MYSQL_PREFIX_wp."posts SET
				comment_count	= comment_count +1
				WHERE ID = '".$row['comment_post_ID']."'";
	mysql_query($update);
	echo mysql_error();
}

echo 'Import Comments successful!';
?>