<a href="<?= $link ?>" 
   title="<?= $title ?>" 
   target="<?= $target ?>" 
   style="color: <?= $color ?>; border-color: <?= $color ?>; background-color: <?= $colorBG ?>;"
   class="block align-middle text-center font-bold border-0 border-t-2 
   <?= ($large) ? 'text-4xl w-10 py-1 mr-2 leading-none' : 'text-xs w-4 py-px mr-1 leading-none'; ?> 
   <?= $class ?? 'float-left' ?>"
>
    <?= $tag ?>
</a>
