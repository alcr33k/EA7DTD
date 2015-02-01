<h1><?=$title?></h1>

<nav class="usersNav">
	<ul>
		<li><a href="<?=$this->url->create('users')?>">Alla användare</a></li>
		<li><a href="<?=$this->url->create('users/active-list')?>">Aktiva användare</a></li>
		<li><a href="<?=$this->url->create('users/inactive-list')?>">Inaktiva användare</a></li>
		<li><a href="<?=$this->url->create('users/deleted-list')?>">Paperskorgen</a></li>
		<li><a href="<?=$this->url->create('users/add')?>">Skapa användare</a></li>
		<li><a href="<?=$this->url->create('setup')?>">Återställ användare</a></li>
	</ul>
</nav> 


<?php foreach ($users as $user) : ?>
<div class="user">
	<h2><a href="<?=$this->url->create('users/id/' . $user->id)?>"><?=$user->acronym?></a></h2>
	<p><b>Namn:</b><?=$user->acronym?></p>
	<p><b>Email:</b><?=$user->email?></p>
	<p><b>Skapad:</b><?=$user->created?></p>
	<p><b>Aktiv:</b><?=isset($user->active) ? 'Ja' : 'Nej'?></p>
	<p><? if (isset($user->deleted)) print('I papperskorgen');?></p>
</div> 
<?php endforeach; ?>

<p><a href='<?=$this->url->create('')?>'>Tillbaka till startsidan</a></p>