            </table>
	<?php $x = new \Gibbon\Form\action(GIBBON_ROOT . 'modules/System Admin/i18n_manageProcess.php', null, $this);
    echo $x->renderReturn();
    $x = new \Gibbon\Form\hidden('divert', true, $this);
    echo $x->renderReturn(); 
    $x = new \Gibbon\Form\token(GIBBON_ROOT . 'modules/System Admin/i18n_manageProcess.php', null, $this);
    echo $x->renderReturn(); ?>
        </form>
<?php $this->addScript("
<script>
$('#TheForm input[name=\"gibboni18nCode\"]').change(function(){

    $('#TheForm').submit();    

});
</script>
");