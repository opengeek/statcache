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
            $statcacheFile = $modx->getOption('statcache_path', $scriptProperties, MODX_BASE_PATH . 'statcache');
            if ($modx->resource->get('id') === (integer) $modx->getOption('site_start', $scriptProperties, 1)) {
                $statcacheFile .= MODX_BASE_URL . '~index.html';
            } else {
                $uri = $modx->makeUrl($modx->resource->get('id'), '', '', 'abs');
                if (substr($uri, strlen($uri) - 1) === '/' && $modx->resource->ContentType->get('mime_type') == 'text/html') {
                    $uri .= '~index.html';
                }
                $statcacheFile .= $uri;
            }
            if (!$modx->cacheManager->writeFile($statcacheFile, $modx->resource->_output)) {
                $modx->log(modX::LOG_LEVEL_ERROR, "Error caching output from Resource {$modx->resource->get('id')} to static file {$statcacheFile}", '', __FUNCTION__, __FILE__, __LINE__);
            }
        }
        break;
}
