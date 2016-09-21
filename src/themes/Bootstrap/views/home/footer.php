<?php
use Gibbon\core\trans ;
?>
<div id="footer">
    <?php print $this->__( "Powered by") ?> <a target='_blank' href="https://gibbonedu.org">Gibbon</a> v<?php print $this->config->get('version') ?><?php if ($this->session->get("cuttingEdgeCode")=="Y") { print "-dev" ; }?> | &#169; <a target='_blank' href="http://rossparker.org">Ross Parker</a> 2010-<?php print date("Y") ?><br/>
    <span style='font-size: 90%; '>
        <?php print $this->__( "Created under the") ?> <a target='_blank' href="https://www.gnu.org/licenses/gpl.html">GNU GPL</a> at <a target='_blank' href='http://www.ichk.edu.hk'>ICHK</a> | <a target='_blank' href='https://gibbonedu.org/contribute/'><?php print $this->__( "Credits") ; ?></a><br/>
        <?php
            $seperator=FALSE ;
            $thirdLine=FALSE ;
            if ($this->session->notEmpty("i18n.maintainerName") AND $this->session->get("i18n.maintainerName")!="Gibbon") {
                if ($this->session->notEmpty("i18n.maintainerWebsite")) {
                    echo $this->__('Translation led by %1$s', array(" <a target='_blank' href='" . $this->session->get("i18n.maintainerWebsite") . "'>" . $this->session->get("i18n.maintainerName") . "</a>")) ;
                }
                else {
                    echo $this->__('Translation led by %1$s', array(" " . $this->session->get("i18n.maintainerName"))) ;
                }
                $seperator=TRUE ;
                $thirdLine=TRUE ;
            }
            if ($this->session->get("theme.Name") != "Default" AND $this->session->notEmpty("theme.Author.name")) {
                if ($seperator) {
                    print " | " ;
                }
                if ($this->session->notEmpty("theme.Author.URL")) {
                    print $this->__('%1$s Theme by %2$s', array($this->session->get("theme.Name"), "<a target='_blank' href='" . $this->session->get("theme.Author.URL") . "'>" . $this->session->get("theme.Author.name") . "</a>")) ;
                }
                else {
                    print $this->__('%1$s Theme by %2$s', array($this->session->get("theme.Name"), $this->session->get("theme.Author.name"))) ;
                }
                $thirdLine=TRUE ;
            }
            if ($thirdLine==FALSE) {
                print "<br/>" ; 
            }
        ?>
    </span>
    <img style='z-index: 9999; margin-top: -82px; margin-left: 850px; opacity: 0.8' alt='Logo Small' src='<?php echo $this->session->get("theme.url") ?>/img/logoFooter.png'/>
	<?php
    echo "<br/>not real: ".(memory_get_peak_usage(false)/1024/1024)." MiB ";
    echo "real: ".(memory_get_peak_usage(true)/1024/1024)." MiB\n\n";
    echo 'SQL Connections: ' . $this->session->get('SQLConnection');
    $this->session->clear('SQLConnection');
	// Code ...

// Script end
function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

$ru = getrusage();
echo "<br />This process used " . rutime($ru, $this->session->get('rustart'), "utime") .
    " ms for its computations\n";
echo "It spent " . rutime($ru, $this->session->get('rustart'), "stime") .
    " ms in system calls\n";
	?>
</div><!-- home.footer -->
