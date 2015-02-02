<?php
namespace Anax\Tags;
 
/**
 * A controller for Tags and admin related events.
 *
 */
class TagsController implements \Anax\DI\IInjectionAware
{
	use \Anax\DI\TInjectable;
	
	/**
	 * Initialize the controller.
	 *
	 * @return void
	*/
	public function initialize()
	{
		$this->Tags = new \Anax\Tags\Tag();
		$this->Tags->setDI($this->di);
	}
	
	/**
	 * List all Tags.
	 *
	 * @return void
	*/
	public function listAction()
	{
		$this->Questions = new \Anax\Questions\Question();
		$this->Questions->setDI($this->di);
		$all = $this->Questions->query()
		->execute();
		$tagArray = [];
		foreach($all as $post) {
			$alltags = $post->tags;
			$manytags = explode(",", $alltags);
			foreach($manytags as $tag) {
				$trimmedtag =  trim($tag);
				$tag = '<a href="tag/'.$trimmedtag.'">'.$trimmedtag.'</a> ';
				if(!in_array($tag, $tagArray)) {
					array_push($tagArray, $tag);
				}
			}
		}
		$content = '<div id="tags"><h2>Availible tags</h2>';
		foreach ($tagArray as $tag) {
			$content .= '<p>'.$tag.'</p>';
		}
		$content .= '</div>';
		$this->views->add('default/page', [
			'content' => $content,
		]);
	}
	/**
	 * Shaw all thread with a specific tag
	 *
	 * @return void
	*/
	public function tagAction($tag) 
	{
		$this->Questions = new \Anax\Questions\Question();
		$this->Questions->setDI($this->di);
		$all = $this->Questions->query()
			->where('tags LIKE ?')
		->execute(array('%'.$tag.'%'));
		$content = '<p>Post about '.$tag.':</p>';
		foreach ($all as $post) {
			$poster = $post->poster;
			$id = $post->id;
			$title = $post->title;
			$posted = $post->created;
			$content .= '<div class="question"><h2><a href="questions/q/'.$id.'">'.$title.'</a></h2><p><b>Posted by:</b><a href="users/u/'.$poster.'">'.$poster.'</a> on '.$posted.'.</div>';
		}
		$this->views->add('default/page', [
			'content' => $content,
		]);
	}
	
}