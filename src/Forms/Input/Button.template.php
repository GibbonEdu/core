<?php $groupClass .= !empty($groupAlign) ? ' text-sm/6' : ' text-sm'; ?>

<?php if ($type == 'blank') { ?>

    <button type="button" <?= $attributes; ?> class="<?= $class; ?> <?= $groupClass; ?> "><?= $value; ?></button>

<?php } elseif ($type == 'submit') { ?>
    <button type="submit" <?= $attributes; ?> x-data="{ submitDisabled: false }" x-bind:disabled="submitDisabled" x-on:submit="submitDisabled = true" @click="submitting = true" :class="{'submitted bg-gray-100': submitting, 'border-gray-800 bg-gray-800 hover:bg-gray-900' : !submitting}" class="<?= $class; ?> <?= $groupClass; ?> items-center px-8 py-2 font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 border border-gray-800 bg-gray-800 hover:bg-gray-900" />
    <span :class="{'opacity-0': submitting}"><?= $value; ?></span>
    </button>
<?php } elseif ($type == 'quickSubmit') { ?>

    <button type="submit" <?= $attributes; ?>  @click="submitting = true" :class="{'submitted': submitting}" class="<?= $class; ?> <?= $groupClass; ?> items-center px-4 py-2 font-semibold text-gray-800 shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 border border-gray-400 bg-gray-100 hover:bg-gray-200" >
    <span :class="{'opacity-0': submitting}"><?= $value; ?></span>
    </button>
<?php } elseif ($type == 'input') { ?>

    <input type="<?= $type; ?>" <?= $attributes; ?> class="<?= $class; ?> <?= $groupClass; ?> items-center border border-gray-400 px-8 py-2 font-semibold shadow-sm text-gray-800 hover:bg-gray-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 bg-gray-100"/>

<?php } elseif ($type == 'button') { ?>

    <button type="button" <?= $attributes; ?> class="<?= $class; ?> <?= $groupClass; ?> flex items-center border border-gray-400 px-4 py-2 font-semibold shadow-sm text-gray-800 hover:bg-gray-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 bg-gray-100">

    <?php $svgClass = 'text-gray-600 block m-0.5 h-5 w-5'.(!empty($value) ? 'lg:-ml-0.5 lg:mr-1.5' : ''); ?>

    <?php if ($icon == 'username') { ?>
        <svg class="<?= $svgClass; ?>" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
        </svg>
    <?php } elseif ($icon == 'password') { ?>
        <svg class="<?= $svgClass; ?>" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M15.75 1.5a6.75 6.75 0 0 0-6.651 7.906c.067.39-.032.717-.221.906l-6.5 6.499a3 3 0 0 0-.878 2.121v2.818c0 .414.336.75.75.75H6a.75.75 0 0 0 .75-.75v-1.5h1.5A.75.75 0 0 0 9 19.5V18h1.5a.75.75 0 0 0 .53-.22l2.658-2.658c.19-.189.517-.288.906-.22A6.75 6.75 0 1 0 15.75 1.5Zm0 3a.75.75 0 0 0 0 1.5A2.25 2.25 0 0 1 18 8.25a.75.75 0 0 0 1.5 0 3.75 3.75 0 0 0-3.75-3.75Z" clip-rule="evenodd" />
        </svg>
    <?php } elseif ($icon == 'calendar') { ?>
        <svg class="<?= $svgClass; ?>" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
        </svg>
    <?php } elseif ($icon == 'language') { ?>
        <svg class="<?= $svgClass; ?>" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M7.75 2.75a.75.75 0 0 0-1.5 0v1.258a32.987 32.987 0 0 0-3.599.278.75.75 0 1 0 .198 1.487A31.545 31.545 0 0 1 8.7 5.545 19.381 19.381 0 0 1 7 9.56a19.418 19.418 0 0 1-1.002-2.05.75.75 0 0 0-1.384.577 20.935 20.935 0 0 0 1.492 2.91 19.613 19.613 0 0 1-3.828 4.154.75.75 0 1 0 .945 1.164A21.116 21.116 0 0 0 7 12.331c.095.132.192.262.29.391a.75.75 0 0 0 1.194-.91c-.204-.266-.4-.538-.59-.815a20.888 20.888 0 0 0 2.333-5.332c.31.031.618.068.924.108a.75.75 0 0 0 .198-1.487 32.832 32.832 0 0 0-3.599-.278V2.75Z" />
        <path fill-rule="evenodd" d="M13 8a.75.75 0 0 1 .671.415l4.25 8.5a.75.75 0 1 1-1.342.67L15.787 16h-5.573l-.793 1.585a.75.75 0 1 1-1.342-.67l4.25-8.5A.75.75 0 0 1 13 8Zm2.037 6.5L13 10.427 10.964 14.5h4.073Z" clip-rule="evenodd" />
        </svg>
    <?php } ?>

    <?= $value; ?>

    </button>


<?php } ?>
