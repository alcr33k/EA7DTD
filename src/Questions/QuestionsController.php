<?php
namespace Anax\Questions;
 
/**
 * A controller for questions and admin related events.
 *
 */
class QuestionsController implements \Anax\DI\IInjectionAware
{
	use \Anax\DI\TInjectable;
	
	/**
	 * Initialize the controller.
	 *
	 * @return void
	*/
	public function initialize()
	{
		$this->Questions = new \Anax\Questions\Question();
		$this->Questions->setDI($this->di);
	}
	/**
	 * Show new questions
	 *
	 * @param int $page, the page to show
	 *
	 * @return void
	*/
	public function pageAction($page) {
		$min = ($page * 10) - 9; 
		$max = $page * 10;
		$all = $this->Questions->query()
			->orderby('created desc LIMIT 10')
		->execute();
		$results = '<h3>Newest questions</h3';
		if ((isset($_SESSION["loginStatus"]) == true) || ($_SESSION["loginStatus"] != null)) {
			$results .= '<br><p><a href="questions/submit">Post a new question</a></p>';
		}
		foreach ($all as $post) {
			$poster = $post->poster;
			$id = $post->id;
			$title = $post->title;
			$posted = $post->created;
			$results .= '<div class="question"><h2><a href="questions/q/'.$id.'">'.$title.'</a></h2><p><b>Posted by:</b><a href="users/u/'.$poster.'">'.$poster.'</a> on '.$posted.'.</div>';
		}
		return $results;
	}
	/**
	 * Show single question
	 *
	 * @param int $id, the id to question to show
	 *
	 * @return void
	*/
	public function qAction($id = null) {
		$filter = new \Anax\Content\CTextFilter();
		$filter->setDI($this);
		$all = $this->Questions->query()
			->where('id = ?')
		->execute(array($id));
		if(isset($all[0]) == false)  {
			header('Location: http://www.student.bth.se/~albh14/phpmvc/kmom07-10/webroot/');
		}
		$title = $all[0]->title;
		$question = $filter->doFilter($all[0]->question, 'shortcode, markdown');
		$poster = $all[0]->poster;
		$gravatar = $this->getGravatar($poster);
		$posted = $all[0]->created;
		$alltags = $all[0]->tags;
		$manytags = explode(",", $alltags);
		$tags = '';
		foreach($manytags as $tag) {
			$trimmedtag =  trim($tag);
			$tags .= '<a href="../../tags/tag/'.$trimmedtag.'">'.$trimmedtag.'</a> ';
		}
		$id = $all[0]->id;
		/// $tags = $all[0]->tags;
		$content = '<div id="question">
		<h1>'.$title.'</h1>
		<p>'.$question.'</p>
		<img src="'.$gravatar.'" alt="ProfilePicure"/>
		<p><b>Posted by:</b><a href="../../users/u/'.$poster.'">'.$poster.'</a> on '.$posted.' with the following tags: '.$tags.'</div>';
		$content .= $this->showComments($id);
		$this->theme->setTitle($title);
		
		$this->views->add('default/page', [
			'content' => $content,
		]);
	}
	/**
	 * Add new question.
	 *
	 * @param string $acronym of question to add.
	 *
	 * @return void
	*/
	public function submitAction($poster, $pdo)
	{
		$this->pdo = $pdo;
		$form = $this->form;
		$form = $form->create([], [
			'title' => [
				'type' => 'text',
				'label' => 'Title: ',
				'required' => true,
				'validation' => ['not_empty'],
			],
			'question' => [
				'type' => 'textarea',
				'label' => 'Question: ',
				'required' => true,
				'validation' => ['not_empty'],
			],
			'tags' => [
				'type' => 'text',
				'label' => 'Tags (seperate by comma): ',
				'required' => true,
				'validation' => ['not_empty'],
			],
			'poster' => [
				'type' => 'hidden',
				'required' => true,
				'validation' => ['not_empty'],
				'value' => $poster,
			],
			'submit' => [
				'type' => 'submit',
				'callback'  => function($form) {
					// take care of tags
					$manytags = explode(",", $form->Value('tags'));
					foreach($manytags as $tag) {
						// check if contains
						$bettertag = trim($tag);
						$stmt = $this->pdo->prepare('Select * from tag where tag = ?');
						$stmt->execute(array($bettertag));
						$count = $stmt->rowCount();
						if($count == 0)
						{
							$stmt = $this->pdo->prepare('INSERT INTO tag (tag) values (?)');
							$stmt->bindParam(1, $bettertag);
							$stmt->execute();
						}
						else {
							$stmt = $this->pdo->prepare('Select * from tag where tag = ?');
							$stmt->execute(array($bettertag));
							$result = $stmt->fetchAll();
							$newvalue = $result[0]['occurance'] + 1;
							$stmt2 = $this->pdo->prepare('UPDATE tag Set occurance=? where tag = ?');
							$stmt2->execute(array($newvalue, $bettertag));
						}
					}
					// save post
					$now = gmdate('Y-m-d H:i:s');
					$this->Questions->save([
						'title' => $form->Value('title'),
						'question' => $form->Value('question'),
						'poster' => $form->Value('poster'),
						'tags' => $form->Value('tags'),
						'created' => $now,
					]);
					return true;
				}
			],
		]);
		$status = $form->check();
		if($status === true) { /// sucessfully submitted
			$url = $this->url->create('questions');
			$this->response->redirect($url);
		}
		else if ($status === false) {
			$form->AddOutput("Could not add new question.");
			/// redirect back to add
			$url = $this->url->create('questions/submit');
			$this->response->redirect($url);
		}
		else {
			echo var_dump($status);
		}
		/// Prepare rendering of page
		$this->theme->setTitle("Add new question");
		$this->views->add('users/add', [
			'content' => $form->getHTML(),
			'title' => 'Add new question',
			'heading' => 'Add new question',
		]);
	}
	/**
	 * Show Comments for post
	 *
	 * @param int $page, the pagewith the comments
	 *
	 * @return comments as html
	*/
	private function showComments($page) {
		$this->Comments = new \Anax\Comments\Comment();
		$this->Comments->setDI($this->di);
		$filter = new \Anax\Content\CTextFilter();
		$filter->setDI($this);
		if((isset($_SESSION["loginStatus"])) && ($_SESSION["loginStatus"] != null)) {
			$content = '<a href="../../comments/submit/'.$page.'">Submit a comment</a><div class="comments">';
			$all = $this->Comments->query()
			->where('threadId = ?')
			->execute(array($page));
			foreach ($all as $comment) {
				$poster = $comment->poster;
				$id = $comment->id;
				$bettercomment = $filter->doFilter($comment->comment, 'shortcode, markdown');
				$posted = $comment->created;
				$gravatar = $this->getGravatar($poster);
				$content .= '<div class=poster><a href="../../users/u/'.$poster.'">'.$poster.'</a><img src="'.$gravatar.'" alt="ProfilePicure"/></div><div class="Comment">'.$bettercomment.'</div>';
			}
			return $content;
		}
		else {
			$content;
			$all = $this->Comments->query()
			->where('threadId = ?')
			->execute(array($page));
			foreach ($all as $comment) {
				$poster = $comment->poster;
				$id = $comment->id;
				$bettercomment = $filter->doFilter($comment->comment, 'shortcode, markdown');
				$posted = $comment->created;
				$content .= '<div class=poster><a href="../../users/u/'.$poster.'">'.$poster.'</a></div><img src="'.$gravatar.'" alt="ProfilePicure"/><div class="Comment">'.$bettercomment.'</div>';
			}
			return $content;
		}
	}
	/**
	 * Get gravatar
	 *
	 * @param int $username,the username of the one we want the gravatar
	 *
	 * @return gravatar url
	*/
	private function getGravatar($username) {
		$this->users = new \Anax\Users\User();
		$this->users->setDI($this->di);
		$sql = $this->users->query()
		->where('acronym = ?')
		->execute(array($username));
		$gravatar = $sql[0]->gravatar;
		return $gravatar;
	}
	/**
	 * Get the 3 most active users
	 *
	 * @return users as html
	*/
	public function getMostActiveAction($pdo) {
		$this->pdo = $pdo;
		$sql = "
		SELECT *, COUNT(poster) as post_count
		FROM question
		GROUP BY poster
		ORDER BY post_count DESC
		LIMIT 3";
		$html = '';
		foreach($pdo->query($sql) as $post) {
			$id = $post['id'];
			$username = $post['poster'];
			$html .= '<a href="users/u/'.$id.'">'.$username.'</a><br>';
		}
		return $html;
	}
	/**
	 * Get the 3 most latest posts
	 *
	 * @return as html list
	*/
	public function getNewestAction() {
		$this->initialize();
		$all = $this->Questions->query()
			->orderby('created DESC LIMIT 3')
		->execute();
		$html = '';
		foreach($all as $post) {
			$id = $post->id;
			$title = $post->title;
			$html .= '<a href="questions/q/'.$id.'">'.$title.'</a><br>';
		}
		return $html;
	}
	/**
	 * Get most popular tags
	 *
	 * @return as html list
	*/
	public function getPopularTagsAction() {
		$this->Tags = new \Anax\Tags\Tag();
		$this->Tags->setDI($this->di);
		$all = $this->Tags->query()
			->orderby('occurance DESC LIMIT 3')
		->execute();
		$html = '';
		foreach($all as $post) {
			$title = $post->tag;
			$html .= '<a href="tags/tag/'.$title.'">'.$title.'</a><br>';
		}
		return $html;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function editquestionAction($id) {
		$form = $this->form;
		$post = $this->Questions->findByUsername($id);
		$form = $this->form->create([], [
			'question' => [
				'type' => 'textarea',
				'label' => 'Question: ',
				'required' => true,
				'validation' => ['not_empty'],
				'value' => $post->question,
			],
			'Update' => [
				'type' => 'submit',
				'callback'  => function($form) {
					$this->Questions->save([
						'question' => $form->Value('question'),
					]);
					return true;
				}
			],
		]);
		$status = $form->check();
		if($status === true) { /// sucessfully submitted
			$form->AddOutput("The question has been updated.");
			/// redirect to members lits, might change to add...
			$url = $this->url->create('questions/submit');
			$this->response->redirect($url);
		}
		else if ($status === false) {
			$form->AddOutput("Could not update question.");
			/// redirect back to add
			$url = $this->url->create('questions/submit');
			$this->response->redirect($url);
		}
		/// Prepare rendering of page
		$this->theme->setTitle("Edit question");
		$this->views->add('users/add', [
			'content' => $form->getHTML(),
			'title' => 'Change your account settings',
			'heading' => 'My account settings',
		]);
	}
	/**
	 * Delete question.
	 *
	 * @param integer $id of question to delete.
	 *
	 * @return void
	*/
	public function deleteAction($id = null)
	{
			if (!isset($id)) {
					die("Missing id");
			}
	 
			$res = $this->Questions->delete($id);
	 
			$url = $this->url->create('questions');
			$this->response->redirect($url);
	}
}
