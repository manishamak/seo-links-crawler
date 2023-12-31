# Seo Links Crawler
## Summary
This is a WordPress plugin which will extract internal links from home page and displays them in plugin's page on admin side so that administrator can check these links which are connected with the home page and can do seo related things. This plugin will crawl in every hour and remove old local storage and saved latest/updated storage as well as generates updated sitemap.html file which contains these internal links crawling result and will be accessible to frontend users also. It will also generate static home.html file every hour which will be the exact replica of current active theme's home page.

# What plugin is doing?
* When the plugin become activated, the Seo Links Crawler admin menu becomes visible. 
* In that page, there is a button called 'Start Crawler'. 
* On clicking that button, the crawling process begins. It will first delete the cache storage, then delete the sitemap.html containing previous crawl list. 
* It will then scan home page, gather internal links and store it in cache file. 
* Then, it will save active theme's home page as home.html in active-theme/slc-templates/home.html(in browser accessible via "site_url/wp-content/themes/active-theme-name/slc-templates/home.html" link) location. 
* It will then create sitemap.html file containing the recent crawl results and save it in plugin's own directory i.e templates/sitemap.html(in browser accessible via "site_url/wp-content/plugins/seo-links-crawler/templates/sitemap.html" link). 
* After first button click, an hourly scheduler of this whole crawling process will get started. 
* On every button click, after the whole crawling process, the results will get fetched from the cache and display it below that button. 
* If there occurs any error in fetching data from cache then it will display the results from recent scanning process and display the admin notice regarding the error. 
* If an error occurs during scanning the home page then the respective error will get displayed below the crawler button. 
* Any other error related to html files creation will get displayed as admin notice. During hourly crawling, the respective errors will get logged in the debug.log file. 
* On plugin deactivation, the hourly crawler will get deleted.

# Technical Decisions
* Used composer autoloader.
* Used ajax for starting the crawling process for better user interface.
* Used container class for easily managing the instances of dependent classes.
* Used WP_Filesystem class for reading and writing the data in the files because it simplifies and streamlines file system operations within the WordPress environment and offers compatibility, security, and integration benefits.
* Used DomDocument class for parsing the HTML because it provides a robust solution and there is no size limit unlike preg_match approach. Also it is available by default as part of core installation.
* Used Filesystem cache for local storage because there are chances of large data and if we use Transient API as database local storage then storing large amounts of data in transients can lead to increased database load and decreased performance. Stored the cache folder under wp-content folder naming 'slc-cache'.
* Saved home.html inside active theme's directory because it replicates the active theme's home page and putting it inside slc-templates folder is to avoid the same file name conflicts.
* Saved sitemap.html inside this plugins folder.
* Single instance of most of the classes are used throughout the plugin because different state of objects are not require so in order to save memory and increase performance.
* Passed all phpcs inspections.
