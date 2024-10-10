<div class="flex-grow relative inline-flex">
    <input type="text" <?= $attributes; ?> class="w-full min-w-0 py-2 rounded-l-md placeholder:text-gray-400  sm:text-sm sm:leading-6">

    <button type="button" class="-ml-px px-4 border border-gray-500 rounded-r-md text-base text-gray-600" onclick="scanner(this)">
        <svg class="w-4 h-4 mt-1 text-gray-700 fill-current" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m40 40h80v-40h-120v120h40z"/><path d="m392 0v40h80v80h40v-120z"/><path d="m40 392h-40v120h120v-40h-80z"/><path d="m472 472h-80v40h120v-120h-40z"/><path d="m76 236h160v-160h-160zm40-120h80v80h-80z"/><path d="m436 76h-160v160h160zm-40 120h-80v-80h80z"/><path d="m76 436h160v-160h-160zm40-120h80v80h-80z"/><path d="m316 316v-40h-40v80h40v40h40v-80z"/><path d="m356 396h80v40h-80z"/><path d="m396 356h40v-80h-80v40h40z"/><path d="m276 396h40v40h-40z"/></g></svg>
    </button>
</div>

<script type="text/javascript">
function scanner(self) {
    if ($("#preview").length > 0) {
        document.getElementById("preview").remove();
        document.getElementById("cameraButton").remove();
    } else {
        $(self).parent().parent().append('<video id="preview" class="w-64"></video>');
    }
    let scanner = new Instascan.Scanner({ video: document.getElementById("preview") });
        scanner.addListener("scan", function (content) {
        scanner.stop()
        $("input", $(self).parent()).val(content);
        document.getElementById("preview").remove()
        document.getElementById("cameraButton").remove();
        });
        Instascan.Camera.getCameras().then(function (cameras) {
        count = 0;
        if (cameras.length > 0) {
            scanner.start(cameras[count]);
            if (cameras.length > 1) {
            if ($("#cameraButton").length < 1 && $("#preview").length > 0) {
                $(self).parent().parent().append('<button type="button" class="button border rounded-r-md text-sm text-gray-600" id="cameraButton" style="height: 36px;">Change Camera</button>');
            }
            $("#cameraButton").on("click", function(){
                count++;
                if (count > cameras.length) {
                    count = 0;
                }
                scanner.start(cameras[count]);
            });
            }
        } else {
            scanner.stop()
            $("input", $(self).parent()).val("No camera available");
        }
        }).catch(function (e) {
        $("input", $(self).parent()).val("Camera Error");
        });   
}
</script>


<?php if (!empty($autocomplete)) { ?>
    <script type="text/javascript">
    $("#<?= $id; ?>").autocomplete({source: [<?= $autocomplete; ?>]});
    </script>
<?php } ?>

<?php if (!empty($unique)) { ?>
    <script type="text/javascript">
        $("#<?= $id; ?>").gibbonUniquenessCheck(<?= $unique; ?>);
    </script>
<?php } ?>
