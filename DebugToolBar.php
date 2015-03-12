<?php
/**
 * Plugin Maintenance
 *
 * @package	PLX
 * @version	4.0
 * @date	10/10/2013
 * @author	Cyril MAGUIRE
 **/
include_once(PLX_PLUGINS.'DebugToolBar/class_outils_debug.php');
class DebugToolBar extends plxPlugin {

	public $Debug;
	
	public function __construct($default_lang) {
		
		# appel du constructeur de la classe plxPlugin (obligatoire)
		parent::__construct($default_lang);

		$this->setAdminProfil(PROFIL_ADMIN);

		$this->Debug = Debug::getDebugInstance($default_lang,$this->getInfo('version'));
		
		# Déclarations des hooks
		$this->addHook('AdminFootEndBody', 'printToolBar');
		if($this->getParam('public') == 1)
			$this->addHook('ThemeEndBody', 'printToolBar');

		$this->addHook('AdminTopBottom', 'AdminTopBottom');
	}
	/**
	 * Méthode qui préconfigure le plugin
	 *
	 * @return	stdio
	 * @author	Cyril MAGUIRE
	 **/
	public function onActivate() {
		#Paramètres par défaut
		if(!is_file($this->plug['parameters.xml'])) {
			$this->setParam('public', 0, 'numeric');
			$this->saveParams();
		}
	}
	/**
	 * Méthode qui affiche la barre de débug
	 * @return stdio
	 * @author Cyril MAGUIRE
	 */
	public function printToolBar() {
		$this->Debug->printBar();
	}
	/**
	 * Méthode qui affiche un message s'il y a un message à afficher
	 *
	 * @return	stdio
	 * @author	Stephane F, Cyril MAGUIRE
	 **/
	public function AdminTopBottom() {
		
			$string = '
			if (str_pad(str_replace(".","",$plxAdmin->aConf["version"]), 3, "0", STR_PAD_RIGHT) < 520 ) {
				$DebugToolBar = $plxAdmin->plxPlugins->aPlugins["DebugToolBar"]["instance"];
			} else {
				$DebugToolBar = $plxAdmin->plxPlugins->aPlugins["DebugToolBar"];
			}
			if($DebugToolBar->getParam("public")==1) {
				echo "<p class=\"notice\">".$DebugToolBar->getLang("L_DEBUG_ACTIVATED_IN_PUBLIC")."</p>";
				plxMsg::Display();
			}';
			echo '<?php '.$string.' ?>';
	}

	
}
?>
