<?php

namespace data\core;

interface TransactionalDataSource {
	function beginTransaction();
	function commit();
	function rollback();
}
