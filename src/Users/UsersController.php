<?php
namespace Anax\Users;
 
/**
 * A controller for users and admin related events.
 *
 */
class UsersController implements \Anax\DI\IInjectionAware
{
	use \Anax\DI\TInjectable;
	
	/**
	 * Initialize the controller.
	 *
	 * @return void
	*/
	public function initialize()
	{
		$this->users = new \Anax\Users\User();
		$this->users->setDI($this->di);
	}
	
	/**
	 * List all users.
	 *
	 * @return void
	*/
	public function listAction()
	{
		$this->initialize();
		$all = $this->users->findAll();
 
		$this->theme->setTitle("List all users");
		$this->views->add('users/list-all', [
			'users' => $all,
			'title' => "Visa alla användare",
		]);
	}
	/**
	 * List all active
	 *
	 * @return void
	*/
	public function activeListAction() {
		$this->initialize();
		$this->theme->setTitle("Aktiva användare");
		$users = $this->activeAction();
		$this->views->add('users/list-all', [
			'users' => $users,
			'title' => 'Aktiva användare',
		]);
	}
	/**
	 * List all inactive users
	 *
	 * @return void
	*/
	public function inactiveLIstAction() {
		$this->initialize();
		$this->theme->setTitle("Inaktiva användare");
		$users = $this->inactiveAction();
		$this->views->add('users/list-all', [
			'users' => $users,
			'title' => 'Inaktiva användare',
		]);
	}
	/**
	 * List all soft-deleted users
	 *
	 * @return void
	*/
	public function deletedListAction() {
		$this->initialize();
		$this->theme->setTitle("Papperskorg");
		$users = $this->deletedAction();
		$this->views->add('users/list-all', [
			'users' => $users,
			'title' => 'Papperskorg',
		]);
	}
	
	/**
	 * Add new user.
	 *
	 * @return void
	*/
	public function addAction()
	{
		$form = $this->form;
		$form = $form->create([], [
			'username' => [
				'type' => 'text',
				'label' => 'Username: ',
				'required' => true,
				'validation' => ['not_empty'],
			],
			'password' => [
				'type' => 'password',
				'label' => 'Password: ',
				'required' => true,
				'validation' => ['not_empty'],
			],
			'name' => [
				'type' => 'text',
				'label' => 'Name: ',
				'required' => true,
				'validation' => ['not_empty'],
			],
			'email' => [
				'type' => 'text',
				'label' => 'Email: ',
				'required' => true,
				'validation' => ['not_empty', 'email_adress'],
			],
			'submit' => [
				'type' => 'submit',
				'callback'  => function($form) {
					$now = gmdate('Y-m-d H:i:s');
					$this->users->save([
						'acronym' => $form->Value('username'),
						'password' => crypt($form->Value('password')),
						'name' => $form->Value('name'),
						'email' => $form->Value('email'),
						'gravatar' => $this->get_gravatar($form->Value('email'))
						'created' => $now,
						'active' => $now,
					]);
					return true;
				}
			],
		]);
		$status = $form->check();
		if($status === true) { /// sucessfully submitted
			$form->AddOutput("The new user was added.");
			/// redirect to members lits, might change to add...
			$url = $this->url->create('users/list');
			$this->response->redirect($url);
		}
		else if ($status === false) {
			$form->AddOutput("Could not add new user.");
			/// redirect back to add
			$url = $this->url->create('users/add');
			$this->response->redirect($url);
		}
		/// Prepare rendering of page
		$this->theme->setTitle("Add new user");
		$this->views->add('users/add', [
			'content' => $form->getHTML(),
			'title' => 'Add new user',
			'heading' => 'Add new user',
		]);
	}
	
	/**
	 * edit user.
	 *
	 * @param string $username of user to edit.
	 *
	 * @return void
	*/
	public function editUserAction($username) {
		$form = $this->form;
		$user = $this->users->findByUsername($username);
		$form = $this->form->create([], [
			'username' => [
				'type' => 'text',
				'label' => 'Username: ',
				'required' => true,
				'validation' => ['not_empty'],
				'value' => $user->acronym,
			],
			'name' => [
				'type' => 'text',
				'label' => 'Name: ',
				'required' => true,
				'validation' => ['not_empty'],
				'value' => $user->name,
			],
			'email' => [
				'type' => 'text',
				'label' => 'Email: ',
				'required' => true,
				'validation' => ['not_empty', 'email_adress'],
				'value' => $user->email,
			],
			'Update' => [
				'type' => 'submit',
				'callback'  => function($form) {
					$now = gmdate('Y-m-d H:i:s');
					$this->users->save([
						'acronym' => $form->Value('username'),
						'name' => $form->Value('name'),
						'email' => $form->Value('email'),
					]);
					return true;
				}
			],
		]);
		$status = $form->check();
		if($status === true) { /// sucessfully submitted
			$form->AddOutput("The user has been updated.");
			/// redirect to members lits, might change to add...
			$url = $this->url->create('users/add');
			$this->response->redirect($url);
		}
		else if ($status === false) {
			$form->AddOutput("Could not update user.");
			/// redirect back to add
			$url = $this->url->create('users/add');
			$this->response->redirect($url);
		}
		/// Prepare rendering of page
		$this->theme->setTitle("Account settings");
		$this->views->add('users/add', [
			'content' => $form->getHTML(),
			'title' => 'Change your account settings',
			'heading' => 'My account settings',
		]);
	}
	/**
	 * Get either a Gravatar URL or complete image tag for a specified email address.
	 *
	 * @param string $email The email address
	 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
	 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
	 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
	 * @param boole $img True to return a complete IMG tag False for just the URL
	 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
	 * @return String containing either just a URL or a complete image tag
	 * @source http://gravatar.com/site/implement/images/php/
	*/
	private function get_gravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
			$url = 'http://www.gravatar.com/avatar/';
			$url .= md5( strtolower( trim( $email ) ) );
			$url .= "?s=$s&d=$d&r=$r";
			if ( $img ) {
					$url = '<img src="' . $url . '"';
					foreach ( $atts as $key => $val )
							$url .= ' ' . $key . '="' . $val . '"';
					$url .= ' />';
			}
			return $url;
	}
	/**
	 * Delete user.
	 *
	 * @param integer $id of user to delete.
	 *
	 * @return void
	*/
	public function deleteAction($id = null)
	{
			if (!isset($id)) {
					die("Missing id");
			}
	 
			$res = $this->users->delete($id);
	 
			$url = $this->url->create('users');
			$this->response->redirect($url);
	}
	/**
	 * Delete (soft) user.
	 *
	 * @param integer $id of user to delete.
	 *
	 * @return void
	*/
	public function softDeleteAction($id = null)
	{
			if (!isset($id)) {
					die("Missing id");
			}
	 
			$now = gmdate('Y-m-d H:i:s');
	 
			$user = $this->users->find($id);
	 
			$user->deleted = $now;
			$user->save();
	 
			$url = $this->url->create('users/id/' . $id);
			$this->response->redirect($url);
	} 
	/**
	* Undo soft-deleted user
	*
	* @param integer $id of user to undo soft delete
	*
	* @return void
	*/
	public function undosoftDeleteAction($id = null) {
		if (!isset($id)) {
			die("Missing id");
		}
 
		$user = $this->users->find($id);
 
		$user->deleted = null;
		$user->save();
 
		$url = $this->url->create('users/id/' . $id);
		$this->response->redirect($url);
	}
	/**
	* Activate user
	*
	* @param integer $id of user to activate
	*
	* @return void
	*/
	public function activateAction($id = null) {
		if (!isset($id)) {
			die("Missing id");
		}
		
		$now = gmdate('Y-m-d H:i:s');
		
		$user = $this->users->find($id);
 
		$user->active = $now;
		$user->save();
 
		$url = $this->url->create('users/id/' . $id);
		$this->response->redirect($url);
	}
	/**
	* Deactivate user
	*
	* @param integer $id of user to deactivate
	*
	* @return void
	*/
	public function deactivateAction($id = null) {
		if (!isset($id)) {
			die("Missing id");
		}
 
		$user = $this->users->find($id);
 
		$user->active = null;
		$user->save();
 
		$url = $this->url->create('users/id/' . $id);
		$this->response->redirect($url);
	}
	
	
	
	/// here goes function to undo soft-delete
	
	/**
	 * List all active and not deleted users.
	 *
	 * @return void
	*/
	public function activeAction()
	{
		$this->initialize();
		$all = $this->users->query()
			->where('active IS NOT NULL')
			->andWhere('deleted is NULL')
			->execute();
		return $all;
	}
	/**
	 * List all inactive and not deleted users.
	 *
	 * @return void
	*/
	public function inactiveAction()
	{
		$this->initialize();
		$all = $this->users->query()
			->where('active IS NULL')
			->andWhere('deleted is NULL')
			->execute();
		return $all;
	}
	/**
	 * List all soft deleted users.
	 *
	 * @return void
	*/
	public function deletedAction()
	{
		$this->initialize();
		$all = $this->users->query()
				->where('deleted IS NOT NULL')
				->execute();
			return $all;
	}
	/**
	 * List user with id.
	 *
	 * @param int $id of user to display
	 *
	 * @return void
	*/
	public function idAction($id = null)
	{	 
		$user = $this->users->find($id);
 
		$this->theme->setTitle("Visa användare med id");
		$this->views->add('users/single-user', [
			'user' => $user,
		]);
	}
	/**
	 * List user with id.
	 *
	 * @param sring $username of user to login
	 * @param sring $password, password of user to login
	 *
	 * @return void
	*/
	public function logInAction($id = null) 
	{
		$this->initialize();
		$form = $this->form;
		$user = $this->users->find($id);
		$form = $this->form->create([], [
			'username' => [
				'type' => 'text',
				'label' => 'Username: ',
				'required' => true,
				'validation' => ['not_empty'],
			],
			'password' => [
				'type' => 'password',
				'label' => 'Password: ',
				'required' => true,
				'validation' => ['not_empty'],
			],
			'submit' => [
				'type' => 'submit',
				'callback'  => function($form) {
					$password_entered = $form->Value('password');
					$username = $form->Value('username');
					$all = $this->users->query()->where('acronym = "'.$username.'"')->execute();
					if(isset($all[0]->password))
					{
						$password_hash = $all[0]->password;
						if(crypt($password_entered, $password_hash) == $password_hash) {
							$this->session = $this->di->session();
							$this->session->set('loginStatus',$username);
							return true;
						}
						else {
							return false;
						}
					}
					else {
						return false;
					}
				}
			],
		]);
		$status = $form->check();
		if($status === true) { /// sucessfully submitted
			$url = $this->url->create('');
			$this->response->redirect($url);
		}
		else if ($status === false) {
			$form->AddOutput("Could not log in.");
			/// redirect back to add
			$url = $this->url->create('users/login');
			$this->response->redirect($url);
		}
		/// Prepare rendering of page
		$this->theme->setTitle("Login");
		$this->views->add('users/add', [
			'content' => $form->getHTML(),
			'title' => 'Login',
			'heading' => 'Login to the website',
		]);
	}
	/**
	 * Check if user with username is logged in
	 *
	 * @param int $id is id of user to check if logged in
	 *
	 * @return void
	*/
	public function checkLogInAction($id = null)
	{
		$this->session = $this->di->session();
		$loggedInAs = $this->session->get('loginStatus');
		if($loggedInAs = null) {
			return false;
		}
		else {
			return $loggedInAs;
		}
	}
	/**
	 * Get info about user
	 *
	 * @param string $username is the username of user to see posts from
	 *
	 * @return void
	*/
	public function uAction($username = null)
	{
		// setup questions-controller
		$this->Questions = new \Anax\Questions\Question();
		$this->Questions->setDI($this->di);
		$this->Comments = new \Anax\Comments\Comment();
		$this->Comments->setDI($this->di);
		$all = $this->Questions->query()
			->where('poster = ?')
		->execute(array($username));
		$content = '<h2>Posts by '.$username.':</h2>';
		foreach ($all as $post) {
			$id = $post->id;
			$title = $post->title;
			$posted = $post->created;
			$content .= '<div class="PostedBy"><p><a href="../../questions/q/'.$id.'">'.$title.'</a></p></div>';
		}
		if($all == null) { 
			$content .= '<p>No posts</p>';
		};
		$content .= '<h2>Comments by  '.$username.'</h2>';
		$allComments = $this->Comments->query()
			->where('poster = ?')
		->execute(array($username));
		if($allComments == null) { 
			$content .= '<p>No Comments</p>';
		};
		foreach ($allComments as $comment) {
			$id = $comment->threadId;
			$betterComment = $comment->comment;
			$time = $comment->created;
			$content .= '<div class="PostedBy"><p>Posted <a href="../../questions/q/'.$id.'">'.$time.'</a>:</p><p>'.$betterComment.'</p></div>';
		}
		$this->views->add('default/page', [
			'content' => $content,
		]);
	}
}