<?php

namespace foo\http\view;
use foo\core\Dispatch;
use foo\core\View;

class JsonView implements View {
	
	function render(Dispatch $dispatch) {
		$json = @json_encode(@$dispatch->response->result->data);
		$callback = @$dispatch->request->data['callback'];
		
		if (@$dispatch->request->data['script']) { // script tag
			$dispatch->response->setHeader("Content-Type: text/html");
			echo "<script>$callback($json);</script>";
		} elseif ($callback) { //jsonp
			$dispatch->response->setHeader("Content-Type: text/javascript");
			$json = "$callback($json);";
		} else {
			$dispatch->response->setHeader("Content-Type: application/json");
			echo $json;
		}
	}
}
