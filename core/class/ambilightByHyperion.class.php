<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
function rgb2hex($r, $g, $b)
{
	$r = dechex($r);
	if (strlen($r) == 1) {
		$r = '0' . $r;
	}
	$g = dechex($g);
	if (strlen($g) == 1) {
		$g = '0' . $g;
	}
	$b = dechex($b);
	if (strlen($b) == 1) {
		$b = '0' . $b;
	}
	$hex = '#' . $r . $g . $b;
	return $hex;
}

class hyperionNG extends eqLogic
{
	/*     * *************************Attributs****************************** */

	/*
	* Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
	* Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	*/

	public static $_widgetPossibility = array('custom' => true);

	/*
	* Permet de crypter/décrypter automatiquement des champs de configuration du plugin
	* Exemple : "param1" & "param2" seront cryptés mais pas "param3"
	public static $_encryptConfigKey = array('param1', 'param2');
	*/

	/*     * ***********************Methode static*************************** */

	public static function update()
	{
		foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
			$autorefresh = $eqLogic->getConfiguration('autorefresh');
			if ($autorefresh != '') {
				try {
					$c = new Cron\CronExpression(checkAndFixCron($autorefresh), new Cron\FieldFactory);
					if ($c->isDue()) {
						$readServerinfo = $eqLogic->socket(null, null, array('command' => 'serverinfo'));
						$eqLogic->refreshData($readServerinfo);
					}
				} catch (Exception $exc) {
					log::add(__CLASS__, 'error', $eqLogic->getHumanName() . ' : Invalid cron expression : ' . $autorefresh);
				}
			}
		}
	}

	/*
	* Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron() {}
	*/

	/*
	* Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
	public static function cron5() {}
	*/

	/*
	* Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
	public static function cron10() {}
	*/

	/*
	* Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
	public static function cron15() {}
	*/

	/*
	* Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
	public static function cron30() {}
	*/

	/*
	* Fonction exécutée automatiquement toutes les heures par Jeedom
	public static function cronHourly() {}
	*/

	/*
	* Fonction exécutée automatiquement tous les jours par Jeedom
	public static function cronDaily() {}
	*/

	public static function templateWidget()
	{
		$return = array('info' => array('string' => array()));
		$return['action']['other']['checkbox'] = array(
			'template' => 'tmpliconline',
			'replace' => array(
				'#_icon_on_#' => "<i class='far fa-check-square' style='font-size: 20px; margin-left: 5px; position: relative; top: 2px;'></i>",
				'#_icon_off_#' => "<i class='far fa-square' style='font-size: 20px; margin-left: 5px; position: relative; top: 2px;'></i>"
			)
		);
		return $return;
	}

	/*     * *********************Méthodes d'instance************************* */

	// Fonction exécutée automatiquement avant la création de l'équipement
	public function preInsert()
	{
		$this->setIsEnable(1);
		$this->setIsVisible(1);
		$this->setCategory('light', 1);
	}

	// Fonction exécutée automatiquement après la création de l'équipement
	public function postInsert()
	{
	}

	// Fonction exécutée automatiquement avant la mise à jour de l'équipement
	public function preUpdate()
	{
	}

	// Fonction exécutée automatiquement après la mise à jour de l'équipement
	public function postUpdate()
	{
	}

	// Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
	public function preSave()
	{
		if (empty($this->getConfiguration('port'))) {
			$this->setConfiguration('port', 19444);
		}
		if (empty($this->getConfiguration('instanceNumber'))) {
			$this->setConfiguration('instanceNumber', 0);
		}
	}

	// Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
	public function postSave()
	{
		$order = 0;
		$commands = json_decode(file_get_contents(dirname(__FILE__) . '/../config/cmds/commands.json'), true);
		// log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $commands : ' . json_encode($commands));
		foreach ($commands as $command) {
			// log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $command : ' . json_encode($command));
			$cmd = $this->getCmd(null, $command['logicalId']);
			if (!is_object($cmd)) {
				log::add(__CLASS__, 'info', $this->getHumanName() . ' : Command [' . $command['name'] . '] created');
				$cmd = (new hyperionNGCmd);
				$cmd->setLogicalId($command['logicalId']);
				if (isset($command['generic_type'])) {
					$cmd->setGeneric_type($command['generic_type']);
				}
				$cmd->setName($command['name']);
				$cmd->setOrder($order++);
				$cmd->setType($command['type']);
				$cmd->setSubType($command['subType']);
				$cmd->setEqLogic_id($this->getId());
				// if (isset($command['isHistorized'])) {
				// 	$cmd->setIsHistorized($command['isHistorized']);
				// }
				if (isset($command['unite'])) {
					$cmd->setUnite($command['unite']);
				}
				if (isset($command['configuration'])) {
					foreach ($command['configuration'] as $key => $value) {
						$cmd->setConfiguration($key, $value);
					}
				}
				if (isset($command['template'])) {
					foreach ($command['template'] as $key => $value) {
						$cmd->setTemplate($key, $value);
					}
				}
				if (isset($command['display'])) {
					foreach ($command['display'] as $key => $value) {
						$cmd->setDisplay($key, $value);
					}
				}
				if (isset($command['value'])) {
					$cmd->setValue($this->getCmd(null, $command['value'])->getId());
				}
				if (isset($command['isVisible'])) {
					$cmd->setIsVisible($command['isVisible']);
				}
				$cmd->save();
			}
		}
	}

	// Fonction exécutée automatiquement avant la suppression de l'équipement
	public function preRemove()
	{
	}

	// Fonction exécutée automatiquement après la suppression de l'équipement
	public function postRemove()
	{
	}

	/*
	* Permet de crypter/décrypter automatiquement des champs de configuration des équipements
	* Exemple avec le champ "Mot de passe" (password)
	public function decrypt() {
		$this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
	}
	public function encrypt() {
		$this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
	}
	*/

	/*
	* Permet de modifier l'affichage du widget (également utilisable par les commandes)
	public function toHtml($_version = 'dashboard') {}
	*/

	/*
	* Permet de déclencher une action avant modification d'une variable de configuration du plugin
	* Exemple avec la variable "param3"
	public static function preConfig_param3( $value ) {
		// do some checks or modify on $value
		return $value;
	}
	*/

	/*
	* Permet de déclencher une action après modification d'une variable de configuration du plugin
	* Exemple avec la variable "param3"
	public static function postConfig_param3($value) {
		// no return value
	}
	*/

	public function socket($dataInstance, $dataCommand, $dataServerinfo)
	{
		if ($this->getIsEnable() == 1) {
			$create = socket_create(AF_INET, SOCK_STREAM, 0);
			if ($create == false) {
				log::add(__CLASS__, 'warning', $this->getHumanName() . ' : Erreur socket_create() : ' . socket_strerror(socket_last_error()));
			}
			$connect = socket_connect($create, $this->getConfiguration('ipAdress'), $this->getConfiguration('port'));
			if ($connect == false) {
				log::add(__CLASS__, 'warning', $this->getHumanName() . ' : Erreur socket_connect() : ' . socket_strerror(socket_last_error()));
			}
			if (!empty($dataInstance)) {
				$encodeInstance = json_encode($dataInstance) . "\n";
				$writeInstance = socket_write($create, $encodeInstance, strlen($encodeInstance));
				if ($writeInstance === false) {
					log::add(__CLASS__, 'warning', $this->getHumanName() . ' : Erreur socket_write() : ' . socket_strerror(socket_last_error()));
				} else {
					log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $encodeInstance : ' . $encodeInstance);
					$readInstance = socket_read($create, 128, PHP_NORMAL_READ);
					if ($readInstance == false) {
						log::add(__CLASS__, 'warning', $this->getHumanName() . ' : Erreur socket_read() : ' . socket_strerror(socket_last_error()));
					} else {
						log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $readInstance : ' . $readInstance);
					}
				}
			}
			if (!empty($dataCommand)) {
				$encodeCommand = json_encode($dataCommand) . "\n";
				$writeCommand = socket_write($create, $encodeCommand, strlen($encodeCommand));
				if ($writeCommand === false) {
					log::add(__CLASS__, 'warning', $this->getHumanName() . ' : Erreur socket_write() : ' . socket_strerror(socket_last_error()));
				} else {
					log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $encodeCommand : ' . $encodeCommand);
					$readCommand = socket_read($create, 128, PHP_NORMAL_READ);
					if ($readCommand == false) {
						log::add(__CLASS__, 'warning', $this->getHumanName() . ' : Erreur socket_read() : ' . socket_strerror(socket_last_error()));
					} else {
						log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $readCommand : ' . $readCommand);
					}
				}
			}
			sleep(1);
			if (!empty($dataServerinfo)) {
				$encodeServerinfo = json_encode($dataServerinfo) . "\n";
				$writeServerinfo = socket_write($create, $encodeServerinfo, strlen($encodeServerinfo));
				if ($writeServerinfo === false) {
					log::add(__CLASS__, 'warning', $this->getHumanName() . ' : Erreur socket_write() : ' . socket_strerror(socket_last_error()));
				} else {
					log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $encodeServerinfo : ' . $encodeServerinfo);
					$readServerinfo = socket_read($create, 32768, PHP_NORMAL_READ);
					if ($readServerinfo == false) {
						log::add(__CLASS__, 'warning', $this->getHumanName() . ' : Erreur socket_read() : ' . socket_strerror(socket_last_error()));
					} else {
						log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $readServerinfo : ' . $readServerinfo);
					}
				}
			}
			socket_close($create);
			return $readServerinfo;
		}
	}

	public function refreshData($readServerinfo)
	{
		if ($this->getIsEnable() == 1) {
			if (!empty($readServerinfo)) {
				$decodeServerinfo = json_decode($readServerinfo, true);
				$colorState = rgb2hex($decodeServerinfo['info']['activeLedColor'][0]['RGB Value'][0], $decodeServerinfo['info']['activeLedColor'][0]['RGB Value'][1], $decodeServerinfo['info']['activeLedColor'][0]['RGB Value'][2]);
				$effectState = $decodeServerinfo['info']['activeEffects'][0]['name'];
				$priorities = $decodeServerinfo['info']['priorities'];
				$this->checkAndUpdateCmd('connectionState', 1);
				if ($colorState != '#000000' || !empty($effectState) || !empty($priorities)) {
					$this->checkAndUpdateCmd('ambilightState', 1);
				} else {
					$this->checkAndUpdateCmd('ambilightState', 0);
				}
				$this->checkAndUpdateCmd('colorState', $colorState);
				if (!empty($effectState)) {
					$this->checkAndUpdateCmd('effectState', $effectState);
				} else {
					$this->checkAndUpdateCmd('effectState', '');
				}
				$this->checkAndUpdateCmd('brightnessState', $decodeServerinfo['info']['adjustment'][0]['brightness']);
				$this->checkAndUpdateCmd('backlightThresholdState', $decodeServerinfo['info']['adjustment'][0]['backlightThreshold']);
				$this->checkAndUpdateCmd('backlightColoredState', $decodeServerinfo['info']['adjustment'][0]['backlightColored']);
				$this->checkAndUpdateCmd('hyperionState', $decodeServerinfo['info']['components'][0]['enabled']);
				$this->checkAndUpdateCmd('smoothingState', $decodeServerinfo['info']['components'][1]['enabled']);
				$this->checkAndUpdateCmd('blackBorderState', $decodeServerinfo['info']['components'][2]['enabled']);
				$this->checkAndUpdateCmd('forwarderState', $decodeServerinfo['info']['components'][3]['enabled']);
				$this->checkAndUpdateCmd('boblightServerState', $decodeServerinfo['info']['components'][4]['enabled']);
				$this->checkAndUpdateCmd('grabberState', $decodeServerinfo['info']['components'][5]['enabled']);
				$this->checkAndUpdateCmd('v4lState', $decodeServerinfo['info']['components'][6]['enabled']);
				$this->checkAndUpdateCmd('audioState', $decodeServerinfo['info']['components'][7]['enabled']);
				$this->checkAndUpdateCmd('ledDeviceState', $decodeServerinfo['info']['components'][8]['enabled']);
				foreach ($decodeServerinfo['info']['effects'] as $effects) {
					// log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $effects : ' . print_r($effects, true));
					// log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $effects[\'file\'] : ' . $effects['file']);
					// log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $effects[\'name\'] : ' . $effects['name']);
					if (substr($effects['file'], 0, 1) != ':') {
						$listValue .= $effects['name'] . '|' . $effects['name'] . ';';
					}
				}
				$listValue = rtrim($listValue, ';');
				// log::add(__CLASS__, 'debug', $this->getHumanName() . ' : $listValue : ' . $listValue);
				$cmd = $this->getCmd(null, 'userEffects');
				$listValueOld = $cmd->getConfiguration('listValue');
				if ($listValue != $listValueOld) {
					$cmd->setConfiguration('listValue', $listValue);
					$cmd->save();
				}
				log::add(__CLASS__, 'info', $this->getHumanName() . ' : Commandes mises à jour');
			} else {
				log::add(__CLASS__, 'warning', $this->getHumanName() . ' : Echec de la connexion');
				$this->checkAndUpdateCmd('connectionState', 0);
			}
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}

class hyperionNGCmd extends cmd
{
	/*     * *************************Attributs****************************** */

	/*
	public static $_widgetPossibility = array();
	*/

	/*     * ***********************Methode static*************************** */


	/*     * *********************Methode d'instance************************* */

	/*
	* Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	public function dontRemoveCmd() {
		return true;
	}
	*/

	// Exécution d'une commande
	public function execute($_options = array())
	{
		$dataInstance = array();
		$dataCommand = array();
		$dataServerinfo = array();
		if (intval($this->getEqLogic()->getConfiguration('instanceNumber')) != 0) {
			$dataInstance['command'] = 'instance';
			$dataInstance['subcommand'] = 'switchTo';
			$dataInstance['instance'] = intval($this->getEqLogic()->getConfiguration('instanceNumber'));
		}
		if ($this->getLogicalId() == 'color') {
			$dataCommand['command'] = 'color';
			$dataCommand['color'] = hex2rgb($_options['color']);
			if ((intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000) != 0) {
				$dataCommand['duration'] = intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000;
			}
			$dataCommand['priority'] = intval($this->getEqLogic()->getCmd(null, 'priorityState')->execCmd());
			$dataCommand['origin'] = 'Jeedom';
		} else if ($this->getLogicalId() == 'providedEffects') {
			$dataCommand['command'] = 'effect';
			$dataCommand['effect'] = array('name' => $_options['select']);
			if ((intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000) != 0) {
				$dataCommand['duration'] = intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000;
			}
			$dataCommand['priority'] = intval($this->getEqLogic()->getCmd(null, 'priorityState')->execCmd());
			$dataCommand['origin'] = 'Jeedom';
		} else if ($this->getLogicalId() == 'userEffects') {
			$dataCommand['command'] = 'effect';
			$dataCommand['effect'] = array('name' => $_options['select']);
			if ((intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000) != 0) {
				$dataCommand['duration'] = intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000;
			}
			$dataCommand['priority'] = intval($this->getEqLogic()->getCmd(null, 'priorityState')->execCmd());
			$dataCommand['origin'] = 'Jeedom';
		} else if ($this->getLogicalId() == 'brightness') {
			$dataCommand['command'] = 'adjustment';
			$dataCommand['adjustment'] = array('brightness' => intval($_options['slider']));
		} else if ($this->getLogicalId() == 'backlightThreshold') {
			$dataCommand['command'] = 'adjustment';
			$dataCommand['adjustment'] = array('backlightThreshold' => intval($_options['slider']));
		} else if ($this->getLogicalId() == 'duration') {
			$this->getEqLogic()->checkAndUpdateCmd('durationState', intval($_options['slider']));
			return;
		} else if ($this->getLogicalId() == 'priority') {
			$this->getEqLogic()->checkAndUpdateCmd('priorityState', intval($_options['slider']));
			return;
		} else if ($this->getLogicalId() == 'backlightColoredOn') {
			$dataCommand['command'] = 'adjustment';
			$dataCommand['adjustment'] = array('backlightColored' => true);
		} else if ($this->getLogicalId() == 'backlightColoredOff') {
			$dataCommand['command'] = 'adjustment';
			$dataCommand['adjustment'] = array('backlightColored' => false);
		} else if ($this->getLogicalId() == 'hyperionOn') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'ALL', 'state' => true);
		} else if ($this->getLogicalId() == 'hyperionOff') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'ALL', 'state' => false);
		} else if ($this->getLogicalId() == 'smoothingOn') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'SMOOTHING', 'state' => true);
		} else if ($this->getLogicalId() == 'smoothingOff') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'SMOOTHING', 'state' => false);
		} else if ($this->getLogicalId() == 'blackBorderOn') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'BLACKBORDER', 'state' => true);
		} else if ($this->getLogicalId() == 'blackBorderOff') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'BLACKBORDER', 'state' => false);
		} else if ($this->getLogicalId() == 'forwarderOn') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'FORWARDER', 'state' => true);
		} else if ($this->getLogicalId() == 'forwarderOff') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'FORWARDER', 'state' => false);
		} else if ($this->getLogicalId() == 'boblightServerOn') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'BOBLIGHTSERVER', 'state' => true);
		} else if ($this->getLogicalId() == 'boblightServerOff') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'BOBLIGHTSERVER', 'state' => false);
		} else if ($this->getLogicalId() == 'grabberOn') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'GRABBER', 'state' => true);
		} else if ($this->getLogicalId() == 'grabberOff') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'GRABBER', 'state' => false);
		} else if ($this->getLogicalId() == 'v4lOn') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'V4L', 'state' => true);
		} else if ($this->getLogicalId() == 'v4lOff') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'V4L', 'state' => false);
		} else if ($this->getLogicalId() == 'audioOn') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'AUDIO', 'state' => true);
		} else if ($this->getLogicalId() == 'audioOff') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'AUDIO', 'state' => false);
		} else if ($this->getLogicalId() == 'ledDeviceOn') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'LEDDEVICE', 'state' => true);
		} else if ($this->getLogicalId() == 'ledDeviceOff') {
			$dataCommand['command'] = 'componentstate';
			$dataCommand['componentstate'] = array('component' => 'LEDDEVICE', 'state' => false);
		} else if ($this->getLogicalId() == 'randomColor') {
			$dataCommand['command'] = 'color';
			$dataCommand['color'] = array(rand(0, 255), rand(0, 255), rand(0, 255));
			if ((intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000) != 0) {
				$dataCommand['duration'] = intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000;
			}
			$dataCommand['priority'] = intval($this->getEqLogic()->getCmd(null, 'priorityState')->execCmd());
			$dataCommand['origin'] = 'Jeedom';
		} else if ($this->getLogicalId() == 'randomEffect') {
			$randomEffect = array('Aucun', 'Atomic swirl', 'Blue mood blobs', 'Breath', 'Candle', 'Cinema brighten lights', 'Cinema dim lights', 'Cold mood blobs', 'Collision', 'Color traces', 'Double swirl', 'Fire', 'Flags Germany/Sweden', 'Full color mood blobs', 'Green mood blobs', 'Knight rider', 'Led Test', 'Light clock', 'Lights', 'Notify blue', 'Pac-Man', 'Plasma', 'Police Lights Single', 'Police Lights Solid', 'Rainbow mood', 'Rainbow swirl', 'Rainbow swirl fast', 'Random', 'Red mood blobs', 'Sea waves', 'Snake', 'Sparks', 'Strobe red', 'Strobe white', 'System Shutdown', 'Trails', 'Trails color', 'Warm mood blobs', 'Waves with Color', 'X-Mas');
			$dataCommand['command'] = 'effect';
			$dataCommand['effect'] = array('name' => $randomEffect[array_rand($randomEffect)]);
			if ((intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000) != 0) {
				$dataCommand['duration'] = intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000;
			}
			$dataCommand['priority'] = intval($this->getEqLogic()->getCmd(null, 'priorityState')->execCmd());
			$dataCommand['origin'] = 'Jeedom';
		} else if ($this->getLogicalId() == 'ambilightOn') {
			$dataCommand['command'] = 'color';
			$dataCommand['color'] = array(238, 173, 47);
			if ((intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000) != 0) {
				$dataCommand['duration'] = intval($this->getEqLogic()->getCmd(null, 'durationState')->execCmd()) * 1000;
			}
			$dataCommand['priority'] = intval($this->getEqLogic()->getCmd(null, 'priorityState')->execCmd());
			$dataCommand['origin'] = 'Jeedom';
		} else if ($this->getLogicalId() == 'reset') {
			$dataCommand['command'] = 'clear';
			$dataCommand['priority'] = -1;
		}
		$dataServerinfo['command'] = 'serverinfo';
		$readServerinfo = $this->getEqLogic()->socket($dataInstance, $dataCommand, $dataServerinfo);
		$this->getEqLogic()->refreshData($readServerinfo);
	}

	/*     * **********************Getteur Setteur*************************** */
}
