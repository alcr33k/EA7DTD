<nav class="usersNav">
	<ul>
		<li><a href="<?=$this->url->create('users')?>">Tillbaka till lista för alla användare</a></li>
	</ul>
</nav> 

<h2>Visar information om användare <?=$user->acronym?></h2>
<div class="user">
	<p><b>Namn:</b><?=$user->acronym?></p>
	<p><b>Email:</b><?=$user->email?></p>
	<p><b>Skapad:</b><?=$user->created?></p>
	<p><b>Aktiv:</b><?=isset($user->active) ? 'Ja' : 'Nej'?></p>
	<p><? if (isset($user->deleted)) print('I papperskorgen');?></p>
</div> 
<h3>Administrera användaren:</h3>
<a href="<?=$this->url->create('users/editUser/' . $user->id)?>">Redigera användare</a>
<?php if($user->active !== null) : ?> 
<a href="<?=$this->url->create('users/deactivate/' . $user->id)?>">Inaktivera konto</a>
<?php elseif($user->deleted === null) : ?>
<a href="<?=$this->url->create('users/activate/' . $user->id)?>">Aktivera konto</a>
<a href="<?=$this->url->create('users/soft-delete/' . $user->id)?>">Ta bort konto (går att ångra)</a>
<?php elseif($user->deleted !== null) : ?>
<a href="<?=$this->url->create('users/undo-softdelete/' . $user->id)?>">Ångra borttagning</a>
<a href="<?=$this->url->create('users/delete/' . $user->id)?>">Ta bort konto permanent</a>
<?php else: ?>
<p>Hello error. </p>
<?php endif; ?>

<p><a href='<?=$this->url->create('')?>'>Tillbaka till startsidan</a></p>