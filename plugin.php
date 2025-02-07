<?php
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **

// PLUGIN INFORMATION - This should match what is in plugin.json
$GLOBALS['plugins']['awx'] = [ // Plugin Name
	'name' => 'awx', // Plugin Name
	'author' => 'TinyTechLabUK', // Who wrote the plugin
	'category' => 'Ansible AWX', // One to Two Word Description
	'link' => 'https://github.com/PHP-EF/plugin-awx', // Link to plugin info
	'version' => '1.0.3', // SemVer of plugin
	'image' => 'logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'api' => '/api/plugin/awx/settings', // api route for settings page, or null if no settings page
];

class awxPlugin extends phpef {
	public function __construct() {
		parent::__construct();
	}

	public function _pluginGetSettings() {
		$Ansible = new awxPluginAnsible();
		$AnsibleLabels = $Ansible->GetAnsibleLabels() ?? null;
		$AnsibleLabelsKeyValuePairs = [];
		if ($AnsibleLabels) {
			$AnsibleLabelsKeyValuePairs = array_merge($AnsibleLabelsKeyValuePairs, array_map(function($item) {
				if (is_array($item) && isset($item['name'])) {
					return [
						"name" => $item['name'],
						"value" => $item['name']
					];
				}
				// If item is a string or doesn't have 'name' key, use it directly
				$value = is_string($item) ? $item : '';
				return [
					"name" => $value,
					"value" => $value
				];
			}, $AnsibleLabels));
		}
		return array(
			'Plugin Settings' => array(
				$this->settingsOption('auth', 'ACL-READ', ['label' => 'CMDB Read ACL']),
				$this->settingsOption('auth', 'ACL-WRITE', ['label' => 'CMDB Write ACL']),
				$this->settingsOption('auth', 'ACL-ADMIN', ['label' => 'CMDB Admin ACL']),
				$this->settingsOption('auth', 'ACL-JOB', ['label' => 'Grants access to use Ansible Integration'])
			),
			'Ansible Settings' => array(
				$this->settingsOption('url', 'Ansible-URL', ['label' => 'Ansible AWX URL']),
				$this->settingsOption('token', 'Ansible-Token', ['label' => 'Ansible AWX Token']),
				$this->settingsOption('select2', 'Ansible-Tag', ['label' => 'The tag(s) to use to restrict available jobs', 'options' => $AnsibleLabelsKeyValuePairs, 'settings' => '{tags: false, closeOnSelect: true, allowClear: true, width: "100%"}'])
			),
		);
	}
}

class awxPluginAnsible extends awxPlugin {
	public function __construct() {
	  parent::__construct();
	}

	public function QueryAnsible($Method, $Uri, $Data = "") {
		$awxConfig = $this->config->get("Plugins","awx");
		$AnsibleUrl = $awxConfig['Ansible-URL'] ?? null;

        if (!isset($awxConfig['Ansible-Token']) || empty($awxConfig['Ansible-Token'])) {
            $this->api->setAPIResponse('Error','Ansible API Key Missing');
            $this->logging->writeLog("AWX","Ansible API Key Missing","error");
            return false;
        } else {
            try {
                $AnsibleApiKey = decrypt($awxConfig['Ansible-Token'],$this->config->get('Security','salt'));
            } catch (Exception $e) {
                $this->api->setAPIResponse('Error','Unable to decrypt Ansible API Key');
                $this->logging->writeLog('AWX','Unable to decrypt Ansible API Key','error');
                return false;
            }
        }

		if (!$AnsibleUrl) {
				$this->api->setAPIResponse('Error','Ansible URL Missing');
				return false;
		}

		$AnsibleHeaders = array(
		 'Authorization' => "Bearer $AnsibleApiKey",
		 'Content-Type' => "application/json"
		);

		if (strpos($Uri,$AnsibleUrl."/api/") === FALSE) {
		  $Url = $AnsibleUrl."/api/v2/".$Uri;
		} else {
		  $Url = $Uri;
		}

		if ($Method == "get") {
			$allResults = [];
			$nextUrl = $Url;
			
			while ($nextUrl) {
				$Result = $this->api->query->$Method($nextUrl, $AnsibleHeaders, null, true);
				if (isset($Result->status_code) && $Result->status_code >= 400) {
					switch($Result->status_code) {
						case 401:
							$this->api->setAPIResponse('Error','Ansible API Key incorrect or expired');
							$this->logging->writeLog("Ansible","Error. Ansible API Key incorrect or expired.","error");
							return false;
						case 404:
							$this->api->setAPIResponse('Error','HTTP 404 Not Found');
							return false;
						default:
							$this->api->setAPIResponse('Error','HTTP '.$Result->status_code);
							return false;
					}
				}
				
				if ($Result->body) {
					$Output = json_decode($Result->body, true);
					if (isset($Output['results'])) {
						$allResults = array_merge($allResults, $Output['results']);
						$next = $Output['next'] ?? null;
						// Handle relative URLs by converting them to absolute URLs
						if ($next) {
							if (strpos($next, 'http') !== 0) {
								$nextUrl = $AnsibleUrl . (strpos($next, '/') === 0 ? $next : '/' . $next);
							} else {
								$nextUrl = $next;
							}
						} else {
							$nextUrl = null;
						}
					} else {
						return $Output;
					}
				} else {
					break;
				}
			}
			
			return $allResults;
		} else {
			$Result = $this->api->query->$Method($Url,$Data,$AnsibleHeaders,null,true);
			if (isset($Result->status_code)) {
			  if ($Result->status_code >= 400 && $Result->status_code < 600) {
				switch($Result->status_code) {
				  case 401:
					$this->api->setAPIResponse('Error','Ansible API Key incorrect or expired');
					$this->logging->writeLog("Ansible","Error. Ansible API Key incorrect or expired.","error");
					break;
				  case 404:
					$this->api->setAPIResponse('Error','HTTP 404 Not Found');
					break;
				  default:
					$this->api->setAPIResponse('Error','HTTP '.$Result->status_code);
					break;
				}
			  }
			}
			if ($Result->body) {
			  $Output = json_decode($Result->body,true);
			  if (isset($Output['results'])) {
					return $Output['results'];
			  } else {
					return $Output;
			  }
			} else {
				if (!$GLOBALS['api']['data']) {
					$this->api->setAPIResponse('Warning','No results returned from the API');
				}
			}
		}
	}

	public function GetAnsibleJobTemplate($id = null,$label = null) {
	  $Filters = array();
	  $AnsibleTags = $this->config->get("Plugins","awx")['Ansible-Tag'] ?? null;
	  if ($label) {
		array_push($Filters, "labels__name__in=$label");
	  } elseif ($AnsibleTags) {
		array_push($Filters, "labels__name__in=".implode(',',$AnsibleTags));
	  }
	  if ($Filters) {
		$filter = combineFilters($Filters);
	  }
	  if ($id) {
		$Result = $this->QueryAnsible("get", "job_templates/".$id."/");
	  } else if (isset($filter)) {
		$Result = $this->QueryAnsible("get", "job_templates/?".$filter);
	  } else {
		$Result = $this->QueryAnsible("get", "job_templates");
	  }
	  if ($Result) {
		$this->api->setAPIResponseData($Result);
		return $Result;
	  }
	}
	
	public function GetAnsibleJobs($id = null) {
	  if ($id) {
	    $Result = $this->QueryAnsible("get", "jobs/" . $id);
	  } else {
	    $Result = $this->QueryAnsible("get", "jobs");
	  }
	  if ($Result) {
		$this->api->setAPIResponseData($Result);
		return $Result;
	  } else {
		$this->api->setAPIResponse('Warning','No results returned from the API');
	  }
	}

	public function GetAnsibleJobEventsStream($id) {
	  if ($id) {
	    $Result = $this->QueryAnsible("get", "jobs/" . $id . "/job_events/");
	    if ($Result) {
		  $this->api->setAPIResponseData($Result);
		  return $Result;
	    } else {
		  $this->api->setAPIResponse('Warning','No job events results returned from the API');
	    }
	  } else {
		$this->api->setAPIResponse('Error','Job ID is required');
	  }
	}

	public function SubmitAnsibleJob($id,$data) {
	  $Result = $this->QueryAnsible("post", "job_templates/".$id."/launch/", $data);
	  if ($Result) {
		$this->api->setAPIResponseData($Result);
		return $Result;
	  } else {
		$this->api->setAPIResponse('Warning','No results returned from the API');
	  }
	}

	public function GetAnsibleLabels() {
		$Result = $this->QueryAnsible("get", "labels/?order_by=name");
		if ($Result) {
			$this->api->setAPIResponseData($Result);
			return $Result;
		} else {
			$this->api->setAPIResponse('Warning','No results returned from the API');
		}
	}
}
