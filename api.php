<?php
// Get awx Plugin Settings
$app->get('/plugin/awx/settings', function ($request, $response, $args) {
	$awxPlugin = new awxPlugin();
	if ($awxPlugin->auth->checkAccess('ADMIN-CONFIG')) {
		$awxPlugin->api->setAPIResponseData($awxPlugin->_pluginGetSettings());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// ** ANSIBLE ** //
//  Return list of Ansible Labels
$app->get('/plugin/awx/ansible/labels', function ($request, $response, $args) {
	$awxPlugin = new awxPluginAnsible();
	if ($awxPlugin->auth->checkAccess($awxPlugin->config->get('Plugins','awx')['ACL-ADMIN'] ?: 'ACL-ADMIN')) {
		$awxPlugin->GetAnsibleLabels();
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

//  Return list of Ansible Job Templates
$app->get('/plugin/awx/ansible/templates', function ($request, $response, $args) {
	$awxPlugin = new awxPluginAnsible();
	if ($awxPlugin->auth->checkAccess($awxPlugin->config->get('Plugins','awx')['ACL-JOB'] ?? null)) {
		$data = $request->getQueryParams();
		$Label = $data['label'] ?? null;
		$Id = $data['id'] ?? null;
		$awxPlugin->GetAnsibleJobTemplate($Id,$Label);
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

//  Return list of Ansible Jobs
$app->get('/plugin/awx/ansible/jobs', function ($request, $response, $args) {
	$awxPlugin = new awxPluginAnsible();
	if ($awxPlugin->auth->checkAccess($awxPlugin->config->get('Plugins','awx')['ACL-JOB'] ?? null)) {
		$jobs = $awxPlugin->GetAnsibleJobs();
        if ($jobs) {
            $awxPlugin->api->setAPIResponseData($jobs);
        }
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Get specific Ansible Job
$app->get('/plugin/awx/ansible/jobs/{id}', function ($request, $response, $args) {
    $awxPlugin = new awxPluginAnsible();
    if ($awxPlugin->auth->checkAccess($awxPlugin->config->get('Plugins','awx')['ACL-JOB'] ?? null)) {
        $jobId = $args['id'];
        $job = $awxPlugin->GetAnsibleJobs($jobId);
        if ($job) {
            $awxPlugin->api->setAPIResponseData($job);
        }
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get specific Ansible Job Activity Stream
$app->get('/plugin/awx/ansible/jobs/{id}/job_events', function ($request, $response, $args) {
    $awxPlugin = new awxPluginAnsible();
    if ($awxPlugin->auth->checkAccess($awxPlugin->config->get('Plugins','awx')['ACL-JOB'] ?? null)) {
        $jobId = $args['id'];
        $events = $awxPlugin->GetAnsibleJobEventsStream($jobId);
        $awxPlugin->api->setAPIResponseData($events);
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get AWX Jobs
$app->get('/plugin/awx/ansible/awx/jobs', function ($request, $response, $args) {
	$awxPlugin = new awxPluginAnsible();
    if ($awxPlugin->auth->checkAccess($awxPlugin->config->get('Plugins','awx')['ACL-READ'] ?? null)) {
        $jobs = $awxPlugin->GetAWXJobs();
        if ($jobs) {
            $awxPlugin->api->setAPIResponseData($jobs);
        }
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Get AWX Job Details
$app->get('/plugin/awx/ansible/awx/job/{id}', function ($request, $response, $args) {
	$awxPlugin = new awxPluginAnsible();
    if ($awxPlugin->auth->checkAccess($awxPlugin->config->get('Plugins','awx')['ACL-READ'] ?? null)) {
        $jobId = $args['id'];
        $job = $awxPlugin->QueryAnsible('get', "jobs/$jobId/");
        if ($job) {
            $awxPlugin->api->setAPIResponseData($job);
        }
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

// Submit Ansible Job
$app->post('/plugin/awx/ansible/job/{id}', function ($request, $response, $args) {
	$awxPlugin = new awxPluginAnsible();
    if ($awxPlugin->auth->checkAccess($awxPlugin->config->get('Plugins','awx')['ACL-JOB'] ?? null)) {
		$data = $awxPlugin->api->getAPIRequestData($request);
		$DataArray = array(
			"extra_vars" => array()
		);
		foreach ($data as $ReqVar => $ReqKey) {
			$DataArray['extra_vars'][$ReqVar] = $ReqKey;
		}
		$result = $awxPlugin->SubmitAnsibleJob($args['id'], $DataArray);
		$DebugArr = array(
			"request" => $data,
			"response" => $result
		);
		if (isset($result['job'])) {
			$awxPlugin->logging->writeLog("Ansible","Submitted Ansible Job.","info",$DebugArr);
			$awxPlugin->api->setAPIResponseData($result);
		} else {
			$awxPlugin->api->setAPIResponse('Error','Error submitting ansible job. Check logs.');
			$awxPlugin->logging->writeLog("Ansible","Error submitting ansible Job.","error",$DebugArr);
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});
// Get AWX Inventories
$app->get('/plugin/awx/ansible/awx/inventories', function ($request, $response, $args) {
	$awxPlugin = new awxPluginAnsible();
    if ($awxPlugin->auth->checkAccess($awxPlugin->config->get('Plugins','awx')['ACL-READ'] ?? null)) {
        $inventories = $awxPlugin->GetAnsibleInventories();
        if ($inventories) {
            $awxPlugin->api->setAPIResponseData($inventories);
        }
    }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});	