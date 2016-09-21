<?php
use Gibbon\core\trans ;
use Gibbon\core\helper ;
?>
<tr>
    <td>
        <?php echo $this->__($el->moduleName) ; ?>
    </td>
    <?php if ($el->installed) { ?>
        <td>
            <?php echo $this->__("Installed") ; ?>
        </td> <?php 
    }
    else {
        //Check for valid manifest
        $manifestOK = false;
        if (file_exists(GIBBON_ROOT . "src/modules/".$el->moduleName."/manifest.php")) {
            include GIBBON_ROOT . "src/modules/".$el->moduleName."/manifest.php";
            if (! empty($name) && ! empty($description) && ! empty($version)) {
                if ($name == $el->moduleName) {
                    $manifestOK = true ; 
                }
            }
        }
        if ($manifestOK) { ?>
            <td colspan=6>
                <?php echo $this->__("Not Installed") ; ?>
            </td> <?php
        }
        else { ?>
            <td colspan=7>
                <?php echo $this->__("Module error due to incorrect manifest file or folder name.") ; ?>
            </td> <?php
        }
    }
    if ($el->installed) { ?>
        <td>
            <?php echo $this->__( $el->moduleObj->getField("description")) ; ?>
        </td>
        <td>
            <?php echo $this->__( $el->moduleObj->getField("type")) ; ?>
        </td>
        <td>
            <?php echo $this->__($el->moduleObj->getField("active")) ; ?>
        </td>
        <td> <?php
            if ($el->moduleObj->getField("type")=="Additional") {
                echo 'v'.$el->moduleObj->getField("version") ; 
            }
            else { 
                echo isset($version) ? 'v'.$version : 'v'.$this->config->get('version') ;
            } ?>
        </td>
        <td> <?php
            if ( $el->moduleObj->getField("url") != "") { ?>
                <a href='<?php echo $el->moduleObj->getField("url"); ?>'><?php echo $el->moduleObj->getField("author"); ?></a> <?php
            }
            else {
                echo $el->moduleObj->getField("author") ; 
            } ?>
        </td>
        
        <?php if ((bool) $el->action) { ?>

        <td style='width: 150px'>
            <?php echo $this->getLink('edit', $this->session->get("absoluteURL")."/index.php?q=/modules/System Admin/module_manage_edit.php&gibbonModuleID=".$el->moduleObj->getField("gibbonModuleID")); 
            if ($el->moduleObj->getField("type")=="Additional") { 
                echo $this->getLink('uninstall', $this->session->get("absoluteURL")."/index.php?q=/modules/System Admin/module_manage_uninstall.php&gibbonModuleID=".$el->moduleObj->getField("gibbonModuleID"));
                echo $this->getLink('update', $this->session->get("absoluteURL")."/index.php?q=/modules/System Admin/module_manage_update.php&gibbonModuleID=".$el->moduleObj->getField("gibbonModuleID"));
            } ?>
        </td> <?php
        }
    }
    else {
        if ((bool) $el->action) { 
            if ($manifestOK) { ?>
                <td>
                    <?php echo $this->getLink('install', $this->session->get("absoluteURL")."/index.php?q=/modules/System Admin/module_manage_installProcess.php&gibbonModuleID=".$el->moduleObj->getField("gibbonModuleID")."&divert=true&name=".urlencode($el->moduleName)); ?>
                </td> <?php
            } 
        }
    } ?>
</tr>