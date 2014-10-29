<?php

namespace PushApp\Controller;

use \PushApp\Controller\Controller;
use \PushApp\System\PushAppException;


class Users extends Controller
{
	const TABLE_NAME = 'users';

	const COL_ID = 'id';
	const COL_USERNAME = 'username';
	const COL_USER_ID = 'userId';
	const COL_EMAIL = 'email';
	const COL_STATUS = 'status';

	const COLUMN_ALL = '*';

	private $id = null;
	private $username;
	private $userId;
	private $email;
	private $status = 1;
	private $created = null;

	private $query = array();

	public function __construct($id = null) {
		parent::__construct();
		
		$this->id = $id;
		if (isset($id) && $this->getUser($this->id)) {
			// veure què fer aqui
		}
	}

	public function createUser($username, $userId, $email) {
		$this->query = array(
			'username' => $this->username,
			'email' => $this->email,
			'userId' => $this->userId
		);

		return $this->dbLink->insert(self::TABLE_NAME, $this->query);
	}

	public function getUserById($id = null) {
		if ($id == null) {
			throw new PushAppException(PushAppException::NO_DATA);
		}

		$this->query = array( 
			self::COL_ID => $id
		);

		$result = $this->dbLink->select(self::TABLE_NAME, self::COLUMN_ALL, $this->query);

		if (empty($result)) {
			throw new PushAppException(PushAppException::NOT_FOUND);
		} else {
			return $result;
		}
	}

	public function getUserByUserId($userId = null) {
		if ($userId == null) {
			throw new PushAppException(PushAppException::NO_DATA);
		}

		$this->query = array(
			self::COL_USER_ID => $userId
		);

		return $this->dbLink->select(self::TABLE_NAME, self::COLUMN_ALL, $this->query);
	}

	public function getUsers() {
		$result = $this->dbLink->select(self::TABLE_NAME, self::COLUMN_ALL, $this->query);

		if ($result) {
			return $result;
		} else {
			throw new PushAppException(PushAppException::NOT_FOUND);
		}
	}

	public function updateUser($id, $update) {
		if ($id == null) {
			echo "Exception: can't search without data";
		}

		if (!isset($data['username']) && !isset($data['userId']) && !isset($data['email'])) {
			throw new Exception("Error Processing Request", 1);
		}

		// update only set fields
		foreach ($data as $key => $value) {
			if ($value == null) {
				unset($data[$key]);
			}
		}

		$this->query = array( 
			self::COL_ID => $id
		);
		$deleted = $this->dbLink->update(self::TABLE_NAME, $update, $this->query);

		if ($deleted == 1) {
			return true;
		} else {
			throw new Exception("Error Processing Request", 1);
		}
	}

	public function deleteUser($id) {
		if ($id == null) {
			echo "Exception: can't search without data";
		}

		$update = array(
			self::COL_STATUS => 0
		);

		$this->query = array( 
			self::COL_ID => $id
		);
		$deleted = $this->dbLink->update(self::TABLE_NAME, $update, $this->query);

		if ($deleted == 1) {
			return true;
		} else {
			throw new Exception("Error Processing Request", 1);
		}
	}
}