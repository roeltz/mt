<?php

define('CFG_FRAMEWORK_PATH', 'app/framework');
define('CFG_CACHE_PATH', 'app/cache');

require_once CFG_FRAMEWORK_PATH . '/foo/core/.include.php';

use foo\core\Importer;
use foo\core\Context;
use foo\util\ConstantSettings;

Importer::addPackage(CFG_FRAMEWORK_PATH . '/foo/core');
Importer::addPackage(CFG_FRAMEWORK_PATH . '/foo/cli');
Importer::addPackage('app');
ConstantSettings::load("mt.conf", "MT");

Context::hook('no-cache');
