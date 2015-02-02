<?php
namespace Anax\Opinions;
 
/**
 * A controller for Opinions and admin related events.
 *
 */
class OpinionsController implements \Anax\DI\IInjectionAware
{
	use \Anax\DI\TInjectable;
	
	/**
	 * Initialize the controller.
	 *
	 * @return void
	*/
	public function initialize()
	{
		$this->Opinions = new \Anax\Opinions\Opinion();
		$this->Opinions->setDI($this->di);
	}
	/**
	 * Add new Opinion.
	 *
	 * @param string $threadId id of thread to Opinion.
	 * @param string $poster who posts the Opinion.
	 *
	 * @return void
	*/
	public function submitAction($threadId)
	{
		if((isset($_SESSION["loginStatus"])) && ($_SESSION["loginStatus"] != null)) {
			$poster = $_SESSION["loginStatus"];
			$form = $this->form;
			$form = $form->create([], [
				'Opinion' => [
					'type' => 'textarea',
					'label' => 'Opinion: ',
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
						$this->Opinions->save([
							'threadId' => $form->Value('threadId'),
							'Opinion' => $form->Value('Opinion'),
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
				$form->AddOutput("Could not add new Opinion.");
				/// redirect back to add
				$url = $this->url->create('Opinions/submit');
				$this->response->redirect($url);
			}
			/// Prepare rendering of page
			$this->theme->setTitle("Add new Opinion");
			$this->views->add('users/add', [
				'content' => $form->getHTML(),
				'title' => 'Add new Opinion',
				'heading' => 'Add new Opinion',
			]);
		}
		else {
			header('Location: http://www.student.bth.se/~albh14/phpmvc/kmom07-10/webroot/login');
		}
	}
	public function editOpinionAction($id) {
		$form = $this->form;
		$post = $this->Opinions->findByUsername($id);
		$form = $this->form->create([], [
			'Opinion' => [
				'type' => 'textarea',
				'label' => 'Opinion: ',
				'required' => true,
				'validation' => ['not_empty'],
				'value' => $post->Opinion,
			],
			'Update' => [
				'type' => 'submit',
				'callback'  => function($form) {
					$this->Opinions->save([
						'Opinion' => $form->Value('Opinion'),
					]);
					return true;
				}
			],
		]);
		$status = $form->check();
		if($status === true) { /// sucessfully submitted
			$form->AddOutput("The Opinion has been updated.");
			/// redirect to members lits, might change to add...
			$url = $this->url->create('Questions/q/'.$threadId.'');
			$this->response->redirect($url);
		}
		else if ($status === false) {
			$form->AddOutput("Could not update Opinion.");
			/// redirect back to add
			$url = $this->url->create('Opinions/submit');
			$this->response->redirect($url);
		}
		/// Prepare rendering of page
		$this->theme->setTitle("Edit Opinion");
		$this->views->add('users/add', [
			'content' => $form->getHTML(),
			'title' => 'Change your account settings',
			'heading' => 'My account settings',
		]);
	}
	/**
	 * Delete Opinion.
	 *
	 * @param integer $id of Opinion to delete.
	 *
	 * @return void
	*/
	public function deleteAction($id = null)
	{
			if (!isset($id)) {
					die("Missing id");
			}
	 
			$res = $this->Opinions->delete($id);
	 
			$url = $this->url->create('Opinions');
			$this->response->redirect($url);
	}
}