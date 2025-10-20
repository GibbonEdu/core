<div class="flex-grow relative inline-flex">
    <input type="text" <?= $attributes; ?> class="w-full min-w-0 py-2 rounded-l-md placeholder:text-gray-500  sm:text-sm sm:leading-6">

    <button type="button" class="-ml-px px-4 bg-gray-100 inline-flex items-center border border-gray-400 rounded-r-md text-base text-gray-600" onclick="scanner(this)">
        <?= icon('solid', 'qr-code', 'pointer-events-none size-5 text-gray-700 fill-current'); ?>
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
