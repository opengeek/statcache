<?php
/*
 * This file is part of the statcache package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @var modX $modx
 * @var array $scriptProperties
 *
 * @var
 */

require_once MODX_CORE_PATH . 'components/statcache/lib/StatCache.php';

if (!empty($contexts)) {
    $contexts = explode(',', $contexts);
    array_walk($contexts, 'trim');
} else {
    $contexts = array();
}

switch ($modx->event->name) {
    case 'OnSiteRefresh':
        if (!empty($regenerate)) {
            /* Regenerate all static files OnSiteRefresh */
            $regenQuery = $modx->newQuery('modResource');
            $conditions = array(
                'cacheable' => true,
                'published' => true,
                'class_key:IN' => array('modDocument', 'modSymLink', 'modStaticResource')
            );
            if (!empty($contexts)) {
                $conditions['context_key:IN'] = $contexts;
            }
            if (!empty($cacheTV)) {
                $conditions[] = array("EXISTS (SELECT 1 FROM {$modx->getTableName('modTemplateVarResource')} `tvr` JOIN {$modx->getTableName('modTemplateVar')} `tv` ON `tvr`.`value` = '1' AND `tv`.`name` = {$modx->quote($cacheTV)} AND `tv`.`id` = `tvr`.`tmplvarid` WHERE `tvr`.`contentid` = `modResource`.`id`)");
            } else {
                $contentTypes = array();
                if (!empty($contentTypes)) {
                    $validContentTypes = array_walk(explode(',', $contentTypes), 'trim');
                    $contentTypes = $validContentTypes;
                }
                if (!empty($mimeTypes)) {
                    $validMimeTypes = array_walk(explode(',', strtolower($mimeTypes)), 'trim');
                    $query = $modx->newQuery('modContentType', array('mime_type:IN' => $validMimeTypes));
                    $query->select(array('id'));
                    $query->prepare()->execute();
                    $validContentTypes = $query->stmt->fetchAll(PDO::FETCH_COLUMN);
                    if (empty($contentTypes)) {
                        $contentTypes = $validContentTypes;
                    } else {
                        $contentTypes = array_intersect($contentTypes, $validContentTypes);
                    }
                }
                if (!empty($skipBinaryContentTypes)) {
                    $query = $modx->newQuery('modContentType', array('binary' => false));
                    $query->select(array('id'));
                    $query->prepare()->execute();
                    $validContentTypes = $query->stmt->fetchAll(PDO::FETCH_COLUMN);
                    if (empty($contentTypes)) {
                        $contentTypes = $validContentTypes;
                    } else {
                        $contentTypes = array_intersect($contentTypes, $validContentTypes);
                    }
                }
                if (!empty($contentTypes)) {
                    $conditions['content_type:IN'] = $contentTypes;
                }
            }
            $regenQuery->where($conditions);
            $regenQuery->select(array('id'));
            $regenQuery->prepare()->execute();
            $resources = $regenQuery->stmt->fetchAll(PDO::FETCH_COLUMN);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_USERAGENT, $modx->getOption('regenerate_useragent', $scriptProperties, 'MODX RegenCache'));
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_NOBODY, true);
            foreach ($resources as $resource) {
                set_time_limit(0);
                $url = $modx->makeUrl($resource, '', '', 'full');
                if (!empty($url)) {
                    $modx->log(modX::LOG_LEVEL_INFO, "Requesting Resource at {$url}");
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_exec($curl);
                    $modx->log(modX::LOG_LEVEL_INFO, "Updated cache for resource at {$url}");
                }
            }
            curl_close($curl);
        } else {
            /* Remove all static files OnSiteRefresh */
            $modx->cacheManager->deleteTree(
                $modx->getOption('statcache_path', $scriptProperties, MODX_BASE_PATH . 'statcache'),
                array(
                    'deleteTop' => false,
                    'skipDirs' => false,
                    'extensions' => array(),
                    'delete_exclude_items' => explode(',', $modx->getOption('statcache_delete_exclude', $scriptProperties, ''))
                )
            );
        }
        break;
    case 'OnBeforeSaveWebPageCache':
        /* Write a static version of the file before caching it in MODX */
        if ($modx->resource->get('cacheable') && $modx->resource->get('published') && $modx->resource->_output != '') {
            if (empty($contexts) || in_array($modx->resource->get('context_key'), $contexts)) {
                /* force caching of Resources with a value of '1' for a specified cacheTV */
                $forced = !empty($cacheTV);
                if (!$forced) {
                    /* optionally skip binary content types */
                    if (!empty($skipBinaryContentTypes) && $modx->resource->ContentType->get('binary')) break;
                    /* do not cache if the cacheable content still contains unprocessed tags */
                    /* skip Resources with a non-empty value for the specified TV */
                    if (!empty($skipTV) && $modx->resource->getTVValue($skipTV)) break;
                    $matches = array();
                    if (!empty($skipIfTagsRemain) && $modx->parser->collectElementTags($modx->resource->_content, $matches)) break;
                    /* if specified, limit caching by mime-type */
                    if (!empty($mimeTypes)) {
                        $validMimeTypes = array_walk(explode(',', strtolower($mimeTypes)), 'trim');
                        if (!in_array(strtolower($modx->resource->ContentType->get('mime_type')), $validMimeTypes)) break;
                    }
                    /* if specified, limit caching by ContentTypes */
                    if (!empty($contentTypes)) {
                        $validContentTypes = array_walk(explode(',', $contentTypes), 'trim');
                        if (!in_array($modx->resource->ContentType->get('id'), $validContentTypes)) break;
                    }
                }
                elseif ($modx->resource->getTVValue($cacheTV) !== '1') {
                    break;
                }
                /* build the path/filename for writing the static representation */
                $statcacheFile = StatCache::getInstance($modx, $scriptProperties)->getStaticPath($modx->resource, $scriptProperties);

                /* attempt to write the complete Resource output to the static file */
                if (!$modx->cacheManager->writeFile($statcacheFile, $modx->resource->_output)) {
                    $modx->log(modX::LOG_LEVEL_ERROR, "Error caching output from Resource {$modx->resource->get('id')} to static file {$statcacheFile}", '', __FUNCTION__, __FILE__, __LINE__);
                }
            }
        }
        break;
    case 'OnDocFormSave':
        if (empty($contexts) || in_array($modx->resource->get('context_key'), $contexts)) {
            if (empty($reloadOnly) && $modx->getOption('regenerate_on_save', $scriptProperties, true) && $resource->get('cacheable') && $resource->get('published')) {
                /* Write a new static version of the file (if it already exists) when changes are saved */
                $statcacheFile = StatCache::getInstance($modx, $scriptProperties)->getStaticPath($resource, $scriptProperties);
                if (is_readable($statcacheFile)) {
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_USERAGENT, $modx->getOption('regenerate_useragent', $scriptProperties, 'MODX RegenCache'));
                    curl_setopt($curl, CURLOPT_FAILONERROR, false);
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_NOBODY, true);
                    $url = $modx->makeUrl($resource->get('id'), $resource->get('context_key'), '', 'full');
                    if (!empty($url)) {
                        $modx->log(modX::LOG_LEVEL_INFO, "Requesting Resource at {$url}");
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_exec($curl);
                        $modx->log(modX::LOG_LEVEL_INFO, "Updated cache for resource at {$url}");
                    }
                    curl_close($curl);
                }
                else {
                    $modx->log(modX::LOG_LEVEL_WARN, "No existing static cache file at {$statcacheFile}");
                }
            }
            elseif (empty($reloadOnly) && ($resource->get('published') === false || $resource->get('deleted') === true)) {
                /* delete a representation if it is unpublished or deleted */
                $statcacheFile = StatCache::getInstance($modx, $scriptProperties)->getStaticPath($resource, $scriptProperties);
                if (is_readable($statcacheFile)) {
                    if (@unlink($statcacheFile) === false) {
                        $modx->log(modX::LOG_LEVEL_ERROR, "Error removing static file {$statcacheFile}", '', "statcache [{$modx->event->name}]");
                    }
                    else {
                        $modx->log(modX::LOG_LEVEL_INFO, "Removed static file {$statcacheFile}", '', "statcache [{$modx->event->name}]");
                    }
                }
            }
        }
        break;
    case 'OnResourceDelete':
    case 'OnDocUnPublished':
        if (empty($contexts) || in_array($modx->resource->get('context_key'), $contexts)) {
            /* delete a representation if it is unpublished or deleted */
            $statcacheFile = StatCache::getInstance($modx, $scriptProperties)->getStaticPath($resource, $scriptProperties);
            if (is_readable($statcacheFile)) {
                if (@unlink($statcacheFile) === false) {
                    $modx->log(modX::LOG_LEVEL_ERROR, "Error removing static file {$statcacheFile}", '', "statcache [{$modx->event->name}]");
                }
                else {
                    $modx->log(modX::LOG_LEVEL_INFO, "Removed static file {$statcacheFile}", '', "statcache [{$modx->event->name}]");
                }
            }
        }
        break;
}
