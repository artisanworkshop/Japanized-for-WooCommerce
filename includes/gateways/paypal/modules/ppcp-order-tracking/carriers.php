<?php
/**
 * The order tracking carriers.
 *
 * @package WooCommerce\PayPalCommerce\OrderTracking
 */

declare(strict_types=1);

namespace WooCommerce\PayPalCommerce\OrderTracking;

use WooCommerce\PayPalCommerce\Vendor\Psr\Container\ContainerInterface;

	return array(
		'global' => array(
			'name'  => 'Global',
			'items' => array(
				'B_TWO_C_EUROPE'       => _x( 'B2C Europe', 'Name of carrier', 'woocommerce-for-japan' ),
				'CJ_LOGISTICS'         => _x( 'CJ Logistics', 'Name of carrier', 'woocommerce-for-japan' ),
				'CORREOS_EXPRESS'      => _x( 'Correos Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_ACTIVE_TRACING'   => _x( 'DHL Active Tracing', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_BENELUX'          => _x( 'DHL Benelux', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_GLOBAL_MAIL'      => _x( 'DHL ecCommerce US', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_GLOBAL_MAIL_ASIA' => _x( 'DHL eCommerce Asia', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL'                  => _x( 'DHL Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_GLOBAL_ECOMMERCE' => _x( 'DHL Global eCommerce', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_PACKET'           => _x( 'DHL Packet', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD'                  => _x( 'DPD Global', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD_LOCAL'            => _x( 'DPD Local', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD_LOCAL_REF'        => _x( 'DPD Local Reference', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPE_EXPRESS'          => _x( 'DPE Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPEX'                 => _x( 'DPEX Hong Kong', 'Name of carrier', 'woocommerce-for-japan' ),
				'DTDC_EXPRESS'         => _x( 'DTDC Express Global', 'Name of carrier', 'woocommerce-for-japan' ),
				'ESHOPWORLD'           => _x( 'EShopWorld', 'Name of carrier', 'woocommerce-for-japan' ),
				'FEDEX'                => _x( 'FedEx', 'Name of carrier', 'woocommerce-for-japan' ),
				'FLYT_EXPRESS'         => _x( 'FLYT Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'GLS'                  => _x( 'GLS', 'Name of carrier', 'woocommerce-for-japan' ),
				'IMX'                  => _x( 'IMX France', 'Name of carrier', 'woocommerce-for-japan' ),
				'INT_SUER'             => _x( 'International SEUR', 'Name of carrier', 'woocommerce-for-japan' ),
				'LANDMARK_GLOBAL'      => _x( 'Landmark Global', 'Name of carrier', 'woocommerce-for-japan' ),
				'MATKAHUOLTO'          => _x( 'Matkahuoloto', 'Name of carrier', 'woocommerce-for-japan' ),
				'OMNIPARCEL'           => _x( 'Omni Parcel', 'Name of carrier', 'woocommerce-for-japan' ),
				'ONE_WORLD'            => _x( 'One World', 'Name of carrier', 'woocommerce-for-japan' ),
				'POSTI'                => _x( 'Posti', 'Name of carrier', 'woocommerce-for-japan' ),
				'RABEN_GROUP'          => _x( 'Raben Group', 'Name of carrier', 'woocommerce-for-japan' ),
				'SF_EXPRESS'           => _x( 'SF EXPRESS', 'Name of carrier', 'woocommerce-for-japan' ),
				'SKYNET_Worldwide'     => _x( 'SkyNet Worldwide Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'SPREADEL'             => _x( 'Spreadel', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT'                  => _x( 'TNT Global', 'Name of carrier', 'woocommerce-for-japan' ),
				'UPS'                  => _x( 'UPS', 'Name of carrier', 'woocommerce-for-japan' ),
				'UPS_MI'               => _x( 'UPS Mail Innovations', 'Name of carrier', 'woocommerce-for-japan' ),
				'WEBINTERPRET'         => _x( 'WebInterpret', 'Name of carrier', 'woocommerce-for-japan' ),
			),

		),
		'AG'     => array(
			'name'  => _x( 'Antigua and Barbuda', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'CORREOS_AG' => _x( 'Correos Antigua and Barbuda', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'AR'     => array(
			'name'  => _x( 'Argentina', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'EMIRATES_POST' => _x( 'Emirates Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'OCA_AR	'       => _x( 'OCA Argentina', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'AU'     => array(
			'name'  => _x( 'Australia', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ADSONE'            => _x( 'Adsone', 'Name of carrier', 'woocommerce-for-japan' ),
				'AUSTRALIA_POST'    => _x( 'Australia Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'TOLL_AU'           => _x( 'Australia Toll', 'Name of carrier', 'woocommerce-for-japan' ),
				'BONDS_COURIERS'    => _x( 'Bonds Couriers', 'Name of carrier', 'woocommerce-for-japan' ),
				'COURIERS_PLEASE'   => _x( 'Couriers Please', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_AU'            => _x( 'DHL Australia', 'Name of carrier', 'woocommerce-for-japan' ),
				'DTDC_AU'           => _x( 'DTDC Australia', 'Name of carrier', 'woocommerce-for-japan' ),
				'FASTWAY_AU'        => _x( 'Fastway Australia', 'Name of carrier', 'woocommerce-for-japan' ),
				'HUNTER_EXPRESS	'   => _x( 'Hunter Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'SENDLE'            => _x( 'Sendle', 'Name of carrier', 'woocommerce-for-japan' ),
				'STARTRACK'         => _x( 'Star Track', 'Name of carrier', 'woocommerce-for-japan' ),
				'STARTRACK_EXPRESS' => _x( 'Star Track Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_AU	'           => _x( 'TNT Australia', 'Name of carrier', 'woocommerce-for-japan' ),
				'TOLL'              => _x( 'Toll', 'Name of carrier', 'woocommerce-for-japan' ),
				'UBI_LOGISTICS'     => _x( 'UBI Logistics', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'AT'     => array(
			'name'  => _x( 'Austria', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'AUSTRIAN_POST_EXPRESS' => _x( 'Austrian Post Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'AUSTRIAN_POST'         => _x( 'Austrian Post Registered', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_AT'                => _x( 'DHL Austria', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'BE'     => array(
			'name'  => _x( 'Belgium', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'BPOST'      => _x( 'bpost', 'Name of carrier', 'woocommerce-for-japan' ),
				'BPOST_INT'  => _x( 'bpost International', 'Name of carrier', 'woocommerce-for-japan' ),
				'MONDIAL_BE' => _x( 'Mondial Belgium', 'Name of carrier', 'woocommerce-for-japan' ),
				'TAXIPOST'   => _x( 'TaxiPost', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'BR'     => array(
			'name'  => _x( 'Brazil', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'CORREOS_BR'   => _x( 'Correos Brazil', 'Name of carrier', 'woocommerce-for-japan' ),
				'DIRECTLOG_BR' => _x( 'Directlog', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'BG'     => array(
			'name'  => _x( 'Bulgaria', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'BULGARIAN_POST' => _x( 'Bulgarian Post', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'CA'     => array(
			'name'  => _x( 'Canada', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'CANADA_POST' => _x( 'Canada Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'CANPAR'      => _x( 'Canpar', 'Name of carrier', 'woocommerce-for-japan' ),
				'GREYHOUND'   => _x( 'Greyhound', 'Name of carrier', 'woocommerce-for-japan' ),
				'LOOMIS'      => _x( 'Loomis', 'Name of carrier', 'woocommerce-for-japan' ),
				'PUROLATOR'   => _x( 'Purolator', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'CL'     => array(
			'name'  => _x( 'Chile', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'CORREOS_CL' => _x( 'Correos Chile', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'CN'     => array(
			'name'  => _x( 'China', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'FOUR_PX_EXPRESS' => _x( 'Correos', 'Name of carrier', 'woocommerce-for-japan' ),
				'AUPOST_CN'       => _x( 'AUPOST CHINA', 'Name of carrier', 'woocommerce-for-japan' ),
				'BQC_EXPRESS'     => _x( 'BQC Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'BUYLOGIC'        => _x( 'Buylogic', 'Name of carrier', 'woocommerce-for-japan' ),
				'CHINA_POST'      => _x( 'China Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'CNEXPS'          => _x( 'CN Exps', 'Name of carrier', 'woocommerce-for-japan' ),
				'EC_CN'           => _x( 'EC China', 'Name of carrier', 'woocommerce-for-japan' ),
				'EFS'             => _x( 'EFS', 'Name of carrier', 'woocommerce-for-japan' ),
				'EMPS_CN'         => _x( 'EMPS China', 'Name of carrier', 'woocommerce-for-japan' ),
				'EMS_CN'          => _x( 'EMS China', 'Name of carrier', 'woocommerce-for-japan' ),
				'HUAHAN_EXPRESS'  => _x( 'Huahan Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'SFC_EXPRESS'     => _x( 'SFC Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_CN'          => _x( 'TNT China', 'Name of carrier', 'woocommerce-for-japan' ),
				'WINIT'           => _x( 'WinIt', 'Name of carrier', 'woocommerce-for-japan' ),
				'YANWEN_CN'       => _x( 'Yanwen', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'CR'     => array(
			'name'  => _x( 'Costa Rica', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'CORREOS_CR' => _x( 'Correos De Costa Rica', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'HR'     => array(
			'name'  => _x( 'Croatia', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'HRVATSKA_HR' => _x( 'Hrvatska', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'CY'     => array(
			'name'  => _x( 'Cyprus', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'CYPRUS_POST_CYP' => _x( 'Cyprus Post', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'CZ'     => array(
			'name'  => _x( 'Czech Republic', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'CESKA_CZ' => _x( 'Ceska', 'Name of carrier', 'woocommerce-for-japan' ),
				'GLS_CZ'   => _x( 'GLS Czech Republic', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'FR'     => array(
			'name'  => _x( 'France', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'BERT'            => _x( 'BERT TRANSPORT', 'Name of carrier', 'woocommerce-for-japan' ),
				'CHRONOPOST_FR'   => _x( 'Chronopost France', 'Name of carrier', 'woocommerce-for-japan' ),
				'COLIPOSTE'       => _x( 'Coliposte', 'Name of carrier', 'woocommerce-for-japan' ),
				'COLIS'           => _x( 'Colis France', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_FR'          => _x( 'DHL France', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD_FR'          => _x( 'DPD France', 'Name of carrier', 'woocommerce-for-japan' ),
				'GEODIS'          => _x( 'GEODIS - Distribution & Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'GLS_FR'          => _x( 'GLS France', 'Name of carrier', 'woocommerce-for-japan' ),
				'LAPOSTE'         => _x( 'LA Poste', 'Name of carrier', 'woocommerce-for-japan' ),
				'MONDIAL'         => _x( 'Mondial Relay', 'Name of carrier', 'woocommerce-for-japan' ),
				'RELAIS_COLIS_FR' => _x( 'Relais Colis', 'Name of carrier', 'woocommerce-for-japan' ),
				'TELIWAY'         => _x( 'Teliway', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_FR'          => _x( 'TNT France', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'DE'     => array(
			'name'  => _x( 'Germany', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ASENDIA_DE'        => _x( 'Asendia Germany', 'Name of carrier', 'woocommerce-for-japan' ),
				'DELTEC_DE'         => _x( 'Deltec Germany', 'Name of carrier', 'woocommerce-for-japan' ),
				'DEUTSCHE_DE'       => _x( 'Deutsche', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_DEUTSCHE_POST' => _x( 'DHL Deutsche Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD_DE'            => _x( 'DPD Germany', 'Name of carrier', 'woocommerce-for-japan' ),
				'GLS_DE'            => _x( 'GLS Germany', 'Name of carrier', 'woocommerce-for-japan' ),
				'HERMES_DE'         => _x( 'Hermes Germany', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_DE'            => _x( 'TNT Germany', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'GR'     => array(
			'name'  => _x( 'Greece', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ELTA_GR'   => _x( 'ELTA Greece', 'Name of carrier', 'woocommerce-for-japan' ),
				'GENIKI_GR' => _x( 'Geniki Greece', 'Name of carrier', 'woocommerce-for-japan' ),
				'ACS_GR'    => _x( 'GRC Greece', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'HK'     => array(
			'name'  => _x( 'Hong Kong', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ASENDIA_HK'            => _x( 'Asendia Hong Kong', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_HK'                => _x( 'DHL Hong Kong', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD_HK'                => _x( 'DPD Hong Kong', 'Name of carrier', 'woocommerce-for-japan' ),
				'HK_POST'               => _x( 'Hong Kong Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'KERRY_EXPRESS_HK'      => _x( 'Kerry Express Hong Kong', 'Name of carrier', 'woocommerce-for-japan' ),
				'LOGISTICSWORLDWIDE_HK' => _x( 'Logistics Worldwide Hong Kong', 'Name of carrier', 'woocommerce-for-japan' ),
				'QUANTIUM'              => _x( 'Quantium', 'Name of carrier', 'woocommerce-for-japan' ),
				'SEKOLOGISTICS'         => _x( 'Seko Logistics', 'Name of carrier', 'woocommerce-for-japan' ),
				'TAQBIN_HK'             => _x( 'TA-Q-BIN Parcel Hong Kong', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'HU'     => array(
			'name'  => _x( 'Hungary', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'MAGYAR_HU' => _x( 'Magyar', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'IS'     => array(
			'name'  => _x( 'Iceland', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'POSTUR_IS' => _x( 'Postur', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'IN'     => array(
			'name'  => _x( 'India', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'BLUEDART'              => _x( 'Bluedart', 'Name of carrier', 'woocommerce-for-japan' ),
				'DELHIVERY_IN'          => _x( 'Delhivery', 'Name of carrier', 'woocommerce-for-japan' ),
				'DOTZOT'                => _x( 'DotZot', 'Name of carrier', 'woocommerce-for-japan' ),
				'DTDC_IN'               => _x( 'DTDC India', 'Name of carrier', 'woocommerce-for-japan' ),
				'EKART'                 => _x( 'Ekart', 'Name of carrier', 'woocommerce-for-japan' ),
				'INDIA_POST'            => _x( 'India Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'PROFESSIONAL_COURIERS' => _x( 'Professional Couriers', 'Name of carrier', 'woocommerce-for-japan' ),
				'REDEXPRESS'            => _x( 'Red Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'SWIFTAIR'              => _x( 'Swift Air', 'Name of carrier', 'woocommerce-for-japan' ),
				'XPRESSBEES'            => _x( 'Xpress Bees', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'ID'     => array(
			'name'  => _x( 'Indonesia', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'FIRST_LOGISITCS' => _x( 'First Logistics', 'Name of carrier', 'woocommerce-for-japan' ),
				'JNE_IDN'         => _x( 'JNE Indonesia', 'Name of carrier', 'woocommerce-for-japan' ),
				'LION_PARCEL'     => _x( 'Lion Parcel', 'Name of carrier', 'woocommerce-for-japan' ),
				'NINJAVAN_ID'     => _x( 'Ninjavan Indonesia', 'Name of carrier', 'woocommerce-for-japan' ),
				'PANDU'           => _x( 'Pandu Logistics', 'Name of carrier', 'woocommerce-for-japan' ),
				'POS_ID'          => _x( 'Pos Indonesia Domestic', 'Name of carrier', 'woocommerce-for-japan' ),
				'POS_INT'         => _x( 'Pos Indonesia International', 'Name of carrier', 'woocommerce-for-japan' ),
				'RPX_ID'          => _x( 'RPX Indonesia', 'Name of carrier', 'woocommerce-for-japan' ),
				'RPX'             => _x( 'RPX International', 'Name of carrier', 'woocommerce-for-japan' ),
				'TIKI_ID'         => _x( 'Tiki', 'Name of carrier', 'woocommerce-for-japan' ),
				'WAHANA_ID'       => _x( 'Wahana', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'IE'     => array(
			'name'  => _x( 'Ireland', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'AN_POST'    => _x( 'AN POST Ireland', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD_IR'     => _x( 'DPD Ireland', 'Name of carrier', 'woocommerce-for-japan' ),
				'MASTERLINK' => _x( 'Masterlink', 'Name of carrier', 'woocommerce-for-japan' ),
				'TPG'        => _x( 'TPG', 'Name of carrier', 'woocommerce-for-japan' ),
				'WISELOADS'  => _x( 'Wiseloads', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'IL'     => array(
			'name'  => _x( 'Israel', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ISRAEL_POST' => _x( 'Israel Post', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'IT'     => array(
			'name'  => _x( 'Italy', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'BRT_IT'           => _x( 'BRT Bartolini', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_IT'           => _x( 'DHL Italy', 'Name of carrier', 'woocommerce-for-japan' ),
				'DMM_NETWORK'      => _x( 'DMM Network', 'Name of carrier', 'woocommerce-for-japan' ),
				'FERCAM_IT'        => _x( 'FERCAM Logistics & Transport', 'Name of carrier', 'woocommerce-for-japan' ),
				'GLS_IT'           => _x( 'GLS Italy', 'Name of carrier', 'woocommerce-for-japan' ),
				'HERMES_IT'        => _x( 'Hermes Italy', 'Name of carrier', 'woocommerce-for-japan' ),
				'POSTE_ITALIANE'   => _x( 'Poste Italiane', 'Name of carrier', 'woocommerce-for-japan' ),
				'REGISTER_MAIL_IT' => _x( 'Register Mail IT', 'Name of carrier', 'woocommerce-for-japan' ),
				'SDA_IT'           => _x( 'SDA Italy', 'Name of carrier', 'woocommerce-for-japan' ),
				'SGT_IT'           => _x( 'SGT Corriere Espresso', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_CLICK_IT'     => _x( 'TNT Click Italy', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_IT'           => _x( 'TNT Italy', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'JP'     => array(
			'name'  => _x( 'Japan', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'DHL_JP'     => _x( 'DHL Japan', 'Name of carrier', 'woocommerce-for-japan' ),
				'JP_POST'    => _x( 'JP Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'JAPAN_POST' => _x( 'Japan Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'POCZTEX'    => _x( 'Pocztex', 'Name of carrier', 'woocommerce-for-japan' ),
				'SAGAWA'     => _x( 'Sagawa', 'Name of carrier', 'woocommerce-for-japan' ),
				'SAGAWA_JP'  => _x( 'Sagawa JP', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_JP'     => _x( 'TNT Japan', 'Name of carrier', 'woocommerce-for-japan' ),
				'YAMATO'     => _x( 'Yamato Japan', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'KR'     => array(
			'name'  => _x( 'Korea', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ECARGO'                => _x( 'Ecargo', 'Name of carrier', 'woocommerce-for-japan' ),
				'EPARCEL_KR'            => _x( 'eParcel Korea', 'Name of carrier', 'woocommerce-for-japan' ),
				'KOREA_POST'            => _x( 'Korea Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'KOR_KOREA_POST'        => _x( 'KOR Korea Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'CJ_KR'                 => _x( 'Korea Thai CJ', 'Name of carrier', 'woocommerce-for-japan' ),
				'LOGISTICSWORLDWIDE_KR' => _x( 'Logistics Worldwide Korea', 'Name of carrier', 'woocommerce-for-japan' ),
				'PANTOS'                => _x( 'Pantos', 'Name of carrier', 'woocommerce-for-japan' ),
				'RINCOS'                => _x( 'Rincos', 'Name of carrier', 'woocommerce-for-japan' ),
				'ROCKET_PARCEL'         => _x( 'Rocket Parcel International', 'Name of carrier', 'woocommerce-for-japan' ),
				'SRE_KOREA'             => _x( 'SRE Korea', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'LT'     => array(
			'name'  => _x( 'Lithuania', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'LIETUVOS_LT' => _x( 'Lietuvos Pastas', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'MY'     => array(
			'name'  => _x( 'Malaysia', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'AIRPAK_MY'             => _x( 'Airpak', 'Name of carrier', 'woocommerce-for-japan' ),
				'CITYLINK_MY'           => _x( 'CityLink Malaysia', 'Name of carrier', 'woocommerce-for-japan' ),
				'CJ_MY'                 => _x( 'CJ Malaysia', 'Name of carrier', 'woocommerce-for-japan' ),
				'CJ_INT_MY'             => _x( 'CJ Malaysia International', 'Name of carrier', 'woocommerce-for-japan' ),
				'CUCKOOEXPRESS'         => _x( 'Cuckoo Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'JETSHIP_MY'            => _x( 'Jet Ship Malaysia', 'Name of carrier', 'woocommerce-for-japan' ),
				'KANGAROO_MY'           => _x( 'Kangaroo Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'LOGISTICSWORLDWIDE_MY' => _x( 'Logistics Worldwide Malaysia', 'Name of carrier', 'woocommerce-for-japan' ),
				'MALAYSIA_POST'         => _x( 'Malaysia Post EMS / Pos Laju', 'Name of carrier', 'woocommerce-for-japan' ),
				'NATIONWIDE'            => _x( 'Nationwide', 'Name of carrier', 'woocommerce-for-japan' ),
				'NINJAVAN_MY'           => _x( 'Ninjavan Malaysia', 'Name of carrier', 'woocommerce-for-japan' ),
				'SKYNET_MY'             => _x( 'Skynet Malaysia', 'Name of carrier', 'woocommerce-for-japan' ),
				'TAQBIN_MY'             => _x( 'TA-Q-BIN Parcel Malaysia', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'MX'     => array(
			'name'  => _x( 'Mexico', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'CORREOS_MX' => _x( 'Correos De Mexico', 'Name of carrier', 'woocommerce-for-japan' ),
				'ESTAFETA'   => _x( 'Estafeta', 'Name of carrier', 'woocommerce-for-japan' ),
				'AEROFLASH'  => _x( 'Mexico Aeroflash', 'Name of carrier', 'woocommerce-for-japan' ),
				'REDPACK'    => _x( 'Mexico Redpack', 'Name of carrier', 'woocommerce-for-japan' ),
				'SENDA_MX'   => _x( 'Mexico Senda Express', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'NL'     => array(
			'name'  => _x( 'Netherlands', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'DHL_NL'         => _x( 'DHL Netherlands', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_PARCEL_NL'  => _x( 'DHL Parcel Netherlands', 'Name of carrier', 'woocommerce-for-japan' ),
				'GLS_NL'         => _x( 'GLS Netherlands', 'Name of carrier', 'woocommerce-for-japan' ),
				'KIALA'          => _x( 'Kiala', 'Name of carrier', 'woocommerce-for-japan' ),
				'POSTNL'         => _x( 'PostNL', 'Name of carrier', 'woocommerce-for-japan' ),
				'POSTNL_INT'     => _x( 'PostNl International', 'Name of carrier', 'woocommerce-for-japan' ),
				'POSTNL_INT_3_S' => _x( 'PostNL International 3S', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_NL'         => _x( 'TNT Netherlands', 'Name of carrier', 'woocommerce-for-japan' ),
				'TRANSMISSION'   => _x( 'Transmission Netherlands', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'NZ'     => array(
			'name'  => _x( 'New Zealand', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'COURIER_POST' => _x( 'Courier Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'FASTWAY_NZ'   => _x( 'Fastway New Zealand', 'Name of carrier', 'woocommerce-for-japan' ),
				'NZ_POST'      => _x( 'New Zealand Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'TOLL_IPEC'    => _x( 'Toll IPEC', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'NG'     => array(
			'name'  => _x( 'Nigeria', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'COURIERPLUS' => _x( 'Courier Plus', 'Name of carrier', 'woocommerce-for-japan' ),
				'NIPOST_NG'   => _x( 'NiPost', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'NO'     => array(
			'name'  => _x( 'Norway', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'POSTEN_NORGE' => _x( 'Posten Norge', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'PH'     => array(
			'name'  => _x( 'Philippines', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'TWO_GO'          => _x( '2GO', 'Name of carrier', 'woocommerce-for-japan' ),
				'AIR_21'          => _x( 'Air 21', 'Name of carrier', 'woocommerce-for-japan' ),
				'AIRSPEED'        => _x( 'Airspeed', 'Name of carrier', 'woocommerce-for-japan' ),
				'JAMEXPRESS_PH'   => _x( 'Jam Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'LBC_PH'          => _x( 'LBC Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'NINJAVAN_PH'     => _x( 'Ninjavan Philippines', 'Name of carrier', 'woocommerce-for-japan' ),
				'RAF_PH'          => _x( 'RAF Philippines', 'Name of carrier', 'woocommerce-for-japan' ),
				'XEND_EXPRESS_PH' => _x( 'Xend Express', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'PL'     => array(
			'name'  => _x( 'Poland', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'DHL_PL'            => _x( 'DHL Poland', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD_PL'            => _x( 'DPD Poland', 'Name of carrier', 'woocommerce-for-japan' ),
				'INPOST_PACZKOMATY' => _x( 'InPost Paczkomaty', 'Name of carrier', 'woocommerce-for-japan' ),
				'POCZTA_POLSKA'     => _x( 'Poczta Polska', 'Name of carrier', 'woocommerce-for-japan' ),
				'SIODEMKA'          => _x( 'Siodemka', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_PL'            => _x( 'TNT Poland', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'PT'     => array(
			'name'  => _x( 'Portugal', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ADICIONAL_PT'  => _x( 'Adicional Logistics', 'Name of carrier', 'woocommerce-for-japan' ),
				'CHRONOPOST_PT' => _x( 'Chronopost Portugal', 'Name of carrier', 'woocommerce-for-japan' ),
				'CTT_PT'        => _x( 'Portugal PTT', 'Name of carrier', 'woocommerce-for-japan' ),
				'SEUR_PT'       => _x( 'Portugal Seur', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'RO'     => array(
			'name'  => _x( 'Romania', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'DPD_RO'   => _x( 'DPD Romania', 'Name of carrier', 'woocommerce-for-japan' ),
				'POSTA_RO' => _x( 'Postaromana', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'RU'     => array(
			'name'  => _x( 'Russia', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'DPD_RU'       => _x( 'DPD Russia', 'Name of carrier', 'woocommerce-for-japan' ),
				'RUSSIAN_POST' => _x( 'Russian Post', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'SA'     => array(
			'name'  => _x( 'Saudi Arabia', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'DAWN_WING'       => _x( 'Dawn Wing', 'Name of carrier', 'woocommerce-for-japan' ),
				'RAM'             => _x( 'Ram', 'Name of carrier', 'woocommerce-for-japan' ),
				'THE_COURIER_GUY' => _x( 'The Courier Guy', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'CS'     => array(
			'name'  => _x( 'Serbia', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'POST_SERBIA_CS' => _x( 'Serbia Post', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'SG'     => array(
			'name'  => _x( 'Singapore', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'DHL_SG'        => _x( 'DHL Singapore', 'Name of carrier', 'woocommerce-for-japan' ),
				'JETSHIP_SG'    => _x( 'JetShip Singapore', 'Name of carrier', 'woocommerce-for-japan' ),
				'NINJAVAN_SG'   => _x( 'Ninjavan Singapore', 'Name of carrier', 'woocommerce-for-japan' ),
				'PARCELPOST_SG' => _x( 'Parcel Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'SINGPOST'      => _x( 'Singapore Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'TAQBIN_SG'     => _x( 'TA-Q-BIN Parcel Singapore', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'ZA'     => array(
			'name'  => _x( 'South Africa', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'FASTWAY_ZA' => _x( 'Fastway South Africa', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'ES'     => array(
			'name'  => _x( 'Spain', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ASM_ES'        => _x( 'ASM', 'Name of carrier', 'woocommerce-for-japan' ),
				'CBL_LOGISTICA' => _x( 'CBL Logistics', 'Name of carrier', 'woocommerce-for-japan' ),
				'CORREOS_ES'    => _x( 'Correos De Spain', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_ES	'       => _x( 'DHL Spain', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_PARCEL_ES' => _x( 'DHL Parcel Spain', 'Name of carrier', 'woocommerce-for-japan' ),
				'GLS_ES'        => _x( 'GLS Spain', 'Name of carrier', 'woocommerce-for-japan' ),
				'INT_SEUR'      => _x( 'International Suer', 'Name of carrier', 'woocommerce-for-japan' ),
				'ITIS'          => _x( 'ITIS', 'Name of carrier', 'woocommerce-for-japan' ),
				'NACEX_ES'      => _x( 'Nacex Spain', 'Name of carrier', 'woocommerce-for-japan' ),
				'REDUR_ES'      => _x( 'Redur Spain', 'Name of carrier', 'woocommerce-for-japan' ),
				'SEUR_ES'       => _x( 'Spanish Seur', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_ES'        => _x( 'TNT Spain', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'SE'     => array(
			'name'  => _x( 'Sweden', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'DBSCHENKER_SE'             => _x( 'DB Schenker Sweden', 'Name of carrier', 'woocommerce-for-japan' ),
				'DIRECTLINK_SE'             => _x( 'DirectLink Sweden', 'Name of carrier', 'woocommerce-for-japan' ),
				'POSTNORD_LOGISTICS_GLOBAL' => _x( 'PostNord Logistics', 'Name of carrier', 'woocommerce-for-japan' ),
				'POSTNORD_LOGISTICS_DK'     => _x( 'PostNord Logistics Denmark', 'Name of carrier', 'woocommerce-for-japan' ),
				'POSTNORD_LOGISTICS_SE'     => _x( 'PostNord Logistics Sweden', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'CH'     => array(
			'name'  => _x( 'Switzerland', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'SWISS_POST' => _x( 'Swiss Post', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'TW'     => array(
			'name'  => _x( 'Taiwan', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'CHUNGHWA_POST'  => _x( 'Chunghwa Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'TAIWAN_POST_TW' => _x( 'Taiwan Post', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'TH'     => array(
			'name'  => _x( 'Thailand', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ACOMMMERCE'       => _x( 'Acommerce', 'Name of carrier', 'woocommerce-for-japan' ),
				'ALPHAFAST'        => _x( 'Alphafast', 'Name of carrier', 'woocommerce-for-japan' ),
				'CJ_TH'            => _x( 'CJ Thailand', 'Name of carrier', 'woocommerce-for-japan' ),
				'FASTRACK'         => _x( 'FastTrack Thailand', 'Name of carrier', 'woocommerce-for-japan' ),
				'KERRY_EXPRESS_TH' => _x( 'Kerry Express Thailand', 'Name of carrier', 'woocommerce-for-japan' ),
				'NIM_EXPRESS'      => _x( 'NIM Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'NINJAVAN_THAI'    => _x( 'Ninjavan Thailand', 'Name of carrier', 'woocommerce-for-japan' ),
				'SENDIT'           => _x( 'SendIt', 'Name of carrier', 'woocommerce-for-japan' ),
				'THAILAND_POST'    => _x( 'Thailand Post', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'TR'     => array(
			'name'  => _x( 'Turkey', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'PTT_POST' => _x( 'PTT Posta', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'UA'     => array(
			'name'  => _x( 'Ukraine', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'NOVA_POSHTA'     => _x( 'Nova Poshta', 'Name of carrier', 'woocommerce-for-japan' ),
				'NOVA_POSHTA_INT' => _x( 'Nova Poshta International', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'AE'     => array(
			'name'  => _x( 'United Arab Emirates', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'AXL'         => _x( 'AXL Express & Logistics', 'Name of carrier', 'woocommerce-for-japan' ),
				'CONTINENTAL' => _x( 'Continental', 'Name of carrier', 'woocommerce-for-japan' ),
				'SKYNET_UAE'  => _x( 'Skynet Worldwide Express UAE', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'GB'     => array(
			'name'  => _x( 'United Kingdom', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'AIRBORNE_EXPRESS_UK' => _x( 'Airborne Express UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'AIRSURE'             => _x( 'Airsure', 'Name of carrier', 'woocommerce-for-japan' ),
				'APC_OVERNIGHT'       => _x( 'APC Overnight', 'Name of carrier', 'woocommerce-for-japan' ),
				'ASENDIA_UK'          => _x( 'Asendia UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'COLLECTPLUS'         => _x( 'CollectPlus', 'Name of carrier', 'woocommerce-for-japan' ),
				'DELTEC_UK'           => _x( 'Deltec UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'DHL_UK'              => _x( 'DHL UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD_DELISTRACK'      => _x( 'DPD Delistrack', 'Name of carrier', 'woocommerce-for-japan' ),
				'DPD_UK'              => _x( 'DPD UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'FASTWAY_UK'          => _x( 'Fastway UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'HERMESWORLD_UK'      => _x( 'HermesWorld', 'Name of carrier', 'woocommerce-for-japan' ),
				'INTERLINK'           => _x( 'Interlink Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'MYHERMES'            => _x( 'MyHermes UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'NIGHTLINE_UK'        => _x( 'Nightline UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'PARCELFORCE'         => _x( 'Parcel Force', 'Name of carrier', 'woocommerce-for-japan' ),
				'ROYAL_MAIL'          => _x( 'Royal Mail', 'Name of carrier', 'woocommerce-for-japan' ),
				'RPD_2_MAN'           => _x( 'RPD2man Deliveries', 'Name of carrier', 'woocommerce-for-japan' ),
				'SKYNET_UK'           => _x( 'Skynet Worldwide Express UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'TNT_UK'              => _x( 'TNT UK', 'Name of carrier', 'woocommerce-for-japan' ),
				'UK_MAIL'             => _x( 'UK Mail', 'Name of carrier', 'woocommerce-for-japan' ),
				'YODEL'               => _x( 'Yodel', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'US'     => array(
			'name'  => _x( 'United States', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'ABC_PACKAGE'          => _x( 'ABC Package Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'AIRBORNE_EXPRESS'     => _x( 'Airborne Express', 'Name of carrier', 'woocommerce-for-japan' ),
				'ASENDIA_US'           => _x( 'Asendia USA', 'Name of carrier', 'woocommerce-for-japan' ),
				'CPACKET'              => _x( 'Cpacket', 'Name of carrier', 'woocommerce-for-japan' ),
				'ENSENDA'              => _x( 'Ensenda USA', 'Name of carrier', 'woocommerce-for-japan' ),
				'ESTES'                => _x( 'Estes', 'Name of carrier', 'woocommerce-for-japan' ),
				'FASTWAY_US'           => _x( 'Fastway USA', 'Name of carrier', 'woocommerce-for-japan' ),
				'GLOBEGISTICS'         => _x( 'Globegistics USA', 'Name of carrier', 'woocommerce-for-japan' ),
				'INTERNATIONAL_BRIDGE' => _x( 'International Bridge', 'Name of carrier', 'woocommerce-for-japan' ),
				'ONTRAC'               => _x( 'OnTrac', 'Name of carrier', 'woocommerce-for-japan' ),
				'RL_US'                => _x( 'RL Carriers', 'Name of carrier', 'woocommerce-for-japan' ),
				'RRDONNELLEY'          => _x( 'RR Donnelley', 'Name of carrier', 'woocommerce-for-japan' ),
				'USPS'                 => _x( 'USPS', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
		'VN'     => array(
			'name'  => _x( 'Vietnam', 'Name of carrier country', 'woocommerce-for-japan' ),
			'items' => array(
				'KERRY_EXPRESS_VN' => _x( 'Kerry Express Vietnam', 'Name of carrier', 'woocommerce-for-japan' ),
				'VIETNAM_POST'     => _x( 'Vietnam Post', 'Name of carrier', 'woocommerce-for-japan' ),
				'VNPOST_EMS'       => _x( 'Vietnam Post EMS', 'Name of carrier', 'woocommerce-for-japan' ),
			),
		),
	);
