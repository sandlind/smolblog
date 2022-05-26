# smolBlog
Light-weight blog made in PHP 7.4, using sqlite3 for data storage and retrieval

## How to install

1. Extract the release into a web server that runs PHP 7.4 and has PDO (PHP Data Objects) library, and the sqlite3 driver for PDO. 
2. Change your admin password at **setting_admin_pass**. 
3. Set file permission of *blog.db* to **777**. 
4. Set file permission to the entire */src/* directory to **777**.

You should now have a fully functioning light-weight blog. 

## How it works

smolBlog saves HTML pages of blog posts to the /src/ directory so the web server can statically load blog posts, saving you processing power. smolBlog uses a sqlite3 database to save posts as secondary storage if your static pages happen to break. 

## Need help?

Join **#dulm** on Rizon IRC.
