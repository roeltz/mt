<?php

use foo\core\Context;

Context::attach("dispatch:finish", function(){
	foreach(rglob(CFG_CACHE_PATH . "/*") as $file)
		if (!is_dir($file))
			unlink($file);
});
