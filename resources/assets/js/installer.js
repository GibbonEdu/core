(function () {
    $(document).ready(function() {

        // If an element with id "status" is found, do the version check
        // Supposed to only have one "#status" in the page, so this is only run once.
        $("#status").first().each(function () {
            var $status = $(this);
            var $edgeIndicator = $('#cuttingEdgeCode');
            var $edgeHiddenInput = $("input[name=cuttingEdgeCodeHidden]");

            // environment check
            var gibboninstallerError = false;
            if (typeof gibboninstaller === 'undefined') {
                console.error('Unable to find gibboninstaller in global variables');
                gibboninstallerError = true;
            } else if (typeof gibboninstaller.version === 'undefined') {
                console.error('No gibbon version is specified in the environment');
                gibboninstallerError = true;
            } else if (typeof gibboninstaller.msg === 'undefined') {
                console.error('Translation function gibboninstaller.msg() does not exits.');
                gibboninstallerError = true;
            }
            if (gibboninstallerError) {
                $status.attr("class", "error");
                $status.html("Cutting Edge Code check: Unexpected javascript error.");
                return;
            }

            // cutting edge code check
            $.ajax({
                crossDomain: true,
                type:"GET",
                url: "https://gibbonedu.org/services/version/devCheck.php?version=" + gibboninstaller.version + "&callback=?",
                dataType: "jsonp",
                jsonpCallback: 'fnsuccesscallback',
                jsonpResult: 'jsonpResult',
                success: function(data) {
                    $status.attr("class", "success");
                    if (data['status'] === 'false') {
                        $status.html(gibboninstaller.msg('__edge_code_check_success__')) ;
                    } else {
                        $status.html(gibboninstaller.msg('__edge_code_check_success__')) ;
                        $edgeIndicator.val('Yes');
                        $edgeHiddenInput.val('Y');
                    }
                },
                error: function(data, textStatus, errorThrown) {
                    $status.attr("class", "error");
                    $status.html(gibboninstaller.msg('__edge_code_check_failed__')) ;
                }
            });
        });
    });
})();
