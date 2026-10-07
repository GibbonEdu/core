<?php
$buttonClass = 'inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-md border border-gray-400 bg-white px-3 py-2 text-xs sm:leading-5 font-medium text-gray-600 hover:border-blue-700 hover:bg-blue-200 hover:text-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 disabled:cursor-not-allowed disabled:opacity-50';
?>

<div class="<?= $outerClass ?? 'flex-grow relative flex items-center' ?>">
    <div class="flex w-full flex-col gap-1" 
        <?php if ($useDateActions) { ?> x-data="{ 
            formatDate(d) { 
                const month = ('0' + (d.getMonth() + 1)).slice(-2); 
                const day = ('0' + d.getDate()).slice(-2); 
                return d.getFullYear() + '-' + month + '-' + day; 
            }, 
            updateDate(d) { 
                this.$refs.dateInput.value = this.formatDate(d); 
                this.$refs.dateInput.dispatchEvent(new Event('input', { bubbles: true })); 
                this.$refs.dateInput.dispatchEvent(new Event('change', { bubbles: true })); 
            }, 
            setToday() { 
                this.updateDate(new Date());
            }, 
            addDays(days) { 
                let d = this.$refs.dateInput.value ? new Date(this.$refs.dateInput.value + 'T00:00:00') : new Date(); 
                if (Number.isNaN(d.getTime())) { d = new Date(); } d.setDate(d.getDate() + days); this.updateDate(d); 
            } 
        }" <?php } ?>
        >
        <div class="flex w-full items-center gap-1">
            <input type="date" <?= $attributes; ?> maxlength="10" x-ref="dateInput" class="<?= $groupClass; ?> flex-1 min-w-0 font-sans py-2 text-gray-900 placeholder:text-gray-500 focus:ring-1 focus:ring-inset focus:ring-blue-500 sm:text-sm sm:leading-5" />
            <?php if (!empty($todayButton)) { ?>
                <button type="button" x-on:click="setToday()" class="<?= $buttonClass ?>" aria-label="<?= __('Today') ?>" title="<?= __('Today') ?>" <?= !empty($readonly) ? 'disabled' : '' ?>><?= __('Today') ?></button>
            <?php } ?>

            <?php if (!empty($dayOffsetButtons)) { ?>
                <?php foreach ($dayOffsetButtons as $days) {
                    $label = ($days > 0 ? '+' : '') . $days;
                    $title = abs($days) === 1
                        ? sprintf(__('%s day'), $label)
                        : sprintf(__('%s days'), $label);
                ?>
                    <button type="button" x-on:click="addDays(<?= (int) $days ?>)" class="<?= $buttonClass ?>" aria-label="<?= $title ?>" title="<?= $title ?>" <?= !empty($readonly) ? 'disabled' : '' ?>><?= $label ?></button>
                <?php } ?>
            <?php } ?>
        </div>

    </div>
</div><?php
        ?>
