<?php
/**
 * PeachPay multi language support
 *
 * @package PeachPay
 */

// importing array of translated modal strings
require_once PEACHPAY_ABSPATH . 'core/util/checkout-modal-translations.php';

/**
 * Returns a multi-array of translated terms for all supported languages.
 *
 * @return array translated terms all all languages array.
 */
function peachpay_get_translated_modal_terms_all_languages() {
	return $GLOBALS['PEACHPAY_TRANSLATED_MODAL_TERMS'];
}

/**
 * Returns either an array of translated terms for the selected language or 'null' if the selected language is 'English' or 'detect-from-page'.
 *
 * @param string $language_code wp locale.
 * @return array|null translated terms array
 */
function peachpay_get_translated_modal_terms( $language_code ) {
	$translated_modal_terms = $GLOBALS['PEACHPAY_TRANSLATED_MODAL_TERMS'];

	if ( array_key_exists( $language_code, $translated_modal_terms ) ) {
		return $translated_modal_terms[ $language_code ];
	} else {
		return null;
	}
}

/**
 * Always returns what we use as the key in our translation files.
 *
 * There is a duplicate of this in peachpay.php
 *
 * @param string $language_code_or_locale Raw language locale.
 */
function peachpay_to_our_language_key( $language_code_or_locale ) {
	// This is mostly for places like Germany, for example. Although they may
	// choose three different versions of German in WordPress, we only support
	// one. It can also be used generally.
	switch ( $language_code_or_locale ) {
		case 'cs':
			return 'cs-CZ';
		case 'da':
			return 'da-DK';
		case 'de':
		case 'de-AT':
		case 'de-DE':
		case 'de-CH':
			return 'de-DE';
		case 'en':
			return 'en-US';
		case 'es':
		case 'es-MX':
		case 'es-AR':
		case 'es-CL':
		case 'es-PE':
		case 'es-PR':
		case 'es-GT':
		case 'es-CO':
		case 'es-EC':
		case 'es-VE':
		case 'es-UY':
		case 'es-CR':
			return 'es-ES';
		case 'fr':
		case 'fr-BE':
		case 'fr-CA':
		case 'fr-FR':
			return 'fr-FR';
		case 'hi':
			return 'hi-IN';
		case 'it-IT':
			return 'it';
		case 'ko':
			return 'ko-KR';
		case 'lb':
			return 'lb-LU';
		case 'nl':
		case 'nl-BE':
		case 'nl-NL':
			return 'nl-NL';
		case 'pt':
		case 'pt-AO':
		case 'pt-BR':
		case 'pt-PT-ao90':
		case 'pt-PT':
			return 'pt-PT';
		case 'ro':
			return 'ro-RO';
		case 'ru':
			return 'ru-RU';
		case 'sl':
			return 'sl-SI';
		case 'sv':
			return 'sv-SE';
		default:
			return $language_code_or_locale;
	}
}

/**
 * Gets the translated text linked to the supplied translation key
 *
 * @param  string $text the elements which need to be translated.
 * @return string translation.
 */
function peachpay_get_translated_text( $text ) {
	$page_language = peachpay_to_our_language_key( substr( get_locale(), 0, 2 ) );
	if ( 'button_text' === $text ) {
		$target = BUTTON_TEXT_TRANSLATION;
	} elseif ( 'header_text_checkout_page' === $text ) {
		$target = CHECKOUT_PAGE_HEADER_TEXT_TRANSLATION;
	} elseif ( 'subtext_text_checkout_page' === $text ) {
		$target = CHECKOUT_PAGE_SUBTEXT_TRANSLATION;
	}

	if ( ! peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'language' ) ) {
		return $target['en-US'];
	}

	if ( 'detect-from-page' === peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'language' ) ) {
		if ( ! isset( $target[ $page_language ] ) ) {
			return $target['en-US'];
		}

		return $target[ $page_language ];
	}

	return $target[ peachpay_get_settings_option( 'peachpay_express_checkout_branding', 'language' ) ];
}

define(
	'BUTTON_TEXT_TRANSLATION',
	array(
		'ar'    => 'الخروج السريع',
		'bg-BG' => 'експресно плащане',
		'bs-BA' => 'ekspresno plaćanje',
		'ca'    => 'Pagament exprés',
		'cs-CZ' => 'Expresní pokladna',
		'da-DK' => 'Hurtig betaling',
		'de-DE' => 'Expresskauf',
		'el'    => 'Γρήγορο ταμείο',
		'en-US' => 'Express checkout',
		'es-ES' => 'Chequeo rápido',
		'fr-FR' => 'Acheter maintenant',
		'hi-IN' => 'स्पष्ट नियंत्रण',
		'it'    => 'Cassa rapida',
		'ja'    => 'エクスプレスチェックアウト',
		'ko-KR' => '익스프레스 체크아웃',
		'lb-LU' => 'Express Kees',
		'nl-NL' => 'Snel afrekenen',
		'pt-PT' => 'Checkout expresso',
		'ro-RO' => 'Cumpără cu 1-click',
		'ru-RU' => 'Экспресс-касса',
		'sl-SI' => 'Hitra odjava',
		'sv-SE' => 'snabbkassa',
		'th'    => 'ชำระเงินด่วน',
		'uk'    => 'Експрес -оплата',
		'zh-CN' => '快速结帐',
		'zh-TW' => '快速結帳',
	)
);

define(
	'CHECKOUT_PAGE_HEADER_TEXT_TRANSLATION',
	array(
		'ar'    => 'تحقق مع PeachPay',
		'bg-BG' => 'Проверете с PeachPay',
		'bs-BA' => 'Plaćanje uz PeachPay',
		'ca'    => 'Fes una ullada amb PeachPay',
		'cs-CZ' => 'Podívejte se na PeachPay',
		'da-DK' => 'Tjek ud med PeachPay',
		'de-DE' => 'Check-out mit PeachPay',
		'el'    => 'Ελέγξτε με το PeachPay',
		'en-US' => 'Check out with PeachPay',
		'es-ES' => 'Consulte con PeachPay',
		'fr-FR' => 'Vérifiez avec PeachPay',
		'hi-IN' => 'पीचपे के साथ चेक आउट करें',
		'it'    => 'Controlla con PeachPay',
		'ja'    => 'PeachPayでチェックしてください',
		'ko-KR' => 'PeachPay로 확인하세요',
		'lb-LU' => 'Check aus mat PeachPay',
		'nl-NL' => 'Afrekenen met PeachPay',
		'pt-PT' => 'Confira com PeachPay',
		'ro-RO' => 'Verificați cu PeachPay',
		'ru-RU' => 'Проверить с PeachPay',
		'sl-SI' => 'Preverite pri PeachPay',
		'sv-SE' => 'Kolla in med PeachPay',
		'th'    => 'ชำระเงินด้วย PeachPay',
		'uk'    => 'Перевірте через PeachPay',
		'zh-CN' => '使用 PeachPay 结账',
		'zh-TW' => '使用 PeachPay 結賬',
	)
);

define(
	'CHECKOUT_PAGE_SUBTEXT_TRANSLATION',
	array(
		'ar'    => 'في المرة التالية التي تعود فيها ، سيكون لديك تسجيل الخروج بنقرة واحدة ولن تضطر إلى إضاعة الوقت في ملء الحقول أدناه.',
		'bg-BG' => 'Следващият път, когато се върнете, ще имате плащане с едно щракване и няма да се налага да губите време за попълване на полетата по-долу.',
		'bs-BA' => 'Sljedeći put kada se vratite, imat ćete naplatu jednim klikom i nećete morati gubiti vrijeme ispunjavajući polja ispod.',
		'ca'    => 'La propera vegada que tornis, tindreu la compra amb un sol clic i no haureu de perdre temps omplint els camps següents.',
		'cs-CZ' => 'Až se příště vrátíte, budete mít pokladnu na jedno kliknutí a nebudete muset ztrácet čas vyplňováním níže uvedených polí.',
		'da-DK' => 'Næste gang du kommer tilbage, har du et-klik til betaling og behøver ikke spilde tid på at udfylde felterne nedenfor.',
		'de-DE' => 'Wenn Sie das nächste Mal wiederkommen, können Sie mit einem Klick zur Kasse gehen und müssen keine Zeit damit verschwenden, die Felder unten auszufüllen.',
		'el'    => 'Την επόμενη φορά που θα επιστρέψετε, θα έχετε ταμείο με ένα κλικ και δεν θα χρειαστεί να χάσετε χρόνο συμπληρώνοντας τα παρακάτω πεδία.',
		'en-US' => 'The next time you come back, you’ll have one-click checkout and won’t have to waste time filling out the fields below.',
		'es-ES' => 'Ocurrió un error al iniciar sesión para su pedido de suscripción. Asegúrate de que tu contraseña sea correcta.',
		'fr-FR' => 'La prochaine fois que vous reviendrez, vous passerez à la caisse en un clic et vous n\'aurez pas à perdre de temps à remplir les champs ci-dessous.',
		'hi-IN' => 'अगली बार जब आप वापस आएंगे, तो आपके पास एक-क्लिक चेकआउट होगा और आपको नीचे दिए गए फ़ील्ड भरने में समय बर्बाद नहीं करना पड़ेगा।',
		'it'    => 'La prossima volta che torni, avrai il checkout con un clic e non dovrai perdere tempo a compilare i campi sottostanti.',
		'ja'    => '次回戻ってきたときに、ワンクリックでチェックアウトできるので、下のフィールドに入力する時間を無駄にする必要はありません。',
		'ko-KR' => '다음에 돌아올 때 클릭 한 번으로 결제할 수 있으며 아래 필드를 채우는 데 시간을 낭비할 필요가 없습니다.',
		'lb-LU' => 'Déi nächst Kéier wann Dir zréck kommt, hutt Dir e Klick a musst keng Zäit verschwenden andeems Dir d\'Felder hei drënner ausfëllt.',
		'nl-NL' => 'De volgende keer dat u terugkomt, kunt u met één klik afrekenen en hoeft u geen tijd te verspillen aan het invullen van de onderstaande velden.',
		'pt-PT' => 'Na próxima vez que você voltar, terá uma finalização de compra com um clique e não terá que perder tempo preenchendo os campos abaixo.',
		'ro-RO' => 'Data viitoare când vă întoarceți, veți avea un singur clic de finalizare și nu va trebui să pierdeți timpul completând câmpurile de mai jos.',
		'ru-RU' => 'Произошла ошибка при входе в систему для заказа подписки. Пожалуйста, убедитесь, что ваш пароль правильный.',
		'sl-SI' => 'Naslednjič, ko se boste vrnili, boste imeli plačilo z enim klikom in vam ne bo treba izgubljati časa z izpolnjevanjem spodnjih polj.',
		'sv-SE' => 'Nästa gång du kommer tillbaka har du ett klick till kassan och behöver inte slösa tid på att fylla i fälten nedan.',
		'th'    => 'ครั้งต่อไปที่คุณกลับมา คุณจะชำระเงินด้วยคลิกเดียวและไม่ต้องเสียเวลากรอกข้อมูลในฟิลด์ด้านล่าง',
		'uk'    => 'Наступного разу, коли ви повернетеся, ви зможете оплатити в один клік, і вам не доведеться витрачати час на заповнення полів нижче.',
		'zh-CN' => '下次回来时，您将可以一键结帐，而不必浪费时间填写下面的信息。',
		'zh-TW' => '下次回來時，您將可以一鍵結帳，而不必浪費時間填寫下面的信息。',
	)
);
