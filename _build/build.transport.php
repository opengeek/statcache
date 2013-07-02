<?php
/**
 * statcache
 *
 * @package statcache
 * @author Jason Coward <jason@modx.com>
 */
$tstart = microtime(true);
set_time_limit(0);

/* set package info */
define('PKG_NAME', 'statcache');
define('PKG_NAME_LOWER', strtolower(PKG_NAME));
define('PKG_VERSION', '1.3.0');
define('PKG_RELEASE', 'pl');

/* define sources */
$root = dirname(dirname(__FILE__)) . '/';
$sources= array (
    'root' => $root,
    'build' => $root . '_build/',
    'resolvers' => $root . '_build/resolvers/',
    'lexicon' => $root . '_build/lexicon/',
    'source_core' => $root . 'core/components/' . PKG_NAME_LOWER . '/',
);
unset($root);

/* instantiate MODx */
require_once $sources['build'].'build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(xPDO::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

/* load builder */
$modx->loadClass('transport.modPackageBuilder', '', false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace('statcache', false, true, '{core_path}components/' . PKG_NAME_LOWER . '/');

/* create plugin object */
$modx->log(xPDO::LOG_LEVEL_INFO,'Adding in plugin.'); flush();
$object= $modx->newObject('modPlugin');
$object->set('name', 'StaticCache');
$object->set('description', '<strong>'.PKG_VERSION.'-'.PKG_RELEASE.'</strong> Plugin for generating and clearing static representations of MODX Resources.');
$object->set('category', 0);
$object->set('plugincode', file_get_contents($sources['source_core'] . 'elements/plugins/StaticCache.php'));
$properties = include $sources['build'] . 'properties.inc.php';
$object->setProperties($properties);
unset($properties);


/* create a transport vehicle for the data object */
$vehicle = $builder->createVehicle($object, array(
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::UNIQUE_KEY => 'name',
));
$vehicle->resolve('php',array(
    'source' => $sources['resolvers'] . 'resolve.plugin_events.php',
));
$builder->putVehicle($vehicle);

/* now pack in the license file, readme and setup options */
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['source_core'] . 'docs/license.txt'),
    'readme' => file_get_contents($sources['source_core'] . 'docs/readme.txt'),
    'changelog' => file_get_contents($sources['source_core'] . 'docs/changelog.txt'),
    'setup_options' => array(
        'source' => $sources['build'] . 'setup.options.php',
    ),
));

/* zip up the package */
$builder->pack();

$tend= microtime(true);
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f seconds", $totalTime);

$modx->log(xPDO::LOG_LEVEL_INFO, "Package Built.");
$modx->log(xPDO::LOG_LEVEL_INFO, "Execution time: {$totalTime}");
exit();
