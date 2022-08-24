<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CConfigFile {

	public const CONFIG_NOT_FOUND = 1;
	public const CONFIG_ERROR = 2;
	public const CONFIG_VAULT_ERROR = 3;

	private const DEFAULT_CONFIG_FILE = 'conf/zabbix.conf.php';
	private const DEFAULT_MAINTENANCE_CONFIG_FILE = 'conf/maintenance.inc.php';

	// This file can be used to override the above paths.
	private const OVERRIDES_FILE = 'conf/overrides.conf.php';

	private const SUPPORTED_DB_TYPES = [
		ZBX_DB_MYSQL => true,
		ZBX_DB_ORACLE => true,
		ZBX_DB_POSTGRESQL => true
	];

	private $config_file;
	private $maintenance_config_file;

	// The way config file will be displayed in Wizard.
	private $config_file_display_name;

	public $config = [];
	public $error = '';

	/**
	 * @param string $error Error message.
	 * @param int    $code  Error code.
	 *
	 * @throws ConfigFileException
	 */
	private static function exception(string $error, int $code = self::CONFIG_ERROR): void {
		throw new ConfigFileException($error, $code);
	}

	/**
	 * @throws ConfigFileException
	 */
	public function __construct() {
		$this->setDefaults();

		// Do not change these variable names, they may be used in overrides file.
		$CONFIG_FILE = APP::getRootDir().'/'.self::DEFAULT_CONFIG_FILE;
		$MAINTENANCE_CONFIG_FILE = self::DEFAULT_MAINTENANCE_CONFIG_FILE;

		$config_file_display_name = self::DEFAULT_CONFIG_FILE;

		if (file_exists(self::OVERRIDES_FILE)) {
			if (!is_readable(self::OVERRIDES_FILE)) {
				self::exception('Cannot access overrides file.');
			}

			ob_start();
			include(self::OVERRIDES_FILE);
			ob_end_clean();

			$config_file_display_name = $CONFIG_FILE;
		}

		$this->setFiles($CONFIG_FILE, $config_file_display_name, $MAINTENANCE_CONFIG_FILE);
	}

	/**
	 * Returns the full path to the config file.
	 *
	 * @return string
	 */
	public function getConfigFile(): string {
		return $this->config_file;
	}

	/**
	 * Returns the config file to be displayed in Wizard. Relative to the web server
	 * Document root by default and full path in case it was specified in the overrides file.
	 *
	 * @return string
	 */
	public function getConfigFileDisplayName(): string {
		return $this->config_file_display_name;
	}

	/**
	 * Returns the path to the maintenance config file. Relative to the web server
	 * Document root by default and full path in case it was specified in the overrides file.
	 *
	 * @return string
	 */
	public function getMaintenanceConfigFile(): string {
		return $this->maintenance_config_file;
	}

	/**
	 * Sets the full path to the configuration file and display name for Wizard.
	 *
	 * @param string $config_file
	 * @param string $config_file_display_name
	 * @param string $maintenance_config_file
	 */
	private function setFiles(string $config_file, string $config_file_display_name,
			string $maintenance_config_file): void {
		$this->config_file = $config_file;
		$this->config_file_display_name = $config_file_display_name;
		$this->maintenance_config_file = $maintenance_config_file;
	}

	/**
	 * @return array
	 * @throws ConfigFileException
	 *
	 */
	public function load(): array {
		if (!file_exists($this->config_file)) {
			self::exception('Config file does not exist.', self::CONFIG_NOT_FOUND);
		}

		if (!is_readable($this->config_file)) {
			self::exception('Permission denied.');
		}

		ob_start();
		include($this->config_file);
		ob_end_clean();

		if (!isset($DB['TYPE'])) {
			self::exception('DB type is not set.');
		}

		if (!array_key_exists($DB['TYPE'], self::SUPPORTED_DB_TYPES)) {
			self::exception(
				'Incorrect value "'.$DB['TYPE'].'" for DB type. Possible values '.
				implode(', ', array_keys(self::SUPPORTED_DB_TYPES)).'.'
			);
		}

		$php_supported_db = array_keys(CFrontendSetup::getSupportedDatabases());

		if (!in_array($DB['TYPE'], $php_supported_db)) {
			self::exception('DB type "'.$DB['TYPE'].'" is not supported by current setup.'.
				($php_supported_db ? ' Possible values '.implode(', ', $php_supported_db).'.' : '')
			);
		}

		if (!isset($DB['DATABASE'])) {
			self::exception('DB database is not set.');
		}

		$this->setDefaults();

		$this->config['DB']['TYPE'] = $DB['TYPE'];
		$this->config['DB']['DATABASE'] = $DB['DATABASE'];

		if (isset($DB['SERVER'])) {
			$this->config['DB']['SERVER'] = $DB['SERVER'];
		}

		if (isset($DB['PORT'])) {
			$this->config['DB']['PORT'] = $DB['PORT'];
		}

		if (isset($DB['USER'])) {
			$this->config['DB']['USER'] = $DB['USER'];
		}

		if (isset($DB['PASSWORD'])) {
			$this->config['DB']['PASSWORD'] = $DB['PASSWORD'];
		}

		if (isset($DB['SCHEMA'])) {
			$this->config['DB']['SCHEMA'] = $DB['SCHEMA'];
		}

		if (isset($DB['ENCRYPTION'])) {
			$this->config['DB']['ENCRYPTION'] = $DB['ENCRYPTION'];
		}

		if (isset($DB['VERIFY_HOST'])) {
			$this->config['DB']['VERIFY_HOST'] = $DB['VERIFY_HOST'];
		}

		if (isset($DB['KEY_FILE'])) {
			$this->config['DB']['KEY_FILE'] = $DB['KEY_FILE'];
		}

		if (isset($DB['CERT_FILE'])) {
			$this->config['DB']['CERT_FILE'] = $DB['CERT_FILE'];
		}

		if (isset($DB['CA_FILE'])) {
			$this->config['DB']['CA_FILE'] = $DB['CA_FILE'];
		}

		if (isset($DB['CIPHER_LIST'])) {
			$this->config['DB']['CIPHER_LIST'] = $DB['CIPHER_LIST'];
		}

		if (isset($DB['DOUBLE_IEEE754'])) {
			$this->config['DB']['DOUBLE_IEEE754'] = $DB['DOUBLE_IEEE754'];
		}

		if (isset($DB['VAULT_URL'])) {
			$this->config['DB']['VAULT_URL'] = $DB['VAULT_URL'];
		}

		if (isset($DB['VAULT_DB_PATH'])) {
			$this->config['DB']['VAULT_DB_PATH'] = $DB['VAULT_DB_PATH'];
		}

		if (isset($DB['VAULT_TOKEN'])) {
			$this->config['DB']['VAULT_TOKEN'] = $DB['VAULT_TOKEN'];
		}

		if (isset($ZBX_SERVER) && $ZBX_SERVER !== '') {
			$this->config['ZBX_SERVER'] = $ZBX_SERVER;
		}

		if (isset($ZBX_SERVER_PORT) && $ZBX_SERVER_PORT !== '') {
			$this->config['ZBX_SERVER_PORT'] = $ZBX_SERVER_PORT;
		}

		if (isset($ZBX_SERVER_NAME)) {
			$this->config['ZBX_SERVER_NAME'] = $ZBX_SERVER_NAME;
		}

		if (isset($IMAGE_FORMAT_DEFAULT)) {
			$this->config['IMAGE_FORMAT_DEFAULT'] = $IMAGE_FORMAT_DEFAULT;
		}

		if (isset($HISTORY)) {
			$this->config['HISTORY'] = $HISTORY;
		}

		if (isset($SSO)) {
			$this->config['SSO'] = $SSO;
		}

		if ($this->config['DB']['VAULT_URL'] !== ''
				&& $this->config['DB']['VAULT_DB_PATH'] !== ''
				&& $this->config['DB']['VAULT_TOKEN'] !== '') {
			[$this->config['DB']['USER'], $this->config['DB']['PASSWORD']] = $this->getCredentialsFromVault();

			if ($this->config['DB']['USER'] === '' || $this->config['DB']['PASSWORD'] === '') {
				self::exception(_('Unable to load database credentials from Vault.'), self::CONFIG_VAULT_ERROR);
			}
		}

		$this->makeGlobal();

		return $this->config;
	}

	protected function getCredentialsFromVault(): array {
		$username = CDataCacheHelper::getValue('db_username', '');
		$password = CDataCacheHelper::getValue('db_password', '');

		if ($username === '' || $password === '') {
			$vault = new CVaultHelper($this->config['DB']['VAULT_URL'], $this->config['DB']['VAULT_TOKEN']);
			$secret = $vault->loadSecret($this->config['DB']['VAULT_DB_PATH']);

			$username = array_key_exists('username', $secret) ? $secret['username'] : '';
			$password = array_key_exists('password', $secret) ? $secret['password'] : '';

			if ($username !== '' && $password !== '') {
				// Update cache.
				CDataCacheHelper::setValueArray([
					'db_username' => $username,
					'db_password' => $password
				]);
			}
			else {
				CDataCacheHelper::clearValues(['db_username', 'db_password']);
			}
		}

		return [$username, $password];
	}

	public function makeGlobal(): void {
		global $DB, $ZBX_SERVER, $ZBX_SERVER_PORT, $ZBX_SERVER_NAME, $IMAGE_FORMAT_DEFAULT, $HISTORY, $SSO;

		$DB = $this->config['DB'];
		$ZBX_SERVER = $this->config['ZBX_SERVER'];
		$ZBX_SERVER_PORT = $this->config['ZBX_SERVER_PORT'];
		$ZBX_SERVER_NAME = $this->config['ZBX_SERVER_NAME'];
		$IMAGE_FORMAT_DEFAULT = $this->config['IMAGE_FORMAT_DEFAULT'];
		$HISTORY = $this->config['HISTORY'];
		$SSO = $this->config['SSO'];
	}

	public function save(): bool {
		try {
			$file = $this->config_file;

			if (is_null($file)) {
				self::exception('Cannot save, config file is not set.');
			}

			$this->check();

			if (is_link($file)) {
				$file = readlink($file);
			}

			$file_is_writable = (!file_exists($file) && is_writable(dirname($file))) || is_writable($file);

			if ($file_is_writable && file_put_contents($file, $this->getString())) {
				if (!chmod($file, 0600)) {
					self::exception(_('Unable to change configuration file permissions to 0600.'));
				}
			}
			elseif (is_readable($file)) {
				if (file_get_contents($file) !== $this->getString()) {
					self::exception(_('Unable to overwrite the existing configuration file.'));
				}
			}
			else {
				self::exception(_('Unable to create the configuration file.'));
			}

			return true;
		}
		catch (Exception $e) {
			$this->error = $e->getMessage();
			return false;
		}
	}

	public function getString(): string {
		return
'<?php
// Zabbix GUI configuration file.

$DB[\'TYPE\']				= \''.addcslashes($this->config['DB']['TYPE'], "'\\").'\';
$DB[\'SERVER\']			= \''.addcslashes($this->config['DB']['SERVER'], "'\\").'\';
$DB[\'PORT\']				= \''.addcslashes($this->config['DB']['PORT'], "'\\").'\';
$DB[\'DATABASE\']			= \''.addcslashes($this->config['DB']['DATABASE'], "'\\").'\';
$DB[\'USER\']				= \''.addcslashes($this->config['DB']['USER'], "'\\").'\';
$DB[\'PASSWORD\']			= \''.addcslashes($this->config['DB']['PASSWORD'], "'\\").'\';

// Schema name. Used for PostgreSQL.
$DB[\'SCHEMA\']			= \''.addcslashes($this->config['DB']['SCHEMA'], "'\\").'\';

// Used for TLS connection.
$DB[\'ENCRYPTION\']		= '.($this->config['DB']['ENCRYPTION'] ? 'true' : 'false').';
$DB[\'KEY_FILE\']			= \''.addcslashes($this->config['DB']['KEY_FILE'], "'\\").'\';
$DB[\'CERT_FILE\']		= \''.addcslashes($this->config['DB']['CERT_FILE'], "'\\").'\';
$DB[\'CA_FILE\']			= \''.addcslashes($this->config['DB']['CA_FILE'], "'\\").'\';
$DB[\'VERIFY_HOST\']		= '.($this->config['DB']['VERIFY_HOST'] ? 'true' : 'false').';
$DB[\'CIPHER_LIST\']		= \''.addcslashes($this->config['DB']['CIPHER_LIST'], "'\\").'\';

// Vault configuration. Used if database credentials are stored in Vault secrets manager.
$DB[\'VAULT_URL\']		= \''.addcslashes($this->config['DB']['VAULT_URL'], "'\\").'\';
$DB[\'VAULT_DB_PATH\']	= \''.addcslashes($this->config['DB']['VAULT_DB_PATH'], "'\\").'\';
$DB[\'VAULT_TOKEN\']		= \''.addcslashes($this->config['DB']['VAULT_TOKEN'], "'\\").'\';

// Use IEEE754 compatible value range for 64-bit Numeric (float) history values.
// This option is enabled by default for new Zabbix installations.
// For upgraded installations, please read database upgrade notes before enabling this option.
$DB[\'DOUBLE_IEEE754\']	= '.($this->config['DB']['DOUBLE_IEEE754'] ? 'true' : 'false').';

// Uncomment and set to desired values to override Zabbix hostname/IP and port.
// $ZBX_SERVER			= \'\';
// $ZBX_SERVER_PORT		= \'\';

$ZBX_SERVER_NAME		= \''.addcslashes($this->config['ZBX_SERVER_NAME'], "'\\").'\';

$IMAGE_FORMAT_DEFAULT	= IMAGE_FORMAT_PNG;

// Uncomment this block only if you are using Elasticsearch.
// Elasticsearch url (can be string if same url is used for all types).
//$HISTORY[\'url\'] = [
//	\'uint\' => \'http://localhost:9200\',
//	\'text\' => \'http://localhost:9200\'
//];
// Value types stored in Elasticsearch.
//$HISTORY[\'types\'] = [\'uint\', \'text\'];

// Used for SAML authentication.
// Uncomment to override the default paths to SP private key, SP and IdP X.509 certificates, and to set extra settings.
//$SSO[\'SP_KEY\']			= \'conf/certs/sp.key\';
//$SSO[\'SP_CERT\']			= \'conf/certs/sp.crt\';
//$SSO[\'IDP_CERT\']		= \'conf/certs/idp.crt\';
//$SSO[\'SETTINGS\']		= [];
';
	}

	private function setDefaults(): void {
		$this->config['DB'] = [
			'TYPE' => null,
			'SERVER' => 'localhost',
			'PORT' => '0',
			'DATABASE' => null,
			'USER' => '',
			'PASSWORD' => '',
			'SCHEMA' => '',
			'ENCRYPTION' => false,
			'KEY_FILE' => '',
			'CERT_FILE' => '',
			'CA_FILE' => '',
			'VERIFY_HOST' => true,
			'CIPHER_LIST' => '',
			'DOUBLE_IEEE754' => false,
			'VAULT_URL' => '',
			'VAULT_DB_PATH' => '',
			'VAULT_TOKEN' => ''
		];
		$this->config['ZBX_SERVER'] = null;
		$this->config['ZBX_SERVER_PORT'] = null;
		$this->config['ZBX_SERVER_NAME'] = '';
		$this->config['IMAGE_FORMAT_DEFAULT'] = IMAGE_FORMAT_PNG;
		$this->config['HISTORY'] = null;
		$this->config['SSO'] = null;
	}

	/**
	 * @throws ConfigFileException
	 */
	private function check(): void {
		if (!isset($this->config['DB']['TYPE'])) {
			self::exception('DB type is not set.');
		}

		if (!array_key_exists($this->config['DB']['TYPE'], self::SUPPORTED_DB_TYPES)) {
			self::exception(
				'Incorrect value "'.$this->config['DB']['TYPE'].'" for DB type. Possible values '.
				implode(', ', array_keys(self::SUPPORTED_DB_TYPES)).'.'
			);
		}

		if (!isset($this->config['DB']['DATABASE'])) {
			self::exception('DB database is not set.');
		}
	}
}
