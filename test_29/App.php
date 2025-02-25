<?php

use Application\AppManager;
use Core\Constants\Cookie;
use Enum\Log\LogAliases;
use Enum\Redis\RedisAliases;
use Enum\Config;
use Helpers\Security\CompromisedManager;
use Illuminate\Support\Carbon;
use Model\Application;
use Model\User;
use User\UserAuthDeviceManager;

class App {

	const APP_MODE_PRODUCTION = "stage";

	const APP_MODE_DEV = "dev";

	const APP_STAND_MASTER = "master";

	const APP_STAND_D = "d*";

	const APP_MODE_LOCAL = "local";

	const DB_MASTER = "master";
	const DB_SLAVE = "slave";
	const DB_WORK = "work";
	const DB_SPHINX = "sphinx";
	const DB_SPHINX_MESSAGES = "sphinx-messages";
	const DB_SPHINX_KWORK = "sphinx-kwork";
	const DB_SPHINX_CLUSTER = "manticore-cluster";

	public const MANTICORE_RT_CONNECTION = self::DB_SPHINX_CLUSTER;

	public const SPHINX_DATABASES = [
		self::DB_SPHINX,
		self::DB_SPHINX_MESSAGES,
		self::DB_SPHINX_KWORK,
		self::DB_SPHINX_CLUSTER,
	];

	const VALID_DATABASES = [
		self::DB_MASTER,
		self::DB_SLAVE,
		self::DB_WORK,
		self::DB_SPHINX,
		self::DB_SPHINX_MESSAGES,
		self::DB_SPHINX_KWORK,
		self::DB_SPHINX_CLUSTER,
	];

	private static $isShowAuthCaptcha = null;

	private static bool $isMobileApi = false;

	public static function getConnectionName($table) {
		if (in_array($table, self::DB_WORK_TABLES)) {
			return App::DB_WORK;
		}

		return self::DB_MASTER;
	}

	public static function isDebugEnable(): bool {
		return self::config(Config::APP_MODE) !== self::APP_MODE_PRODUCTION;
	}

	public static function isProductionMode(): bool {
		return self::config(Config::APP_MODE) === self::APP_MODE_PRODUCTION;
	}

	public static function isMasterStand(): bool {
		return self::config(Config::APP_STAND) === self::APP_STAND_MASTER;
	}

	public static function isDStand(): bool {
		return self::config(Config::APP_STAND) === self::APP_STAND_D;
	}

	public static function isLocalMode(): bool {
		return self::config(Config::APP_MODE) === self::APP_MODE_LOCAL;
	}

	public static function isDevMode(): bool {
		return self::isMasterStand() || self::isDStand() || self::isLocalMode();
	}

	public static function isDevStageMode(): bool {
		return self::config(Config::APP_MODE) === self::APP_MODE_DEV;
	}

	public static function isMobileMode(): bool {
		return MobileDetect::getInstance()->isMobile();
	}

	public static function isTabletMode(): bool {
		return MobileDetect::getInstance()->isTablet();
	}

	public static function isMobileApi(): bool {
		return self::$isMobileApi;
	}

	public static function isWebView(): bool {
		return strpos($_SERVER["HTTP_USER_AGENT"] ?? "", "KworkMobileAppWebView") !== false;
	}

	public static function isMobileApplication(): bool {
		return self::isMobileApi() || self::isWebView();
	}

	public static function isIOs(): bool {
		return MobileDetect::getInstance()->isIOs();
	}

	public static function isApplePhoneOrPad(): bool {
		return self::isIOs() && (self::isTabletMode() || self::isMobileMode());
	}

	public static function isAndroid(): bool {
		return MobileDetect::getInstance()->is("AndroidOS");
	}

	public static function isSafari(): bool {
		$userAgent = (string)$_SERVER["HTTP_USER_AGENT"];

		$ver = "[\\w._\\+]+";

		return preg_match("!Version/$ver\\b.*?\\bSafari/$ver!", $userAgent) > 0;
	}

	public static function config(string $param, ?string $lang = null) {
		return Configurator::getInstance($lang, Configurator::MODE_CACHE_USING)->get($param);
	}

	public static function getHost(): string {
		if (empty($_SERVER["HTTP_HOST"])) {
			return App::config("originurl");
		}

		if (stripos(App::config("mirrorurl"), "//" . $_SERVER["HTTP_HOST"]) > 0) {
			return App::config("mirrorurl");
		}

		return App::config("originurl");
	}

	public static function getDomain() {
		return str_replace(["http://", "https://"], "", App::getHost());
	}

	public static function isMirror() {
		return App::getHost() == App::config("mirrorurl");
	}

	public static function isNotMirror(): bool {
		return !self::isMirror();
	}

	public static function send404() {
		$smarty = new \Smarty\CachedProxy();
		header("HTTP/1.0 404 Not Found");
		$smarty->assign("isPageNotFound", true);
		$cacheTime = Helper::ONE_DAY;
		$cacheFile = App::config("basedir") . "/temporary/" . str_replace(["..","/","\\"], "", App::getDomain()) . "_not_found.html";
		if (file_exists($cacheFile)  && time() - $cacheTime < filemtime($cacheFile) && filesize($cacheFile)) {
			include_once $cacheFile;
		} else {
			$pagetitle = \Localization\LocalizationManager::translate("notFoundPageTitle", "pages/not-access");
			$smarty->assign("pagetitle", $pagetitle);
			$cacheContent = $smarty->fetch("not_found.tpl");

			FileManager::filePutContents($cacheFile, $cacheContent);

			print $cacheContent;
		}
		exit;
	}

	public static function send403Forbidden(){
		header('HTTP/1.0 403 Forbidden');
		exit;
	}

	public static function isShowAuthCaptcha(?int $userId = null): bool {
		if (self::$isShowAuthCaptcha === null) {
			self::$isShowAuthCaptcha = self::checkShowAuthCaptcha($userId);
		}

		return self::$isShowAuthCaptcha;
	}

	public static function isNeedMobileBanner(?User $user = null): bool {

		if (\Smarty\CriticalStylesPagesManager::isInBuildCriticalStylesMode()) {
			return true;
		}

		if (!AdminManager::isAdminPage()) {

			$domainAccess = false;
			foreach (self::config(Config::MOBILE_BANNER_DOMAINS) as $domain) {
				if (strpos(App::getDomain(), $domain) !== false) {
					$domainAccess = true;
					break;
				}
			}

			if ($domainAccess) {
				$showDate = CookieManager::get(Cookie::SHOW_MOBILE_APP_BANNER_DATE);

				if ($user instanceof User) {

					if ($user->data->hasMobileApp()) {
						return false;
					}

					if ($user->data->show_mobile_banner) {

						if ($user->data->show_mobile_banner <= Carbon::now()) {
							return true;
						} else {

							if (!$showDate) {
								CookieManager::set(Cookie::SHOW_MOBILE_APP_BANNER_DATE, 1, $user->data->show_mobile_banner->unix());
								return false;
							}
						}
					}
				}

				if (!$showDate) {
					return true;
				}
			}
		}

		return false;
	}

	public static function setShowAuthCaptcha(bool $showAuthCaptcha): void {
		self::$isShowAuthCaptcha = $showAuthCaptcha;
	}

	public static function unsetShowAuthCaptcha(): void {
		self::$isShowAuthCaptcha = null;
	}

	private static function checkShowAuthCaptcha(?int $userId) {

		if (self::isLocalMode() || UserManager::getCurrentUserId() || (!self::isProductionMode() && self::isWhiteIp())) {
			if (App::config(Config::AUTH_ALWAYS_SHOW_CAPTCHA)) {
				return (bool)App::config(reCAPTCHA::CAPTCHA_ENABLE_CONFIG_OPTION);
			}
			return false;
		}

		if (!App::allowUseGoogle()) {
			return false;
		}

		if (!reCAPTCHA::hasInvalidLoginAttempts()) {
			return false;
		} else {

			if ($userId && UserAuthDeviceManager::isUserLoginSuspicion($userId,
				(string)CookieManager::get(UserAuthDeviceManager::COOKIE_NAME))) {
				return true;
			}
		}

		if (reCAPTCHA::hasRegisterIp()) {
			return true;
		}

		return (bool)App::config(reCAPTCHA::CAPTCHA_ENABLE_CONFIG_OPTION);
	}

	public static function getGuid(int $length = 32): string {
		$bytesLength = $length > 1 ? $length / 2 : 1;
		return bin2hex(random_bytes($bytesLength));
	}

	public static function getPhoneVerifyCode() {
		return random_int(1000, 9999);
	}

	public static function pdo($database = self::DB_MASTER) {
		return DataBasePDO::getInstance($database);
	}

	public static function pdoSlave() {
		return DataBasePDO::getInstance(self::DB_SLAVE);
	}

	public static function end() {
		exit;
	}

	public static function isServiceUser($userId): bool {
		return in_array($userId, self::getServiceUserIds());
	}

	public static function getServiceUserIds(): array {
		static $result;

		if (!$result) {
			$result = [
				UserManager::getSupportUserId(),
				(int)App::config(Config::MODERATOR_ID),
				(int)App::config(Config::SERVICE_USER_ID),
			];
		}

		return $result;
	}

	public static function allowUseGoogle() {
		$return = App::config('disallow_use_google');
		return $return ? false : true;
	}

	public static function isSSL(): bool {
		if (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == 1 || strtolower($_SERVER["HTTPS"]) == "on")) {
			return true;
		}
		if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] == 443) {
			return true;
		}
		if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) == "https") {
			return true;
		}
		if (isset($_SERVER["HTTP_X_FORWARDED_PORT"]) && $_SERVER["HTTP_X_FORWARDED_PORT"] == 443) {
			return true;
		}

		return false;
	}

	public static function isMarketApp(): bool {
		return self::getCurrentAppCode() === Application::APP_MARKET;
	}

	public static function isExchangeApp(): bool {
		return self::getCurrentAppCode() === Application::APP_EXCHANGE;
	}

	public static function isExchangeAppLimited(): bool {
		return self::isExchangeApp() && !self::isExchangeReleased();
	}

	public static function isExchangeReleased(): bool {
		return \App::config("exchange.release") !== false;
	}

	public static function isEnterpriseApp(): bool {
		return self::getCurrentAppCode() === Application::APP_ENTERPRISE;
	}

	public static function isIqTestApp(): bool {
		return self::getCurrentAppCode() === Application::APP_IQTEST;
	}

	public static function getCurrentApp(): Application {
		return AppManager::getInstance()->getCurrentApp();
	}

	public static function getCurrentAppId(): int {
		return self::getCurrentApp()->getKey();
	}

	public static function getCurrentAppCode(): string {
		return self::getCurrentApp()->code;
	}

	public static function getCurrentAppName(): string {
		return self::getCurrentApp()->name;
	}

	public static function getAppBaseUrl(?string $lang = null): string {
		if ($lang) {
			return App::config(Config::BASEURL, $lang);
		}

		return self::getCurrentApp()->baseurl;
	}

	public static function validateApp(): void {

		if (App::isExchangeApp()) {
			$smarty = new \Smarty\CachedProxy();
			$smarty->display("exchange/close.tpl");
			exit;
		}
	}

	private static function isWhiteIp() : bool {
		$ip = BanManager::getIp();

		if (empty($ip)) {
			return false;
		}

		$list = array(
			"10.0.7.0/24"
		);

		if (BanManager::compareIps($ip, $list)) {
			return true;
		}

		return false;
	}

	public static function checkImportantTables(): void {
		if (!App::config("app.check_tables_exists")) {
			return;
		}

		$sleep = App::config("app.check_tables_exists.sleep") ?? 1;
		$times = App::config("app.check_tables_exists.times") ?? 10;

		$broken = RedisManager::getInstance()->get(RedisAliases::FOUND_REMOVE_IMPORTANT_TABLE);

		if (!empty($broken)) {

			if (!RedisManager::getInstance()->exists(RedisAliases::FOUND_REMOVE_IMPORTANT_TABLE_LOG)) {

				RedisManager::getInstance()->set(RedisAliases::FOUND_REMOVE_IMPORTANT_TABLE_LOG, 1, Helper::ONE_MINUTE);
				Log::daily("Необходимо убрать таблицы " . $broken . " из BaseTablesList, затем запустить скрипт 7741_clear.php", LogAliases::ERROR_LOG);
			}
		} else {
			$i = 0;
			do {
				$tables = App::pdo()->fetchAllByColumn("SHOW TABLES");
				$i++;
				$haveErrors = false;
				$notFoundTables = [];
				foreach (BaseTablesList::$list as $table) {
					if (!in_array($table, $tables)) {
						$notFoundTables[] = $table;
						$haveErrors = true;
					}
				}
				if (!$haveErrors) {
					break;
				}
				sleep($sleep);
			} while ($i < $times);

			if ($haveErrors) {
				if (!RedisManager::getInstance()->exists(RedisAliases::FOUND_REMOVE_IMPORTANT_TABLE)) {
					RedisManager::getInstance()->set(RedisAliases::FOUND_REMOVE_IMPORTANT_TABLE, implode(", ", $notFoundTables));
					RedisManager::getInstance()->set(RedisAliases::FOUND_REMOVE_IMPORTANT_TABLE_LOG, 1, Helper::ONE_MINUTE);
				}
			}
		}
	}

	public static function isNewDesign(): bool {
		if (!self::isMarketApp()) {
			return false;
		}
		if (!Translations::isDefaultLang()) {
			return false;
		}
		return true;
	}

	public static function showDebugPanel(): bool {
		return App::config("sqllog.enable") && App::isDebugEnable();
	}

	public static function setMobileApi(): void {
		self::$isMobileApi = true;
	}

	public static function isTopKworkCom(): bool {
		return self::config(Config::BASEURL) === "https://topkwork.com";
	}

	public static function isPPJ(): bool {
		return self::config(Config::BASEURL) === "https://paperjettech.com";
	}
}