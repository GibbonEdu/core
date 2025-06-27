
<?php 
    if ($group == 'left') $groupClass = 'rounded-l-md -mr-px';
    elseif ($group == 'right') $groupClass = 'rounded-r-md -ml-px';
    elseif ($group == 'middle') $groupClass = 'rounded-none';
    else $groupClass = 'rounded-md';
?>

<div x-data="{ show: true, policy: false, pw: '' }" class="flex-grow relative flex">
    <input x-model.fill="pw" :type="show ? 'password' : 'text'" <?= $attributes; ?> autocomplete="off" 
    class="<?= $class; ?> <?= $groupClass; ?> w-full min-w-0  py-2 placeholder:text-gray-500  sm:text-sm sm:leading-6 <?= $type != 'text' ? 'input-icon' : ''; ?>
    <?= !empty($readonly) ? 'border-dashed text-gray-600 cursor-not-allowed :ring-0 focus:border-gray-400' : 'text-gray-900 focus:ring-1 focus:ring-inset focus:ring-blue-500'; ?>"
    <?= !empty($policy) ? ' @focus="policy=true;pw=$el.value" @blur="policy=false;pw=$el.value" @click.away="policy=false"' : ''; ?>
    />

    <span class="absolute top-0.5 right-0.5">

        <button type="button" @click="show = !show" :class="{'hidden': !show, 'block':show }" tabindex="-1">
        <?= icon('basic', 'eye', 'pointer-events-none size-9 p-2 rounded bg-white text-gray-500 hover:text-gray-700'); ?>
        </button>

        <button type="button" @click="show = !show" :class="{'block': !show, 'hidden':show }" tabindex="-1">
        <?= icon('basic', 'eye-slash', 'pointer-events-none size-9 p-2 rounded bg-white text-gray-500 hover:text-gray-700'); ?>
        </button>

    </span>

    <?php if (!empty($policy)) { ?>

    <div x-cloak x-show="policy" x-transition x-data="{meterColor: 'bg-green-400'}" class="absolute mt-10 w-full z-50 bg-white border rounded-md p-2" x-init="$watch('pw', function(value) { meterColor = value.length >= 16 && pw.match(/<?= $policyPattern ?>/) ? 'bg-green-500' : value.length >= 12 ? 'bg-lime-500' : value.length >= 6 ? 'bg-yellow-500' : 'bg-red-600'; } )">

        <?= __('Password Strength') ?><br>
        <div class="flex gap-1 w-full my-2" >
            <div class="h-1 flex-1 rounded"
                x-bind:class="pw.length > 0 ? meterColor : 'bg-gray-200'"></div>
            <div class="h-1 flex-1 rounded"
                x-bind:class="pw.length >= 6 ? meterColor : 'bg-gray-200'"></div>
            <div class="h-1 flex-1 rounded"
                x-bind:class="pw.length >= 12 ? meterColor : 'bg-gray-200'"></div>
            <div class="h-1 flex-1 rounded"
                x-bind:class="pw.length >= 16 && pw.match(/<?= $policyPattern ?>/) ? meterColor : 'bg-gray-200'"></div>
        </div>

        <?= __('The password policy stipulates that passwords must:') ?><br>

        <ul class="list-none ml-2 mb-0 text-xs">
        <?php foreach ($policy as $pattern => $description) { ?>

            <li>
                <span x-show="pw.match(/<?= $pattern ?>/)" class="align-middle mr-0.5">
                    <?= icon('solid', 'check', 'pointer-events-none size-3 text-green-600'); ?>
                </span>
                <span x-show="!pw.match(/<?= $pattern ?>/)" class="align-middle mr-0.5">
                    <?= icon('solid', 'help', 'pointer-events-none size-3 text-gray-400'); ?>
                </span>
                <?= $description ?>
            </li>
        <?php } ?>
        </ul>
    </div>

    <?php } ?>
</div>


