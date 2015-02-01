<?php
namespace Anax\Comments;
 
/**
 * A controller for Comments and admin related events.
 *
 */
class CommentsController implements \Anax\DI\IInjectionAware
{
	use \Anax\DI\TInjectable;
	
	/**
	 * Initialize the controller.
	 *
	 * @return void
	*/
	public function initialize()
	{
		$this->Comments = new \Anax\Comments\Comment();
		$this->Comments->setDI($this->di);
	}
	/**
	 * Show single Comment
	 *
	 * @param int $id, the id to Comment to show
	 *
	 * @return void
	*/
	public function qAction($id = null) {
		$all = $this->Comments->query()
			->where('id = ?')
		->execute(array($id));
		if(isset($all[0]) == false)  {
			header('Location: http://www.student.bth.se/~albh14/phpmvc/kmom07-10/webroot/');
		}
		$title = $all[0]->title;
		$Comment = $all[0]->Comment;
		$poster = $all[0]->poster;
		$posted = $all[0]->created;
		/// $tags = $all[0]->tags;
		$content = '<div id="Comment">
		<h1>'.$title.'</h1>
		<p>'.$Comment.'</p>
		<p><b>Posted by:</b><a href="../../users/u/'.$poster.'">'.$poster.'</a> on '.$posted.' with the following tags:';///.$tags.'.';
		$this->theme->setTitle($title);
		
		$this->views->add('default/page', [
			'content' => $content,
		]);
	}
	/**
	 * Add new Comment.
	 *
	 * @param string $threadId id of thread to comment.
	 * @param string $poster who posts the comment.
	 *
	 * @return void
	*/
	public function submitAction($threadId)
	{
		if((isset($_SESSION["loginStatus"])) && ($_SESSION["loginStatus"] != null)) {
			$poster = $_SESSION["loginStatus"];
			$form = $this->form;
			$form = $form->create([], [
				'Comment' => [
					'type' => 'textarea',
					'label' => 'Comment: ',
					'required' => true,
					'validation' => ['not_empty'],
				],
				'poster' => [
					'type' => 'hidden',
					'required' => true,
					'validation' => ['not_empty'],
					'value' => $poster,
				],
				'threadId' => [
					'type' => 'hidden',
					'required' => true,
					'validation' => ['not_empty'],
					'value' => $threadId,
				],
				'submit' => [
					'type' => 'submit',
					'callback'  => function($form) {
						$now = gmdate('Y-m-d H:i:s');
						$this->Comments->save([
							'threadId' => $form->Value('threadId'),
							'Comment' => $form->Value('Comment'),
							'poster' => $form->Value('poster'),
							'created' => $now,
						]);
						return true;
					}
				],
			]);
			$status = $form->check();
			if($status === true) { /// sucessfully submitted
				$url = $this->url->create('Questions/q/'.$threadId.'');
				$this->response->redirect($url);
			}
			else if ($status === false) {
				$form->AddOutput("Could not add new Comment.");
				/// redirect back to add
				$url = $this->url->create('comments/submit');
				$this->response->redirect($url);
			}
			/// Prepare rendering of page
			$this->theme->setTitle("Add new Comment");
			$this->views->add('users/add', [
				'content' => $form->getHTML(),
				'title' => 'Add new Comment',
				'heading' => 'Add new Comment',
			]);
		}
		else {
			header('Location: http://www.student.bth.se/~albh14/phpmvc/kmom07-10/webroot/login');
		}
	}
	public function editCommentAction($id) {
		$form = $this->form;
		$post = $this->Comments->findByUsername($id);
		$form = $this->form->create([], [
			'Comment' => [
				'type' => 'textarea',
				'label' => 'Comment: ',
				'required' => true,
				'validation' => ['not_empty'],
				'value' => $post->Comment,
			],
			'Update' => [
				'type' => 'submit',
				'callback'  => function($form) {
					$this->Comments->save([
						'Comment' => $form->Value('Comment'),
					]);
					return true;
				}
			],
		]);
		$status = $form->check();
		if($status === true) { /// sucessfully submitted
			$form->AddOutput("The Comment has been updated.");
			/// redirect to members lits, might change to add...
			$url = $this->url->create('Questions/q/'.$threadId.'');
			$this->response->redirect($url);
		}
		else if ($status === false) {
			$form->AddOutput("Could not update Comment.");
			/// redirect back to add
			$url = $this->url->create('Comments/submit');
			$this->response->redirect($url);
		}
		/// Prepare rendering of page
		$this->theme->setTitle("Edit Comment");
		$this->views->add('users/add', [
			'content' => $form->getHTML(),
			'title' => 'Change your account settings',
			'heading' => 'My account settings',
		]);
	}
	/**
	 * Delete Comment.
	 *
	 * @param integer $id of Comment to delete.
	 *
	 * @return void
	*/
	public function deleteAction($id = null)
	{
			if (!isset($id)) {
					die("Missing id");
			}
	 
			$res = $this->Comments->delete($id);
	 
			$url = $this->url->create('Comments');
			$this->response->redirect($url);
	}
}