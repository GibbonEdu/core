<textarea <?= $attributes; ?> 
    class="<?= $class; ?> w-full rounded-md border py-2 font-sans placeholder:text-gray-500  sm:text-sm sm:leading-6
    <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed :ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>"><?= $text; ?></textarea>

<?php if (!empty($autosize)) { ?>
    <script type="text/javascript">autosize($("#<?= $id; ?>"));</script>
<?php } ?>
