<?php
/**
 * framework/Layout.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace framework;

use \Exception;
use \DOMDocument;

use \urls\API;



class LayoutException extends Exception {}

abstract class Layout {
	protected object $api;
	protected int $response_header;
	protected array $exported_vars;
	public string $template_file_ext = 'php';
	public string $template_path;

	public function __construct(array $config) {
		$this->config = $config;
		$this->api = new API($this->config, false);

		try {
			$this->api->run();

			$route = $this->api->getRoute();

			$this->prepare($route);
		} catch (Exception $error) {
			print($this->error(500));

			throw $error;
		}
	}

	public function prepare(array $route) {
		$file = empty($route) ? 'index' : $route[0];

		if ($this->template($file)) {
			$this->api->call();

			$this->render();

			$this->view($file);
		}
	}

	public function template(string $filename) {
		if (file_exists($this->template_path . '/' . $filename . '.' . $this->template_file_ext))
			return $file;

		return false;
	}

	public function render() {
		$this->response_header = $this->api->getResponseHeader();

		$this->header($this->response_header);

		if ($this->response_header > 204)
			$this->error($this->response_header);

		$this->exported_vars = $this->api->getResponseRaw();
	}

	public function view(string $filename) {
		if (! file_exists($this->template_path . '/' . $filename))
			throw new Exception('Error Processing Request');

		\extract($this->exported_vars, EXTR_PREFIX_SAME, __NAMESPACE__);

		include $file;
	}

	public function error(int $response_code) {
		die("{$response_code} Error");
	}

	public function header(int $response_code) {
		header("Status: {$response_code}", true, $response_code);
	}
}


class BackendLayout extends Layout {
	public object $template;
	public string $template_file_ext = 'html';
	public string $template_path = __DIR__ . '/../backend';

	public function template(string $filename) {
		libxml_use_internal_errors(true);

		$this->template = new DOMDocument;
		$this->template->loadHTMLFile($this->template_path . '/index.' . $this->template_file_ext);

		return true;
	}

	public function view(string $filename) {
		echo $this->template->saveHTML();
	}

	public function error(int $response_code) {
	}
}
