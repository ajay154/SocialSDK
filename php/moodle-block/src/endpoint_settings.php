***REMOVED***
global $CFG;
if (!isset($CFG) || !isset($CFG->wwwroot)) {
	$path = str_replace('blocks/ibmsbt', '', __DIR__);
	include_once $path . '/config.php';
}

if (!defined('ENDPOINTS')) {
	define('ENDPOINTS', 'ibm_sbt_endpoints');
}

// TODO: Client token

if (isset($_POST['type'])) {
	global $DB;
	global $USER;

	// Create a new endpoint
	if ($_POST['type'] == 'create') {
		$record = new stdClass();
		$record->created_by_user_id = intval($USER->id);
		
		if (isset($_POST['allow_client_access'])) {
			$record->allow_client_access = mysql_real_escape_string($_POST['allow_client_access']);
		}
		
		if (isset($_POST['server_type'])) {
			$record->server_type = mysql_real_escape_string($_POST['server_type']);
		}
		
		if (isset($_POST['api_version'])) {
			$record->api_version = mysql_real_escape_string($_POST['api_version']);
		}
		
		if (isset($_POST['api_version'])) {
			$record->force_ssl_trust = mysql_real_escape_string($_POST['force_ssl_trust']);
		}
		
		if (isset($_POST['basic_auth_method'])) {
			$record->basic_auth_method = mysql_real_escape_string($_POST['basic_auth_method']);
		}
		
		if (isset($_POST['basic_auth_password'])) {
			$record->basic_auth_password = mysql_real_escape_string($_POST['basic_auth_password']);
		}
		
		if (isset($_POST['basic_auth_username'])) {
			$record->basic_auth_username = mysql_real_escape_string($_POST['basic_auth_username']);
		}
		
		if (isset($_POST['auth_type'])) {
			$record->auth_type = mysql_real_escape_string($_POST['auth_type']);
		}
		
		if (isset($_POST['authorization_url'])) {
			$record->authorization_url = mysql_real_escape_string($_POST['server_url']) . mysql_real_escape_string($_POST['authorization_url']);
		}
		
		if (isset($_POST['oauth2_callback_url'])) {
			$record->oauth2_callback_url = mysql_real_escape_string($_POST['oauth2_callback_url']);
		}
		
		if (isset($_POST['request_token_url'])) {
			$record->request_token_url = mysql_real_escape_string($_POST['server_url']) . mysql_real_escape_string($_POST['request_token_url']);
		}
		
		if (isset($_POST['client_secret'])) {
			$record->client_secret = mysql_real_escape_string($_POST['client_secret']);
		}
		
		if (isset($_POST['client_id'])) {
			$record->client_id = mysql_real_escape_string($_POST['client_id']);
		}
		
		if (isset($_POST['consumer_secret'])) {
			$record->consumer_secret = mysql_real_escape_string($_POST['consumer_secret']);
		}
		
		if (isset($_POST['consumer_key'])) {
			$record->consumer_key = mysql_real_escape_string($_POST['consumer_key']);
		}
		
		if (isset($_POST['access_token_url'])) {
			$record->access_token_url = mysql_real_escape_string($_POST['server_url']) . mysql_real_escape_string($_POST['access_token_url']);
		}
		
		if (isset($_POST['server_url'])) {
			$record->server_url = mysql_real_escape_string($_POST['server_url']);
		}
		
		if (isset($_POST['name'])) {
			$record->name = mysql_real_escape_string($_POST['name']);
		}
		
		$ret = $DB->insert_record(ENDPOINTS, $record);
		var_dump($ret);
		return $ret;
	} else if ($_POST['type'] == 'delete') {
		$DB->delete_records(ENDPOINTS, array('id' => mysql_real_escape_string(intval($_POST['id']))));
	} else if ($_POST['type'] == 'update') {
		$record = $DB->get_record(ENDPOINTS, array('id' => mysql_real_escape_string(intval($_POST['id']))));
		
		if ($record == null) {
			return;
		}
		
		if (isset($_POST['allow_client_access'])) {
			$record->allow_client_access = mysql_real_escape_string($_POST['allow_client_access']);
		}
		
		if (isset($_POST['server_type'])) {
			$record->server_type = mysql_real_escape_string($_POST['server_type']);
		}
		
		if (isset($_POST['api_version'])) {
			$record->api_version = mysql_real_escape_string($_POST['api_version']);
		}
		
		if (isset($_POST['force_ssl_trust'])) {
			$record->force_ssl_trust = mysql_real_escape_string($_POST['force_ssl_trust']);
		}
		
		if (isset($_POST['basic_auth_method'])) {
			$record->basic_auth_method = mysql_real_escape_string($_POST['basic_auth_method']);
		}
		
		if (isset($_POST['basic_auth_password'])) {
			$record->basic_auth_password = mysql_real_escape_string($_POST['basic_auth_password']);
		}
		
		if (isset($_POST['basic_auth_username'])) {
			$record->basic_auth_username = mysql_real_escape_string($_POST['basic_auth_username']);
		}
		
		if (isset($_POST['auth_type'])) {
			$record->auth_type = mysql_real_escape_string($_POST['auth_type']);
		}
		
		if (isset($_POST['authorization_url'])) {
			$record->authorization_url = mysql_real_escape_string($_POST['server_url']) . mysql_real_escape_string($_POST['authorization_url']);
		}
		
		if (isset($_POST['oauth2_callback_url'])) {
			$record->oauth2_callback_url = mysql_real_escape_string($_POST['oauth2_callback_url']);
		}
		
		if (isset($_POST['request_token_url'])) {
			$record->request_token_url = mysql_real_escape_string($_POST['server_url']) . mysql_real_escape_string($_POST['request_token_url']);
		}
		
		if (isset($_POST['client_secret'])) {
			$record->client_secret = mysql_real_escape_string($_POST['client_secret']);
		}
		
		if (isset($_POST['client_id'])) {
			$record->client_id = mysql_real_escape_string($_POST['client_id']);
		}
		
		if (isset($_POST['consumer_secret'])) {
			$record->consumer_secret = mysql_real_escape_string($_POST['consumer_secret']);
		}
		
		if (isset($_POST['consumer_key'])) {
			$record->consumer_key = mysql_real_escape_string($_POST['consumer_key']);
		}
		
		if (isset($_POST['access_token_url'])) {
			$record->access_token_url = mysql_real_escape_string($_POST['server_url']) . mysql_real_escape_string($_POST['access_token_url']);
		}
		
		if (isset($_POST['server_url'])) {
			$record->server_url = mysql_real_escape_string($_POST['server_url']);
		}
		
		if (isset($_POST['name'])) {
			$record->name = mysql_real_escape_string($_POST['name']);
		}
			
		$DB->update_record(ENDPOINTS, $record);
	} else if ($_POST['type'] == 'get') {
		$record = $DB->get_record(ENDPOINTS, array('id' => mysql_real_escape_string(intval($_POST['id']))));
		
		if ($record == null) {
			return;
		}
		
		$endpoint = array();
		
		$endpoint['allow_client_access'] = $record->allow_client_access;
		$endpoint['server_type'] = $record->server_type;
		$endpoint['api_version'] = $record->api_version;
		$endpoint['force_ssl_trust'] = $record->force_ssl_trust;
		$endpoint['basic_auth_method'] = $record->basic_auth_method;
		$endpoint['basic_auth_password'] = $record->basic_auth_password;
		$endpoint['basic_auth_username'] = $record->basic_auth_username;
		$endpoint['auth_type'] = $record->auth_type;
		$endpoint['authorization_url'] = $record->authorization_url;
		$endpoint['oauth2_callback_url'] = $record->oauth2_callback_url;
		$endpoint['request_token_url'] = $record->request_token_url;
		$endpoint['client_secret'] = $record->client_secret;
		$endpoint['client_id'] = $record->client_id;
		$endpoint['consumer_secret'] = $record->consumer_secret;
		$endpoint['consumer_key'] = $record->consumer_key;
		$endpoint['access_token_url'] = $record->access_token_url;
		$endpoint['server_url'] = $record->server_url;
		$endpoint['name'] = $record->name;
		
		echo json_encode($endpoint);
	}
}