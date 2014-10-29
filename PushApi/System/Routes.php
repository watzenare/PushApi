<?php 

use \PushApi\Controllers\Users;
use \PushApi\System\PushApiException;

$this->slim->post('/user', function () {
	
	$result = false;
	$error = 0;

	try {
		$username = $this->slim->request->post('username');
		$email = $this->slim->request->post('email');
		$userId = $this->slim->request->post('userId');

		if (!isset($username) && !isset($userId) && !isset($email)) {
			throw new PushApiException(PushApiException::EMPTY_PARAMS);
		}

		$user = new Users();
		$created = $user->createUser($username, $userId, $email);
		if ($created) {
			$this->slim->response()->status(HTTP_OK);
			$result = array(
				'id' => $created
			);
		} else {
			$this->slim->response()->status(HTTP_NOT_MODIFIED);
		}
	} catch (PushApiException $e) {
		$this->slim->response()->status(HTTP_BAD_REQUEST);
		$this->slim->response()->header('X-Status-Reason', $e->getMessage());
		$result = $e->getMessage();
		$error = $e->getCode();
	}
	$this->sendResponse($result, $error);
});

$this->slim->get('/user/:id', function ($id) {
	
	$result = false;
	$error = 0;

	try {
		if (!isset($id)) {
			throw new PushApiException(PushApiException::EMPTY_PARAMS);
		}

		$user = new Users();
		$result = $user->getUserById($id);
		if ($result) {
			$this->slim->response()->status(HTTP_OK);
		} else {
			$this->slim->response()->status(HTTP_NOT_FOUND);
		}
	} catch (PushApiException $e) {
		$this->slim->response()->status(HTTP_BAD_REQUEST);
		$this->slim->response()->header('X-Status-Reason', $e->getMessage());
		$result = $e->getMessage();
		$error = $e->getCode();
	}
	$this->sendResponse($result, $error);
});

$this->slim->put('/user/:id', function ($id) {
	
	$result = false;
	$error = 0;

	try {
		$data = array();

		if (!isset($id)) {
			throw new PushApiException(PushApiException::EMPTY_PARAMS);
		}

		$data['username'] = $this->slim->request->put('username');
		$data['userId'] = $this->slim->request->put('userId');
		$data['email'] = $this->slim->request->put('email');

		if (!isset($data['username']) && !isset($data['userId']) && !isset($data['email'])) {
			throw new PushApiException(PushApiException::EMPTY_PARAMS);
		}

		$user = new Users();
		$result = $user->updateUser($id, $data);
		if ($result) {
			$this->slim->response()->status(HTTP_OK);
		} else {
			$this->slim->response()->status(HTTP_NOT_FOUND);
		}
	} catch (PushApiException $e) {
		$this->slim->response()->status(HTTP_BAD_REQUEST);
		$this->slim->response()->header('X-Status-Reason', $e->getMessage());
		$result = $e->getMessage();
		$error = $e->getCode();
	}
	$this->sendResponse($result, $error);
});

$this->slim->delete('/user/:id', function ($id) {
	
	$result = false;
	$error = 0;

	try {
		if (!isset($id)) {
			throw new PushApiException(PushApiException::EMPTY_PARAMS);
		}

		$user = new Users();
		$result = $user->deleteUser($id);
		if ($result) {
			$this->slim->response()->status(HTTP_OK);
		} else {
			$this->slim->response()->status(HTTP_NOT_FOUND);
		}
	} catch (PushApiException $e) {
		$this->slim->response()->status(HTTP_BAD_REQUEST);
		$this->slim->response()->header('X-Status-Reason', $e->getMessage());
		$result = $e->getMessage();
		$error = $e->getCode();
	}
	$this->sendResponse($result, $error);
});

$this->slim->get('/users', function () {
	
	$result = false;
	$error = 0;

	try {
		$user = new Users();
		$result = $user->getUsers();
		if ($result) {
			$this->slim->response()->status(HTTP_OK);
		} else {
			$this->slim->response()->status(HTTP_NOT_FOUND);
		}
	} catch (PushApiException $e) {
		$this->slim->response()->status(HTTP_BAD_REQUEST);
		$this->slim->response()->header('X-Status-Reason', $e->getMessage());
		$result = $e->getMessage();
		$error = $e->getCode();
	}
	$this->sendResponse($result, $error);
});