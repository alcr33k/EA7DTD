<article>
<div class="homeArticle">
<?=$content?>
</div>
<div class="sidebar">
<?=$sidebar?>
</div>

<?php if (isset($links)) : ?>
<ul>
<?php foreach ($links as $link) : ?>
<li><a href="<?=$link['href']?>"><?=$link['text']?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</article>