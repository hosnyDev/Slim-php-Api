<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;


require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../includes/DbOperations.php';

$app = AppFactory::create();



// Set the base path of the Slim App
$basePath = str_replace('/' . basename(__FILE__), '', $_SERVER['SCRIPT_NAME']);
$app = $app->setBasePath($basePath);

/**
 *  endpoint : cretate user 
 * 	param : email, password, name, school
 * 	method : POST
 */

$app->post('/createuser', function (Request $request, Response $response) {

	if (!haveEmptyParameters(array('email', 'password', 'name', 'school'), $request, $response)) {

		$request_data = $request->getParsedBody();

		$email = $request_data['email'];
		$password = $request_data['password'];
		$name = $request_data['name'];
		$school = $request_data['school'];

		$hash_password = password_hash($password, PASSWORD_DEFAULT);

		$db = new DbOperations();

		$result = $db->createUser($email, $hash_password, $name, $school);

		if ($result == USER_CREATED) {

			$message = array();
			$message['error'] = false;
			$message['message'] = 'User Created Successfully';
			$response->getBody()->write(json_encode($message));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(201);
		} else if ($result == USER_FALURE) {

			$message = array();
			$message['error'] = true;
			$message['message'] = 'Some error..' . $request_data;

			$response->getBody()->write(json_encode($message));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(200);
		} else if ($result == USER_EXISTS) {

			$message = array();
			$message['error'] = true;
			$message['message'] = 'User Aleady Exists';

			$response->getBody()->write(json_encode($message));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(422);
		}
	}
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus(232);
});


$app->post('/userlogin', function (Request $request, Response $response) {

	if (!haveEmptyParameters(array('email', 'password'), $request, $response)) {

		$request_data = $request->getParsedBody();
		$email = $request_data['email'];
		$password = $request_data['password'];

		$db = new DbOperations;
		$result = $db->userLogin($email, $password);

		if ($result == USER_AUTHENTICATED) {

			$user = $db->getUserByEmail($email);
			$response_data = array();
			$response_data['error'] = false;
			$response_data['message'] = 'Login is successful';
			if ($user != null)
				$response_data['user'] = $user;
			else
				$response_data['user'] = 'null data';
			$response->getBody()->write(json_encode($response_data));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(200);
		} else if ($result == USER_NOT_FOND) {

			$response_data = array();
			$response_data['error'] = true;
			$response_data['message'] = 'User not exist!..';
			$response->getBody()->write(json_encode($response_data));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(404);
		} else if ($result == USER_PASSWORD_DO_NOT_MATCH) {

			$response_data = array();
			$response_data['error'] = true;
			$response_data['message'] = 'Invaled email or password!..';
			$response->getBody()->write(json_encode($response_data));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(404);
		}
	}
	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus(422);
});

$app->get('/allusers', function (Request $request, Response $response) {

	$db = new DbOperations;
	$users = $db->getAllUser();

	$response_data = array();
	$response_data['error'] = false;
	$response_data['users'] = $users;
	$response->getBody()->write(json_encode($response_data));

	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus(200);
});


$app->put('/updateuser/{id}', function (Request $request, Response $response, array $args) {

	$id = $args['id'];

	if (!haveEmptyParameters(array('email', 'name', 'school', 'id'), $request, $response)) {

		$request_data = $request->getParsedBody();

		$email = $request_data['email'];
		$name = $request_data['name'];
		$school = $request_data['school'];
		$id = $request_data['id'];


		$db = new DbOperations;

		if ($db->updateUser($email, $name, $school, $id)) {

			$response_data = array();
			$response_data['error'] = false;
			$response_data['message'] = 'data updated succssuflly';

			$user = $db->getUserByEmail($email);
			$response_data['user'] = $user;

			$response->getBody()->write(json_encode($response_data));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(200);
		} else {

			$response_data = array();
			$response_data['error'] = true;
			$response_data['message'] = 'error try again';

			$user = $db->getUserByEmail($email);
			$response_data['user'] = $user;

			$response->getBody()->write(json_encode($response_data));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(200);
		}
	}

	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus(200);
});


$app->put('/updatepassword', function (Request $request, Response $response) {

	if (!haveEmptyParameters(array('current_password', 'new_password', 'email'), $request, $response)) {

		$request_data = $request->getParsedBody();

		$current_password = $request_data['current_password'];
		$new_password = $request_data['new_password'];
		$email = $request_data['email'];

		$db = new DbOperations;

		$result = $db->updatePassword($current_password, $new_password, $email);

		if ($result == PASSWORD_CHANGED) {

			$response_data = array();
			$response_data['error'] = false;
			$response_data['message'] = 'password updated succssuflly';

			$response->getBody()->write(json_encode($response_data));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(200);
		} else if ($result == PASSWORD_DO_NOT_MATCH) {
			$response_data = array();
			$response_data['error'] = true;
			$response_data['message'] = 'wrong pass';

			$response->getBody()->write(json_encode($response_data));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(200);
		} else if ($result == PASSWORD_NOT_CHANGED) {
			$response_data = array();
			$response_data['error'] = true;
			$response_data['message'] = 'some error';

			$response->getBody()->write(json_encode($response_data));

			return $response
				->withHeader('Content-Type', 'application/json')
				->withStatus(200);
		}
	}

	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus(412);
});


$app->delete('/deleteuser/{id}', function (Request $request, Response $response, array $args) {

	$id = $args['id'];

	$db = new DbOperations;

	$response_data = array();

	if ($db->deleteUser($id)) {

		$response_data = array();
		$response_data['error'] = false;
		$response_data['message'] = 'User deleted';
	} else {

		$response_data = array();
		$response_data['error'] = true;
		$response_data['message'] = 'Error try again';
	}

	$response->getBody()->write(json_encode($response_data));

	return $response
		->withHeader('Content-Type', 'application/json')
		->withStatus(412);
});

function haveEmptyParameters($required_params, $request, $response)
{
	$error = false;
	$error_params = '';
	$request_params = $request->getParsedBody();
	//$request_params = $_REQUEST;

	foreach ($required_params as $param) {
		if (!isset($request_params[$param]) || strlen($request_params[$param]) <= 0) {
			$error = true;
			$error_params .= $param . ', ';
		}
	}

	if ($error) {

		$error_detail = array();
		$error_detail['error'] = true;
		$error_detail['massage'] = 'Required parameterts ' . substr($error_params, 0, -2) . ' are missing or empty';
		$response->getBody()->write(json_encode($error_detail));
	}

	return $error;
}

$app->run();
s