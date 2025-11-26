<?php
    if ($action == 'add' || $action == 'addMultiple' || $action == 'accept' || $action == 'approve') {
        $hoverClass = 'hover:text-green-500 hover:border-green-500';
    } elseif ($action == 'delete' || $action == 'reject' || $action == 'decline' || $action == 'cancel') {
        $hoverClass = 'hover:text-red-700 hover:border-red-700';
    } else {
        $hoverClass = 'hover:text-blue-500 hover:border-blue-500';
    }

    switch ($type) {
        case 'interface':
            $displayClass = 'border-0 px-2 py-2';
            $svgClass = 'size-4 '.($displayLabel ? 'lg:-ml-0.5 lg:mr-1.5 ' : '').($iconClass ?? '');
            break;
        default:
            $displayClass = 'px-3 py-2 bg-white shadow-sm border border-gray-400 hover:bg-gray-100';
            $svgClass = 'size-6 sm:size-5 '.($displayLabel ? 'lg:-ml-0.5 lg:mr-1.5 ' : '').($iconClass ?? '');
    }
?>

<a <?= $attributes; ?> <?= !$modal ? '@click="modalOpen = false"' : '' ?> title="<?= !$displayLabel ? $label : ''; ?>"
    class="<?= $class; ?> inline-flex items-center align-middle rounded-md text-sm sm:leading-5 font-semibold <?= $displayClass; ?> <?= $hoverClass; ?> <?= $displayLabel ? 'text-gray-600 lg:text-gray-500' : 'text-gray-600'; ?>">

    <?= icon($iconLibrary ?? 'solid', $icon ?? $action, $svgClass) ?>
    
    <?php if ($displayLabel) { ?>
    <span class="hidden lg:block text-gray-800 whitespace-nowrap">
        <?= $label; ?>
    </span>
    <?php } ?>
</a>
