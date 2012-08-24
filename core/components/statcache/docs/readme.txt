--------------------
Extra: statcache
--------------------
Version: 1.0.0-pl
Released: August 24, 2012
Since: August 24, 2012
Author: Jason Coward <jason@modx.com>

The statcache extra is made up of a simple Plugin to write (OnBeforeSaveWebPageCache) and clear (OnSiteRefresh) static files representing MODX Resources. Using rewrite rules available in various web server engines, you can intercept user requests for your MODX Resources, serving the static files instead. Which Resources are to be cached (and where) can be configured through the Plugin properties.

WARNING: All static files generated will be served based on the web server configuration until removed/updated, as MODX is no longer involved at that point.

Official Documentation at https://github.com/opengeek/statcache/wiki
