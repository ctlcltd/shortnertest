<?php
/**
 * framework/urls_Controller.php
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version staging
 * @license MIT License
 */

namespace urls;

use \Exception;
use \stdClass;

use \urls\Controller;
use \urls\ControllerException;
use \urls\Shortner;


/**
 * Interface for controller class
 *
 * @interface
 */
interface Urls_ControllerInterface {
	public function store_add($domain_id, $url);
	public function store_delete($store_id);
	public function store_list($domain_id);
	public function domain_get($domain_id);
	public function domain_list($user_id);
	public function domain_add($master, $service);
	public function domain_update($domain_id, $master, $service);
	public function domain_delete($domain_id, $purge);
	public function user_get_by_id($user_id);
	public function user_get_by_name($user);
	public function user_add($user_acl, $user_email, $user_name, $user_password, $user_notify);
	public function user_delete($user_id, $purge);
	public function user_match($user_email, $user_name, $user_password);
}

/**
 * @class
 */
class Urls_Controller extends Controller implements Urls_ControllerInterface {
	// private $config, $data, $enable_shadow, $user;

	// public function __construct($config, $data) {
	// 	parent::__construct($config, $data);

	// 	//var_dump($this);

	// 	$this->user = NULL;
	// 	$this->list = NULL;
	// }

	public $config, $data, $enable_shadow, $user;

	public function __construct($config, $data) {
		$this->config = $config;
		$this->data = $data;
		$this->enable_shadow = $this->config['Database']['dbshadow'];
		$this->need_preauth = true;

		$this->user = NULL;
		$this->list = NULL;
	}

	public function store_get_by_id($store_id) {
		$event = $this->call('store', 'get_by_id', false);

		$user_id = $this->user->id;

		$this->data->fetch('store', $this->list);

		$this->data->where('store_id', $user_id);

		return $this->data->run();
	}

	public function store_get_by_slug($store_slug) {
		$event = $this->call('store', 'get_by_slug', false);

		$user_id = $this->user->id;

		$this->data->fetch('store', $this->list, true);

		$this->data->where('store_slug', $store_slug);

		return $this->data->run();
	}

	public function store_list($domain_id) {
		$event = $this->call('store', 'list', false);

		$user_id = $this->user->id;

		$this->data->fetch('store', $this->list);

		$this->data
			->where('user_id', $user_id)
			->where('domain_id', $domain_id);

		return $this->data->run();
	}

	public function store_add($domain_id, $url) {
		$event = $this->call('store', 'add');

		if (! filter_var($url, FILTER_VALIDATE_URL))
			throw new ControllerException('Not a valid URL');

		//same domain ?

		$this->data->add('store');

		$user_id = $this->user->id;
		$store_id = $this->uniqid('store', $event);

		//sanitize with fragment

		$store_url = $url;
		$shortner = Shortner::shortner($store_url);

		$this->data
			->set('store_id', $store_id)
			->set('user_id', $user_id)
			->set('domain_id', $domain_id)
			->set('store_index', $shortner->index)
			->set('store_slug', $shortner->slug)
			->set('store_url', $store_url)
			->set('event', $event->getToken())
			->set('store_time_created', $event->getTime());

		return $this->data->run();
	}

	public function store_update($store_id, $url) {
		$event = $this->call('store', 'update');

		if (! filter_var($url, FILTER_VALIDATE_URL))
			throw new ControllerException('Not a valid URL');

		//same domain ?

		$this->data->fetch('store', ['store_url']);

		$row = $this->data->run();

		//sanitize with fragment

		$store_url = $url;

		if ($row['store_url'] === $store_url)
			return false;

		$this->shadow($event, 'store', ['store_id' => $store_id]);

		$this->data->update('store');

		$shortner = Shortner::shortner($store_url);

		$this->data
			->where('store_id', $store_id)
			->set('store_index', $shortner->index)
			->set('store_slug', $shortner->slug)
			->set('store_url', $store_url)
			->set('event', $event->getToken())
			->set('store_time_modified', $event->getTime());

		return $this->data->run();
	}

	public function store_delete($store_id) {
		$event = $this->call('store', 'delete');

		$this->shadow($event, 'store', ['store_id' => $store_id]);

		$this->data->remove('store');

		$this->data->where('store_id', $store_id);

		return $this->data->run();
	}

	public function domain_get($domain_id) {
		$event = $this->call('domain', 'get', false);

		$this->data->fetch('domains', $this->list, true);

		$this->data->where('domain_id', $domain_id);

		return $this->data->run();
	}

	public function domain_list($user_id) {
		$event = $this->call('domain', 'list', false);

		$this->data->fetch('domains', $this->list);

		$this->data->where('user_id', $user_id);

		return $this->data->run();
	}

	public function domain_add($master, $service) {
		$event = $this->call('domain', 'add');

		if (! filter_var($master, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
			throw new ControllerException('Not a valid master domain');

		if (! filter_var($service, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
			throw new ControllerException('Not a valid service domain');

		$this->data->add('domains');

		$user_id = $this->user->id;
		$domain_id = $this->uniqid('domain', $event);

		$this->data
			->set('domain_id', $domain_id)
			->set('user_id', $user_id)
			->set('domain_master', $master)
			->set('domain_service', $service)
			->set('event', $event->getToken())
			->set('domain_time_created', $event->getTime());

		return $this->data->run();
	}

	public function domain_update($domain_id, $master, $service) {
		$event = $this->call('domain', 'update');

		if ($master && ! filter_var($master, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
			throw new ControllerException('Not a valid master domain');

		if ($service && ! filter_var($service, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
			throw new ControllerException('Not a valid service domain');

		$this->shadow($event, 'domains', ['domain_id' => $domain_id]);

		$this->data->update('domains');

		$this->data->where('domain_id', $domain_id);

		$master || $this->data->set('domain_master', $master);
		$service || $this->data->set('domain_service', $service);

		$this->data
			->set('event', $event->getToken())
			->set('domain_time_modified', $event->getTime());

		return $this->data->run();
	}

	public function domain_delete($domain_id, $purge) {
		$event = $this->call('domain', 'delete');

		$this->shadow($event, 'domains', ['domain_id' => $domain_id]);

		$this->data->remove('domains');

		$this->data->set('domain_id', $domain_id);

		return $this->data->run();
	}

	public function user_get_by_id($user_id) {
		$event = $this->call('user', 'get_by_id', false);

		$this->data->fetch('users', $this->list, true);

		$this->data->where('user_id', $user_id);

		return $this->data->run();
	}

	public function user_get_by_name($user_name) {
		$event = $this->call('user', 'get_by_name', false);

		$this->data->fetch('users', $this->list, true);

		$this->data->where('user_name', $user_name);

		return $this->data->run();
	}

	public function user_list() {
		$event = $this->call('user', 'list', false);

		$this->data->fetch('users', $this->list);

		return $this->data->run();
	}

	public function user_add($user_acl, $user_email, $user_name, $user_password, $user_notify) {
		$event = $this->call('user', 'add');

		if (! filter_var($user_email, FILTER_VALIDATE_EMAIL))
			throw new ControllerException('Not a valid e-mail address');

		$user_email = filter_var($user_email, FILTER_SANITIZE_EMAIL);

		//check & sanitize
		$user_name = trim($user_name);

		if ($this->user_get_by_name($user_name))
			throw new ControllerException('User name already exists');

		$user_acl = $user_acl ? $user_acl : $this->config["Network"]["nwuseracl"];

		$this->data->add('users');

		$user_id = $this->uniqid('user', $event);
		$user_pass = password_hash($user_password, PASSWORD_DEFAULT);
		$user_pending = $this->pending('activation');
		$user_notify = $user_notify ? (int) $user_notify : 0;

		$this->data
			->set('user_id', $user_id)
			->set('user_acl', $user_acl)
			->set('user_pending', $user_pending)
			->set('user_email', $user_email)
			->set('user_name', $user_name)
			->set('user_pass', $user_pass)
			->set('user_notify', $user_notify)
			->set('event', $event->getToken())
			->set('user_time_created', $event->getTime());

		//auth
		//mail

		return $this->data->run();
	}

	public function user_update($user_id, $user_acl, $user_email, $user_name, $user_password, $user_notify) {
		$event = $this->call('user', 'update');

		if (! $user_id)
			$user_id = $this->user->id;

		if ($user_email) {
			if (! filter_var($user_email, FILTER_VALIDATE_EMAIL))
				throw new ControllerException('Not a valid e-mail address');

			$user_email = filter_var($user_email, FILTER_SANITIZE_EMAIL);
		}

		if ($user_name) {
			//check & sanitize
			$user_name = trim($user_name);

			if ($this->user_get_by_name($user_name))
				throw new ControllerException('User name already exists');
		}

		$this->shadow($event, 'users', ['user_id' => $user_id]);

		$this->data->update('users');

		$this->data->where('user_id', $user_id);

		if ($user_pass)
			$user_pass = password_hash($user_password, PASSWORD_DEFAULT);

		$user_acl || $this->data->set('user_acl', $user_acl);
		$user_email || $this->data->set('user_email', $user_email);
		$user_name || $this->data->set('user_name', $user_name);
		$user_pass || $this->data->set('user_pass', $user_pass);

		$this->data
			->set('event', $event->getToken())
			->set('user_time_modified', $event->getTime());

		//auth
		//mail

		return $this->data->run();
	}

	public function user_delete($user_id, $purge) {
		$event = $this->call('user', 'delete');

		$this->shadow($event, 'users', ['user_id' => $user_id]);

		$this->data->remove('users');

		$this->data->set('user_id', $user_id);

		return $this->data->run();
	}

	public function user_activation($email, $token) {
		$event = $this->call('user', 'activation', true, false);

		if (! filter_var($email, FILTER_VALIDATE_EMAIL))
			throw new ControllerException('Not a valid e-mail address');

		//email privacy another public ID
		//validate & sanitize

		$user_email = filter_var($email, FILTER_SANITIZE_EMAIL);
		$activation_token = $token;

		$this->data->fetch('users', ['user_id', 'user_time_created', 'user_pending'], true);

		$this->data->where('user_email', $user_email);

		$row = $this->data->run();

		if (empty($row))
			throw new ControllerException('Unknown user');

		$user_id = $row['user_id'];
		$user_created_epoch = strtotime($row['user_time_created']);
		$user_pending = empty($row['user_pending']) ? false : json_decode($row['user_pending']);

		$action_lifetime = (int) $this->config['Network']['nwuseractionlifetime'];
		$user_action_lifetime = ($user_created_epoch + $action_lifetime);

		if (! $user_pending)
			throw new ControllerException('User already activated');
		else if ($event->epoch > $user_action_lifetime)
			throw new ControllerException('User activation expired');
		else if ($user_pending->digest !== $token)
			throw new ControllerException('Bad request');

		$this->shadow($event, 'users', ['user_id' => $user_id]);

		$this->data->update('users');

		$this->data->where('user_id', $user_id);

		$this->data->set('user_pending', NULL);

		$this->data
			->set('event', $event->getToken())
			->set('user_time_modified', $event->getTime());

		return $this->data->run();
	}

	public function user_match($user_email, $user_name, $user_password) {
		$this->data->fetch('users', ['user_id', 'user_acl', 'user_name', 'user_pass'], true);

		if ($user_email) $this->data->where('user_email', $user_email);
		else $this->data->where('user_name', $user_name);

		$row = $this->data->run();

		if (isset($row['user_id']))
			$row['user_match'] = password_verify($user_password, $row['user_pass']);

		return $row;
	}

	public function install() {
		parent::install();

		$this->data->fetch('users', ['user_id']);

		$this->data->count()->limit(1);

		$count = $this->data->run();

		if (! empty($count))
			throw new ControllerException('Already installed');

		$user_acl = '*';
		$user_notify = true;

		$row = $this->user_add($user_acl, $user_email, $user_name, $user_password, $user_notify);

		return $row;
	}
}