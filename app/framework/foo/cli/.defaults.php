<?php

use foo\core\Request;

Request::setProfile('foo\\cli\\CliContext', 'windows', '/winnt/i');
Request::setProfile('foo\\cli\\CliContext', 'linux', '/linux/i');
