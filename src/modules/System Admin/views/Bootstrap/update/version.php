<?php 
use Gibbon\core\trans ;
$version = $this->config->get('version'); 
$this->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		$.ajax({
			crossDomain: true, type:"GET", contentType: "application/json; charset=utf-8",async: false,
			url: "https://gibbonedu.org/services/version/version.php?callback=?",
			data: "", dataType: "jsonp", jsonpCallback: "fnsuccesscallback",jsonpResult: "jsonpResult",
			success: function(data) {
				if (data["version"]==="false") {
					$("#status").attr("class", "error");
					$("#status").html("'.trans::__('Version check failed.').'") ;
				}
				else {
					if (parseFloat(data["version"]) <= parseFloat("'.$version.'")) {
						$("#status").attr("class", "success");
						$("#status").html("'.trans::__('Version check successful. Your Gibbon installation is up to date at %1$s.', array($version)) .' '. trans::__('If you have recently updated your system files, please check that your database is up to date in %1$sUpdates%2$s.', array("<a href='" . $this->session->get('absoluteURL') . "/index.php?q=/modules/System%20Admin/update.php'>", '</a>')).'") ;
					}
					else {
						$("#status").attr("class", "warning");
						$("#status").html("'.trans::__('Version check successful. Your Gibbon installation is out of date. Please visit %1$sthe Gibbon download page%2$s to download the latest version.', array("<a target='blank' href='https://gibbonedu.org/download'>", "</a>")).'") ;
					}
				}
			},
			error: function (data, textStatus, errorThrown) {
				$("#status").attr("class", "error");
				$("#status").html("'.trans::__('Version check failed.').'") ;
			}
		});
	});
</script>
');
		
$cuttingEdgeCode=$this->config->getSettingByScope( "System", "cuttingEdgeCode" ) ; 
if ($cuttingEdgeCode == "N") { ?>
        <div id='status' class='warning'>
            <div style='width: 100%; text-align: center'>
                <img style='margin: 10px 0 5px 0' src='<?php echo $this->session->get('absoluteURL'); ?>/themes/<?php echo $this->session->get('theme.Name'); ?>/img/loading.gif' alt='Loading'/><br/>
            	<?php echo trans::__("Checking for Gibbon updates.") ; ?>
            </div>
        </div>
<?php }
