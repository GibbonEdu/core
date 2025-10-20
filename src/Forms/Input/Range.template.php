<div class="flex-grow relative inline-flex justify-between items-center" x-data="{'rangeValue': '<?= $value; ?>'}">
    <span class="flex-shrink text-left text-gray-600 font-normal mr-2"><?= $min; ?></span>
    <input type="range" class="flex-grow" <?= $attributes; ?> x-model="rangeValue">
    <span class="flex-shrink text-right text-gray-600 font-normal ml-2"><?= $max; ?></span>
    <output class="block w-12 p-1 ml-4 border rounded-md bg-gray-200" x-html="rangeValue"><?= $value; ?></output>
</div>
