<?php

namespace data\core;

interface SQLDataSource {
	function query($query, array $parameters = null, $return = DataSource::QUERY_RETURN_ALL);
	function exec($query, array $parameters = null);
}
