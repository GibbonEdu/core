<a href="<?= $link ??'#' ?>" 
   title="<?= $title ?? '' ?>" 
   target="<?= $target ?? 'blank' ?>" 
   style="color: <?= $color ?? '#939090' ?>; border-color: <?= $color ?? '#939090' ?>; background-color: <?= $colorBG ?? '#dddddd' ?>;"
   class="block align-middle text-center font-bold border-0 border-t-2 
   <?= ($large ?? false) ? 'text-4xl w-10 py-1 mr-2 leading-none' : 'text-xs w-4 py-px mr-1 leading-none'; ?> 
   <?= $class ?? 'float-left' ?>"
>
    <?= $tag ?>
</a>
