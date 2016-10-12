    <div style='position: relative'>
        <div class='ttClosure' style='z-index: <?php echo $el->zCount; ?>; position: absolute; width: <?php echo $el->width; ?>; height: <?php echo ceil($el->diffTime / 60)?>px; margin: 0px; padding: 0px; opacity:<?php echo $el->ttAlpha; ?>'>
            <div style='position: relative; top: 50%'>
                <span style='color: rgba(255,0,0,<?php echo $el->ttAlpha ?>);'><?php echo $this->__('School Closed')?></span>
            </div>
        </div>
        <!-- day.schoolClosedStart -->
        