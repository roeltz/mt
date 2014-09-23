<?php

use foo\util\cache\Cache;
use foo\util\cache\sources;

Cache::set("memory", new sources\InMemoryCacheSource());
Cache::set("file", new sources\FileCacheSource());
