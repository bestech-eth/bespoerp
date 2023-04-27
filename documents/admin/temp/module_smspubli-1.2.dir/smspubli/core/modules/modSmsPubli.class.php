<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2017-2021  Josep Lluís Amador <joseplluis@lliuretic.cat>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   smspubli     Module SmsPubli
 *  \brief      SmsPubli module descriptor.
 *
 *  \file       custom/smspubli/core/modules/modSmsPubli.class.php
 *  \ingroup    smspubli
 *  \brief      Module for send SMS by SMSpubli.
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module SmsPubli
 */
class modSmsPubli extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

		$this->numero = 409000;
		$this->rights_class = 'smspubli';
		$this->family = 'interface';
		$this->module_position = 500;
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Send SMS to your thirdparties around the world with smspubli.com";
		$this->descriptionlong = "You can send SMS to your thirdparties around the world with SmsPubli<br />
		   Create and account and start sending SMS with a very good price.";
		$this->editor_name = 'LliureTIC';
		$this->editor_url = 'https://www.lliuretic.cat';
		$this->version = '1.2';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where SMSPUBLI is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'email';
		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
            'sms' => 1,
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'theme' => 0,
			'dir' => array(),
			'workflow' => array(),
			'hooks' => array(),
		);

		$this->config_page_url = array("setup.php@smspubli");
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('smspubli@smspubli');
		$this->phpmin = array(5, 5);
		$this->need_dolibarr_version = array(3, 2);

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// );
		$this->const = array(
			1 => array('SMSPUBLI_FAKESMS', 'chaine', '1', 'Send fake SMS for testing', 1, 'current', 1)
		);

        // Some keys to add into the overwriting translation tables
        /*$this->overwrite_translation = array(
            'en_US:ParentCompany'=>'Parent company or reseller',
            'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if (!isset($conf->lliuretictest) || !isset($conf->lliuretictest->enabled)) {
            $conf->lliuretictest = new stdClass();
            $conf->lliuretictest->enabled = 0;
        }

        // Array to add new pages in new tabs
        $this->tabs = array(
			0 => 'thirdparty:+sendsmspubli:SendSMS:smspubli@smspubli:$user->rights->smspubli->send:/smspubli/send.php?id=__ID__'
        );

		// Permissions provided by this module
		$this->rights[] = array(
			0 => 40900001,
			1 => 'Send a single SMS',
			3 => 0,
			4 => 'send'
		);
		
		$this->rights[] = array(
			0 => 40900002,
			1 => 'Send a massive SMS',
			3 => 0,
			4 => 'sendmulti'
		);

        // Main menu entries to add
		$this->menu[] = array(
			'fk_menu'=>'fk_mainmenu=tools',
			'type'=>'left',
			'titre'=>'SendSMS',
			'mainmenu'=>'tools',
			'leftmenu'=>'smspubli',
			'url'=>'/smspubli/send.php',
			'langs'=>'smspubli@smspubli',
			'position'=>100,
			'enabled'=>'$conf->smspubli->enabled',
			'perms'=>'$user->rights->smspubli->send',
			'target'=>'',
			'user'=>0
		);

	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$result = $this->_load_tables('/smspubli/sql/');
		if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

		$sql = array();
		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		$sql[] = 'DELETE FROM '.MAIN_DB_PREFIX.'const WHERE name LIKE "MAIN_MODULE_SMSPUBLI%"';
		$sql[] = 'DELETE FROM '.MAIN_DB_PREFIX.'const WHERE name LIKE "SMSPUBLI%"';
		return $this->_remove($sql, $options);
	}
}
