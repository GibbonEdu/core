<?php
$dayOffsetButtons = !empty($dayOffsetButtons) && is_array($dayOffsetButtons) ? array_values($dayOffsetButtons) : [];
$useDateActions = !empty($todayButton) || !empty($dayOffsetButtons);
$todayButtonClass = 'inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-md border border-gray-400 bg-white px-3 py-2 text-xs font-medium text-gray-600 hover:border-blue-700 hover:bg-blue-200 hover:text-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 disabled:cursor-not-allowed disabled:opacity-50';
// Match Form Button default sizing/style used for secondary actions below fields (e.g. Departments > Add Another Resource)
$offsetButtonClass = 'inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-md border border-gray-400 bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 disabled:cursor-not-allowed disabled:opacity-50 sm:leading-5';
$alpineData = $useDateActions ? 'x-data="{ formatDate(d) { const month = (\'0\' + (d.getMonth() + 1)).slice(-2); const day = (\'0\' + d.getDate()).slice(-2); return d.getFullYear() + \'-\' + month + \'-\' + day; }, updateDate(d) { this.$refs.dateInput.value = this.formatDate(d); this.$refs.dateInput.dispatchEvent(new Event(\'input\', { bubbles: true })); this.$refs.dateInput.dispatchEvent(new Event(\'change\', { bubbles: true })); }, setToday() { this.updateDate(new Date()); }, addDays(days) { let d = this.$refs.dateInput.value ? new Date(this.$refs.dateInput.value + \'T00:00:00\') : new Date(); if (Number.isNaN(d.getTime())) { d = new Date(); } d.setDate(d.getDate() + days); this.updateDate(d); } }"' : '';
?>
<div class="<?= $outerClass ?? 'flex-grow relative flex items-center' ?>">
    <div class="flex w-full flex-col gap-1" <?= $alpineData ?>>
        <div class="flex w-full items-center gap-1">
            <input type="date" <?= $attributes; ?> maxlength="10"
            <?= $useDateActions ? 'x-ref="dateInput"' : '' ?>
            class="<?= $groupClass; ?> flex-1 min-w-0 rounded-md font-sans py-2 text-gray-900 placeholder:text-gray-500 focus:ring-1 focus:ring-inset focus:ring-blue-500 sm:text-sm sm:leading-5"
            />
            <?php if (!empty($todayButton)) { ?>
                <button type="button" x-on:click="setToday()" class="<?= $todayButtonClass ?>" aria-label="<?= __('Today') ?>" title="<?= __('Today') ?>" <?= !empty($readonly) ? 'disabled' : '' ?>><?= __('Today') ?></button>
            <?php } ?>
        </div>
        <?php if (!empty($dayOffsetButtons)) { ?>
            <div class="right flex w-full items-center justify-end gap-2" role="group" aria-label="<?= __('Quick date options') ?>">
                <?php foreach ($dayOffsetButtons as $days) {
                    $label = ($days > 0 ? '+' : '').$days;
                    $title = abs($days) === 1
                        ? sprintf(__('%s day'), $label)
                        : sprintf(__('%s days'), $label);
                ?>
                    <button type="button" x-on:click="addDays(<?= (int) $days ?>)" class="<?= $offsetButtonClass ?>" aria-label="<?= $title ?>" title="<?= $title ?>" <?= !empty($readonly) ? 'disabled' : '' ?>><?= $label ?></button>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div><?php
?>
