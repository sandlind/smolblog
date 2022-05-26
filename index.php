<html>
<head>
<style>
body { min-height: 100%; background: #ccc; font-family: sans-serif; margin: 0; padding: 0; }

div#contain{ float: right; width: calc(100% - 228px); text-align: left; padding: 8px;}
div#content{float:right; width: calc(100% - 210px); line-height:1.4;}
div#nav{background:#999; top: 0px; color: white; padding: 4px; border: 4px #bbb; border-style: hidden double double hidden; float:left; width:200px;}
div#post{ display: table; background: white; color: black; padding: 8px; margin-bottom: 8px; border: 2px black; border-style: solid hidden hidden solid; }

p.list { padding: 0; margin: 0; margin-left: 15px; margin-bottom: 10px; font-size: 11pt; border: 1px #bbb; border-style: hidden hidden hidden solid; background-color: #777; color: white; }
a.post { font-size: 18pt; text-decoration: none; }
a.list { color: white;; text-decoration: none; }
a.list:hover { color: #bbb; }
a { color:#777 ; text-decoration: none; }
a:hover { color: #bbb; }
span#info { font-size: 10pt; color: #999; }
</style>
</head>
<?php
## Start counting execution time of script
$exec_start = microtime(true);
## First things first...

## DATABASE
##	Uses sqlite by default, but MySQL could be used. The rest of this script would function the same but this connection code would need to be changed a bit. The tables would also need to exist on the database too.
try {
	$conn = new PDO('sqlite:blog.db');
	$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch (PDOException $e) {
	echo "Connection failed: " . $e->getmessage();
}
## Session for admin accounts
session_start();

## CONFIGURATION
##	Please change the following variables appropriately for your blog.

$setting_blog_name = 'smolBlog';			# Name of this blog
$setting_blog_title = "<b>smolBlog</b><br>This is your new smolBlog installation.";	# In-line HTML that shows on top of the board
$setting_admin_pass = 'CHANGEME2';	 		# Admin pass (CHANGE THIS ! ! ! )
$setting_time_zone = 'America/New_York';	# Time zone

## FUNCTIONS

## blogExit : Consistent script exiting for the user
function blogExit($message){
	echo "
	<div id='contain'>
	<div id='post'>
	$message<br><br>
	<a href=''><b>Back</b></a>
	</div>
	</div>";
	blogExecTime();
	die();
}

## blogWrite : Consistent code for writing flat files 
function blogWrite($file, $contents){
	$openfile = fopen($file,"w");
	fwrite($openfile, $contents);
	fclose($openfile);
}

## blogExecTime : Prints the time it took the script to execute
function blogExecTime(){
	GLOBAL $exec_start;
	$exec_end = microtime(true);
	$exec_time = round($exec_end - $exec_start, 3) * 1000;
	echo "$exec_time ms";
}

## ACTUAL SCRIPT

date_default_timezone_set($setting_time_zone);
$current_time = time();
echo "<title>$setting_blog_name</title>";

if ($setting_admin_pass == "CHANGEME"){
	blogExit("<b>Set the password. Now.</b><br>Go into <b>index.php</b> with an editor and change <i>setting_admin_pass</i> on or near <b>line 44</b> to something secure.");
}

echo "<div id='nav'>$setting_blog_title<br> <p class='list'>" . file_get_contents("nav.html") . "</p>";
echo "
<form style='margin: 0; padding: 0;' action='index.php' method='post'>
<input required type='hidden' name='mode' value='admin'>
<input required type='password' style='width: 125px;' name='adminpass' maxlength='32'>
<input type='submit' value='Admin'> 
</form>
";
echo "</div>";

## Display entire single blog post
if (isset($_GET['read'])){
	if (file_exists("src/" . $_GET['read'] . ".html")){
		$read = $_GET['read'];
		echo "<div id=contain><div id='post'>" . file_get_contents("src/$read.html") . "</div></div>";
		die();
	} else {
		blogExit("Post doesn't exist.");
	}
}

if (!isset($post) && !isset($mode)){
	$mode = "home";
}

if (isset($_GET['admin'])){
	$mode = "admin";
}

if (isset($_POST['mode'])){
	$mode = $_POST['mode'];
} else {
	$mode = 'home';
}

if ($mode == "admin"){
	if ($_POST['adminpass'] != $setting_admin_pass){
		blogExit("Wrong password. Go away.");
	}
	echo "
	<div id='contain'>
	<div id='post'>
	<h1>Compose a post</h1>
	Don't put weird characters into the URL field. Enter HTML, CSS or JS into the post text.<br>
	<form action='index.php' method='post'>
	<input type='text' name='title' placeholder='Post title' maxlength='32'>
	<input  type='text' name='url' placeholder='Post URL' maxlength='32'>
	<br><textarea rows='4' cols='16' name='text'  placeholder='Type your text here...'></textarea>
	<input type='hidden' name='mode' value='publish'>
	<input type='hidden' name='adminpass' value='$setting_admin_pass'><br>
	<input type='submit' value='Publish'></form>
	</div>
	
	<div id='post'>
	<h1>Delete a post</h1>
	Make backups of your blog if you're deleting important stuff!
	<form action='index.php' method='post'>
	<input  type='text' name='url' placeholder='Post URL' maxlength='32'>
	<input  type='hidden' name='mode' value='delete'>
	<input  type='hidden' name='adminpass' value='$setting_admin_pass'><br>
	<input type='submit' value='Delete'></form>
	</div>
	
	<div id='post'>
	<h1>Edit a post</h1>
	This will open another page for editing.
	<form action='index.php' method='post'>
	<input  type='text' name='url' placeholder='Post URL' maxlength='32'>
	<input  type='hidden' name='mode' value='edit'>
	<input  type='hidden' name='adminpass' value='$setting_admin_pass'><br>
	<input type='submit' value='Edit'></form>
	</div>
	
	</div>";
}

if ($mode == "publish"){
	if ($_POST['adminpass'] != $setting_admin_pass){
		blogExit("Wrong password. Go away.");
	}
	if (isset($_POST['title']) && isset($_POST['url']) && isset($_POST['text'])){
		$title = $_POST['title'];
		$url = $_POST['url'];
		$text = $_POST['text'];
	} else { 
		blogExit("You didn't set one of these things: Title, URL or Text");
	}
	
	## Check for duplicate post ID 
	$sql = "SELECT * FROM posts WHERE post_id=?";
	$stmt = $conn->prepare($sql);
	$stmt->execute([$url]);
	$dupe_post = $stmt->fetch();
	if (!empty($dupe_post)){
		blogExit("The post <b>$url</b> already exists according to the database.");
	}
	
	## Write blog post to database
	$sql = "INSERT INTO posts (post_id, post_title, post_text, post_time) VALUES (?,?,?,?)";
	$conn->prepare($sql)->execute([$url, $title, $text, $current_time]);
	
	$date = date("j F Y",$current_time);
	$post_contents = "<a id='post' href='index.php'>$title</a><br><span id='info'>$date</span><br>$text";
	blogWrite("src/$url.html", $post_contents);
	
	## Write blog index...
	## Not "index.html" because some web servers sperg if there's an index.php and index.html
	$new_index = "<a class='list' href='?read=$url'>$title</a><br>$date<br><br>\n\n" . file_get_contents("nav.html");
	blogWrite("nav.html", $new_index);
	blogExit("Post published!");
}

if ($mode == "delete") {
	if ($_POST['adminpass'] !== $setting_admin_pass){
		die("Wrong password. Go away.");
	}
	if (isset($_POST['url'])){
		$del_url = $_POST['url'];
	} else {
		blogExit("URL of blog post isn't set?");
	}
	## Delete blogpost from DB
	$sql = "DELETE FROM posts WHERE post_id=?";
	$conn->prepare($sql)->execute([$del_url]);
	
	## Delete the file
	unlink("src/$del_url.html");
	
	## Rewrite index without the deleted post in it
	$stmt = $conn->query("SELECT * FROM posts");
	$stmt->execute();
	$blogposts = $stmt->fetchAll();
	## Rewrite blog index...
	$new_index = array();
	foreach ($blogposts as $post){
		$date = date("j F Y",$post['post_time']);
		$new_post = "<a class='list' href='?read=" . $post['post_id'] . "'>" . $post['post_title'] . "</a><br>$date<br><br>\n\n";
		array_push($new_index,$new_post);
	}
	$new_index = array_reverse($new_index);
	$new_index = implode("\n\n",$new_index);
	blogWrite("nav.html", $new_index);
	blogExit("Deleted blogpost <b>$del_url</b>");
}

if ($mode == "edit"){
	if ($_POST['adminpass'] !== $setting_admin_pass){
		die("Wrong password. Go away.");
	}
	if (isset($_POST['url'])){
		$url = $_POST['url'];
	} else {
		blogExit("URL of blog post isn't set?");
	}
	$sql = "SELECT * FROM posts WHERE post_id=?";
	$stmt = $conn->prepare($sql);
	$stmt->execute([$url]);
	$post = $stmt->fetch();
	if (empty($post)){
		blogExit("The post <b>$url</b> does not exist according to the database.");
	}
	echo "
	<div id='contain'>
	<div id='post'>
	<h1>Edit post '$url'</h1>
	Don't put weird characters into the URL field. Enter HTML, CSS or JS into the post text.<br>
	<form action='index.php' method='post'>
	<input type='text' name='title' value='" . $post['post_title'] . "' maxlength='32'>
	<input  type='text' name='url' value='" . $post['post_id'] . "' maxlength='32'>
	<br><textarea rows='4' cols='16' name='text'  placeholder='Type your text here...'>
	" . $post['post_text'] . "
	</textarea>
	<input type='hidden' name='mode' value='update'>
	<input type='hidden' name='time' value='" . $post['post_time'] . "'>
	<input type='hidden' name='adminpass' value='$setting_admin_pass'><br>
	<input type='submit' value='Update'></form>
	</div>
	";
}

if ($mode == "update"){
	if ($_POST['adminpass'] != $setting_admin_pass){
		blogExit("Wrong password. Go away.");
	}
	if (isset($_POST['title']) && isset($_POST['url']) && isset($_POST['text']) && isset($_POST['time'])){
		$title = $_POST['title'];
		$text = $_POST['text'];
		$url = $_POST['url'];
		$time = $_POST['time'];
	} else {
		blogExit("You didn't set one of these things: Title, URL or Text");
	}
	## Update post in database
	$sql = "UPDATE posts SET post_title=?, post_text=? WHERE post_id=?";
	$conn->prepare($sql)->execute([$title, $text, $url]);
	
	## Update static post page
	$date = date("j F Y",$time);
	$post_contents = "<a id='post' href='index.php'>$title</a><br><span id='info'>$date</span><br>$text";
	blogWrite("src/$url.html", $post_contents);
	
	## Rewrite index with new title
	$stmt = $conn->query("SELECT * FROM posts");
	$stmt->execute();
	$blogposts = $stmt->fetchAll();
	## Rewrite blog index...
	$new_index = array();
	foreach ($blogposts as $post){
		$date = date("j F Y",$post['post_time']);
		$new_post = "<a class='list' href='?read=" . $post['post_id'] . "'>" . $post['post_title'] . "</a><br>$date<br><br>\n\n";
		array_push($new_index,$new_post);
	}
	$new_index = array_reverse($new_index);
	$new_index = implode("\n\n",$new_index);
	blogWrite("nav.html", $new_index);
	blogExit("Updated blogpost <b>$url</b>");
}


if ($mode == "home"){
	$stmt = $conn->query("SELECT post_id FROM posts ORDER BY post_id ASC LIMIT 0, 1");
	$stmt->execute();
	$last_id = $stmt->fetch();
	$last_id = $last_id['post_id'];
	
	echo "<div id='contain'><div id='post'>";
	if (file_exists("src/$last_id.html")){
		$lastpost = file_get_contents("src/$last_id.html");
		echo $lastpost;
	} else {
		echo "No posts to view...";
	}
	echo "</div></div>";
}

blogExecTime();
echo "</body></div>";
?>
</html>