<?php
switch ($modx->event->name) {
    case 'OnSiteRefresh':
        /* Remove all static files OnSiteRefresh */
        $modx->cacheManager->deleteTree(
            $modx->getOption('statcache_path', $scriptProperties, MODX_BASE_PATH . 'statcache'),
            array(
                'deleteTop' => false,
                'skipDirs' => false,
                'extensions' => array()
            )
        );
        break;
    case 'OnBeforeSaveWebPageCache':
        /* Write a static version of the file before caching it in MODX */
        if ($modx->resource->get('cacheable') && $modx->resource->_output != '') {
            /* skip Resources with a non-empty value for the specified TV */
            if (!empty($skipTV) && $modx->resource->getTVValue($skipTV)) break;
            /* do not cache if the cacheable content still contains unprocessed tags */
            if (!empty($skipIfTagsRemain) && $modx->parser->collectElementTags($modx->resource->_content, $matches = array())) break;
            /* build the path/filename for writing the static representation */
            $statcacheFile = $modx->getOption('statcache_path', $scriptProperties, MODX_BASE_PATH . 'statcache');
            if ($modx->resource->get('id') === (integer) $modx->getOption('site_start', $scriptProperties, 1)) {
                /* use ~index.html to represent the site_start Resource */
                $statcacheFile .= MODX_BASE_URL . '~index.html';
            } else {
                /* generate an absolute URI representation of the Resource to append to the statcache_path */
                $uri = $modx->makeUrl($modx->resource->get('id'), '', '', 'abs');
                if (substr($uri, strlen($uri) - 1) === '/' && $modx->resource->ContentType->get('mime_type') == 'text/html') {
                    /* if Resource is HTML and ends with a /, use ~index.html for the filename */
                    $uri .= '~index.html';
                }
                $statcacheFile .= $uri;
            }
            /* attempt to write the complete Resource output to the static file */
            if (!$modx->cacheManager->writeFile($statcacheFile, $modx->resource->_output)) {
                $modx->log(modX::LOG_LEVEL_ERROR, "Error caching output from Resource {$modx->resource->get('id')} to static file {$statcacheFile}", '', __FUNCTION__, __FILE__, __LINE__);
            }
        }
        break;
}
