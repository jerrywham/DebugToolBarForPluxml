<?php
	if(!defined('PLX_ROOT')) exit; 
	
	# Control du token du formulaire
	plxToken::validateFormToken($_POST);
	$aPublic = array(
		0 => $plxPlugin->getLang('L_NO'),
		1 => $plxPlugin->getLang('L_YES')
	);
	if(!empty($_POST)) {
		$plxPlugin->setParam('public', $_POST['public'], 'numeric');
		$plxPlugin->saveParams();
		header('Location: plugin.php?p=DebugToolBarForPluxml');
		exit;
	}
?>

<h2><?php $plxPlugin->lang('L_TITLE') ?></h2>
<p><?php $plxPlugin->lang('L_CONFIG_DESCRIPTION') ?></p>

<form action="plugin.php?p=DebugToolBarForPluxml" method="post">
	<fieldset class="withlabel">
		<h2><?php echo $plxPlugin->getLang('L_CONFIG_PUBLIC') ?></h2>
		<p style="color:red;"><?php echo $plxPlugin->getLang('L_BE_CAREFUL_IN_PRODUCTION') ?></p>
		<p>&nbsp;</p>
		<?php echo plxUtils::printSelect('public', $aPublic, $plxPlugin->getParam('public')); ?>
	</fieldset>
	<br />
	<?php echo plxToken::getTokenPostMethod() ?>
	<input type="submit" name="submit" value="<?php echo $plxPlugin->getLang('L_CONFIG_SAVE') ?>" />
</form>
