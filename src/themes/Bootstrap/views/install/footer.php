<div id="footer">
	<?php print Gibbon\core\trans::__( "Powered by") ?> <a href="https://gibbonedu.org">Gibbon</a> v<?php print $this->config->get('version'); ?> &#169; <a href="http://rossparker.org">Ross Parker</a> 2010-<?php print date("Y") ?><br/>
    <span style='font-size: 90%; '>
    	<?php print Gibbon\core\trans::__( "Created under the") ?> <a href="https://www.gnu.org/licenses/gpl.html">GNU GPL</a> at <a href='http://www.ichk.edu.hk'>ICHK</a>
    </span><br/>
    <img style='z-index: 9999; margin-top: -58px; margin-left: 850px; opacity: 0.8' alt='Logo Small' src='<?php echo $this->session->get("theme.url") ?>/img/logoFooter.png'/>
    <?php
    echo "<br/>not real: ".(memory_get_peak_usage(false)/1024/1024)." MiB ";
    echo "real: ".(memory_get_peak_usage(true)/1024/1024)." MiB\n\n";
    echo 'SQL Connections: ' . $this->session->get('SQLConnection');
    ?>
</div><!-- install.footer -->
