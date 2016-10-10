<?php
use Gibbon\core\trans ;
use Gibbon\Record\theme ;
?>
    <tr class="<?php echo isset($el->rowNum) ? $el->rowNum : ''; ?>">
        <td>
            <?php echo $this->__( ! $el->isEmpty('name') ? $el->getField('name') : $el->themeName ) ; ?>
        </td>
        <?php if ($el->installed) { ?>
            <td>
                <?php echo $this->__("Installed") ; ?>
            </td> <?php
        }
        else {
            //Check for valid manifest
            $manifestOK=FALSE ;
            if (include GIBBON_ROOT . "src/themes/".$el->themeName."/manifest.php") {
                if (! empty($name) && ! empty($description) && ! empty($version)) {
                    if ($name == $el->themeName) {
                        $manifestOK = true ;
                    }
                }
            }
            if ($manifestOK) { ?>
                <td colspan=5>
                    <?php echo $this->__("Not Installed") ; ?>
                </td> <?php
            }
            else { ?>
                <td colspan=6>
                    <?php echo $this->__("Theme Error") ; ?>
                </td> <?php
            }
        }
        if ($el->installed) { ?>
            <td>
                <?php echo $el->getField("description") ; ?>
            </td>
            <td> <?php
                if ($el->getField("name")=="Default") {
                    echo  "v" . $el->getField("version") ;
                }
                else {
                    if ($el->themeVersion > $el->getField("version")) {
                        //Update database
                        $gTheme = new theme($this, $el->getField("gibbonThemeID"));
                        if ($gTheme->getField('gibbonThemeID') == $el->getField("gibbonThemeID")){
                            $gTheme->setField('version', $themeVerison);
                            $gTheme->writeRecord();
                        }
                    } else {
                        $el->themeVersion = $el->getField("version") ;
                    }
                    echo "v" . $el->themeVersion ;
                } ?>
            </td>
            <td> <?php
                if ($el->getField("url")!="") { ?>
                    <a href='<?php echo $el->getField("url"); ?>'><?php echo $el->getField("author"); ?></a> <?php
                }
                else { 
                    print $el->getField("author") ;
                } ?>
            </td>
            <td> <?php if ($el->action) { 
                if ($el->getField("active")=="Y") { ?>
                    <input checked type='radio' name='gibbonThemeID' value='<?php echo $el->getField("gibbonThemeID"); ?>' class="radioclass" onchange="this.form.submit()" /> <?php
                }
                else { ?>
                    <input type='radio' name='gibbonThemeID' value='<?php echo $el->getField("gibbonThemeID"); ?>' class="radioclass" onchange="this.form.submit()" /> <?php
                } 
            } ?>

            </td>
    <?php if ($el->action) { ?>
            <td> <?php 
            if (! in_array($el->getField("name"), array("Default", 'Bootstrap')) && $el->getField('active') != 'Y') 
                $this->getLink('delete', array('q'=>'/modules/System Admin/theme_manage_uninstall.php', 'gibbonThemeID'=>$el->getField("gibbonThemeID")));
            ?>
            </td> <?php
            }
        }
        else {
            if ($manifestOK) { ?>
                <td><?php
                    $this->getLink('install', GIBBON_URL . 'index.php?q=/modules/System Admin/theme_manage_installProcess.php&divert=true&name='.urlencode($el->themeName));
                    ?>
                </td> <?php
            }
        } ?>
    </tr> 
