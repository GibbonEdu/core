<input type="text" <?= $attributes; ?> 
    class="w-full rounded-md border py-2 text-gray-900  placeholder:text-gray-400 focus:ring-1 focus:ring-inset focus:ring-blue-500 sm:text-sm sm:leading-6" >

<?php if (!empty($autocomplete)) { ?>
    <script type="text/javascript">
    $("#<?= $element['id']; ?>").autocomplete({source: [<?= $autocomplete; ?>]});
    </script>
<?php } ?>

<?php if (!empty($unique)) { ?>
    <script type="text/javascript">
        $("#<?= $element['id']; ?>").gibbonUniquenessCheck(<?= $unique; ?>);
    </script>
<?php } ?>
