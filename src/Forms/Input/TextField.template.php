<input type="<?= $type ?? 'text'; ?>" <?= $attributes; ?> 
    class="<?= $class; ?> w-full min-w-0 rounded-md py-2  placeholder:text-gray-400  sm:text-sm sm:leading-6 <?= $type != 'text' ? 'input-icon' : ''; ?>
    
    <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed focus:ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>
    
    "
    
    style="<?= $type == 'url' ? "background-image: url('data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2024%2024%22%20stroke-width%3D%222.2%22%20stroke%3D%22%23605f5f%22%20class%3D%22size-6%22%3E%3Cpath%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20d%3D%22M13.19%208.688a4.5%204.5%200%200%201%201.242%207.244l-4.5%204.5a4.5%204.5%200%200%201-6.364-6.364l1.757-1.757m13.35-.622%201.757-1.757a4.5%204.5%200%200%200-6.364-6.364l-4.5%204.5a4.5%204.5%200%200%200%201.242%207.244%22%20%2F%3E%3C%2Fsvg%3E');
    background-position: right .5rem center;
    background-repeat: no-repeat;
    background-size: 1.3em 1.3em;
    padding-right: 2.5rem;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;" : ''; ?>"
    />

<?php if (!empty($autocomplete)) { ?>
    <script type="text/javascript">
    $("#<?= $id; ?>").autocomplete({source: [<?= $autocompleteList; ?>]});
    </script>
<?php } ?>

<?php if (!empty($unique)) { ?>
    <script type="text/javascript">
        $("#<?= $id; ?>").gibbonUniquenessCheck(<?= $unique; ?>);
    </script>
<?php } ?>
