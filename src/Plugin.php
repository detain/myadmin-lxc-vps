<?php

namespace Detain\MyAdminLxc;

use Detain\Lxc\Lxc;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Lxc Vps';
	public static $description = 'Allows selling of Lxc Server and VPS License Types.  More info at https://www.netenberg.com/lxc.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a lxc license. Allow 10 minutes for activation.';
	public static $module = 'vps';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log(self::$module, 'info', 'Lxc Activation', __LINE__, __FILE__);
			function_requirements('activate_lxc');
			activate_lxc($serviceClass->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$lxc = new Lxc(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', "IP Change - (OLD:".$serviceClass->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $lxc->editIp($serviceClass->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Lxc editIp('.$serviceClass->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->get_ip());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_lxc', 'icons/database_warning_48.png', 'ReUsable Lxc Licenses');
			$menu->add_link(self::$module, 'choice=none.lxc_list', 'icons/database_warning_48.png', 'Lxc Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.lxc_licenses_list', 'whm/createacct.gif', 'List all Lxc Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('crud_lxc_list', '/../vendor/detain/crud/src/crud/crud_lxc_list.php');
		$loader->add_requirement('crud_reusable_lxc', '/../vendor/detain/crud/src/crud/crud_reusable_lxc.php');
		$loader->add_requirement('get_lxc_licenses', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_requirement('get_lxc_list', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_requirement('lxc_licenses_list', '/../vendor/detain/myadmin-lxc-vps/src/lxc_licenses_list.php');
		$loader->add_requirement('lxc_list', '/../vendor/detain/myadmin-lxc-vps/src/lxc_list.php');
		$loader->add_requirement('get_available_lxc', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_requirement('activate_lxc', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_requirement('get_reusable_lxc', '/../vendor/detain/myadmin-lxc-vps/src/lxc.inc.php');
		$loader->add_requirement('reusable_lxc', '/../vendor/detain/myadmin-lxc-vps/src/reusable_lxc.php');
		$loader->add_requirement('class.Lxc', '/../vendor/detain/lxc-vps/src/Lxc.php');
		$loader->add_requirement('vps_add_lxc', '/vps/addons/vps_add_lxc.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_lxc_cost', 'LXC VPS Cost Per Slice:', 'LXC VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_LXC_COST'));
		//$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_lxc_server', 'LXC NJ Server', NEW_VPS_LXC_SERVER, 9, 1);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_lxc', 'Out Of Stock LXC Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LXC'), array('0', '1'), array('No', 'Yes',));
	}

}
