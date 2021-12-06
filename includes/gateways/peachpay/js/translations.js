function getLanguage() {
	if (!php_data || !php_data.language) {
		return 'en-US';
	}

	let language = php_data.language;

	if (php_data.language === 'detect-from-page') {
		language = languageCodeToLocale(getPageLanguage())
		const englishVariants = new Set(['en-AU', 'en-CA', 'en-GB', 'en-NZ', 'en-ZA']);
		if (englishVariants.has(language)) {
			language = 'en-US';
		}
	}

	return language;
}

// The below section is for translating our checkout window when the parent
// page changes language. For example, on the site there may be a Google
// Translate widget which allows language selection. Since the checkout
// window is in an iframe, the widget will not change its text. We have to
// listen for language changes on the page and inform the checkout window to
// update which language text is shown.

document.addEventListener('DOMContentLoaded', peachpayListenForPageTranslate);

function peachpayListenForPageTranslate() {
	const $helper = document.querySelector('.peachpay-translate-helper');
	if ($helper) {
		let previous = document.querySelector('html').lang;
		$helper.addEventListener('DOMSubtreeModified', () => {
			const current = document.querySelector('html').lang;
			if (previous !== current) {
				previous = current;
				sendPageLanguageChangeMessage(current);
			}
		});
	} else {
		window.requestAnimationFrame(peachpayListenForPageTranslate);
	}
}

function sendPageLanguageChangeMessage(language) {
	const peachpay = document.querySelector('#peachpay-iframe');
	if (!peachpay) {
		return;
	}
	peachpay.contentWindow.postMessage({
		event: 'pageLanguageChange',
		language: languageCodeToLocale(language),
	}, '*');
}

/**
 * Given a ISO 639-1 language code, get the locale that PeachPay supports for
 * that language. If there is no match, it will return the same language code.
 * This is used to turn the html lang attribute into a format that we can use
 * to decide which translation to use.
 *
 * It does not indicate whether or not we support that language in PeachPay.
 *
 * @param {string} languageCode As per ISO 639-1.
 * @returns Locale code that has country and langauge information, roughly the
 * one that WordPress uses. For example: en-US
 */
function languageCodeToLocale(languageCode) {
	switch (languageCode) {
		case 'cs':
			return 'cs-CZ';
		case 'da':
			return 'da-DK';
		case 'de':
			return 'de-DE';
		case 'en':
			return 'en-US';
		case 'es':
			return 'es-ES';
		case 'hi':
			return 'hi-IN';
		case 'ko':
			return 'ko-KR';
		case 'lb':
			return 'lb-LU';
		case 'nl':
			return 'nl-NL';
		case 'pt':
			return 'pt-PT';
		case 'ru':
			return 'ru-RU';
		case 'sl':
			return 'sl-SI';
		case 'sv':
			return 'sv-SE';
		case 'auto':
			return navigator.language || navigator.userLanguage;
		default:
			return languageCode;
	}
}

window.pp_i18n = {
	'button': {
		"ar": "شراء بنقرة واحدة",
		"ca": "Comprar amb un sol clic",
		"cs-CZ": "Nakupujte jedním kliknutím",
		"da-DK": "Køb med et klik",
		'de-DE': 'Mit mit einem Klick kaufen',
		"el": "Αγορά με ένα κλικ",
		'en-US': 'Buy with one click',
		'es-ES': 'Comprar con un solo clic',
		'fr': 'Achetez en un clic',
		"hi-IN": "एक क्लिक से खरीदें",
		'it': 'Acquista con un clic',
		'ja': 'すぐに購入',
		"ko-KR": "클릭 한 번으로 구매",
		"lb-LU": "Kaaft mat engem Klick",
		"nl-NL": "Koop met één klik",
		"pt-PT": "Compre com um clique",
		'ro-RO': 'Cumpărați cu un singur clic',
		"ru-RU": "Купить в один клик",
		"sl-SI": "Kupujte z enim klikom",
		"sv-SE": "Köp med ett klick",
		"th": "ซื้อได้ด้วยคลิกเดียว",
		"uk": "Купуйте в один клік",
		"zh-CN": "一键购买",
		"zh-TW": "一鍵購買",
	},
	'express-checkout': {
		"ar": "الخروج السريع",
		"ca": "Pagament exprés",
		"cs-CZ": "Expresní pokladna",
		"da-DK": "Hurtig betaling",
		'de-DE': 'Expresskauf',
		"el": "Γρήγορο ταμείο",
		'en-US': 'Express checkout',
		'es-ES': 'Chequeo rápido',
		'fr': 'Acheter maintenant',
		"hi-IN": "स्पष्ट नियंत्रण",
		'it': 'Cassa rapida',
		'ja': 'エクスプレスチェックアウト',
		"ko-KR": "익스프레스 체크아웃",
		"lb-LU": "Express Kees",
		"nl-NL": "Snel afrekenen",
		"pt-PT": "Checkout expresso",
		'ro-RO': 'Cumpără cu 1-click',
		"ru-RU": "Экспресс-касса",
		"sl-SI": "Hitra odjava",
		"sv-SE": "snabbkassa",
		"th": "ชำระเงินด่วน",
		"uk": "Експрес -оплата",
		"zh-CN": "快速结帐",
		"zh-TW": "快速結帳",
	},
	'out-of-stock': {
		"ar": "المعذرة، هذه المنتج غير متوفر في المخزن.",
		"ca": "Ho sentim, aquest producte està esgotat.",
		"cs-CZ": "Omlouváme se, tento produkt není na skladě.",
		"da-DK": "Dette produkt er desværre ikke på lager.",
		'de-DE': 'Dieses Produkt ist leider nicht verfügbar.',
		"el": "Λυπούμαστε, αυτό το προϊόν είναι εκτός αποθέματος.",
		'en-US': 'Sorry, this product is out of stock.',
		'es-ES': 'Lo sentimos, este producto no está disponible.',
		'fr': 'Désolé, ce produit n\'est pas disponible.',
		"hi-IN": "माफ़ कीजिये, यह माल स्टॉक में नहीं है।",
		'it': 'Spiacenti, questo prodotto non è disponibile.',
		'ja': 'この商品では利用できません。',
		"ko-KR": "죄송합니다. 이 제품은 품절입니다.",
		"lb-LU": "Entschëllegt, dëst Produkt ass net op Lager.",
		"nl-NL": "Sorry, dit product is niet op voorraad.",
		"pt-PT": "Desculpe, este produto está fora de estoque.",
		'ro-RO': 'Ne pare rău, acest produs nu este disponibil.',
		"ru-RU": "Извините, этого товара нет в наличии.",
		"sl-SI": "Žal tega izdelka ni na zalogi.",
		"sv-SE": "Den här produkten är slut i lager.",
		"th": "ขออภัย สินค้าหมด",
		"uk": "На жаль, цього товару немає на складі.",
		"zh-CN": "抱歉，该产品缺货。",
		"zh-TW": "抱歉，該產品缺貨。",
	},
	'select-variation': {
		"ar": "الرجاء تحديد خيارات المنتج المتبقية قبل إضافة هذا المنتج إلى سلة التسوق الخاصة بك.",
		"ca": "Seleccioneu la resta d’opcions de producte abans d’afegir aquest producte al carretó.",
		"cs-CZ": "Před přidáním tohoto produktu do košíku vyberte zbývající možnosti produktu.",
		"da-DK": "Vælg de resterende produktmuligheder, før du tilføjer dette produkt til din indkøbsvogn.",
		'de-DE': 'Bitte wählen Sie die verbleibenden Produktoptionen aus, bevor Sie dieses Produkt in Ihren Warenkorb legen.',
		"el": "Επιλέξτε τις υπόλοιπες επιλογές προϊόντος πριν προσθέσετε αυτό το προϊόν στο καλάθι σας.",
		'en-US': 'Please select the remaining product options before adding this product to your cart.',
		'es-ES': 'Seleccione las opciones de producto restantes antes de agregar este producto a su carrito.',
		'fr': 'Veuillez sélectionner les options de produit restantes avant d\'ajouter ce produit à votre panier.',
		"hi-IN": "इस उत्पाद को अपनी कार्ट में जोड़ने से पहले कृपया शेष उत्पाद विकल्पों का चयन करें।",
		'it': 'Seleziona le restanti opzioni di prodotto prima di aggiungere questo prodotto al carrello.',
		'ja': 'この商品をお買い物カゴに追加する前に、残りの商品オプションを選択してください。',
		"ko-KR": "이 제품을 장바구니에 추가하기 전에 나머지 제품 옵션을 선택하십시오.",
		"lb-LU": "Wielt w.e.g. déi verbleiwen Produktoptiounen ier Dir dëst Produkt an Äre Weenchen bäidréit.",
		"nl-NL": "Selecteer de resterende productopties voordat u dit product aan uw winkelwagentje toevoegt.",
		"pt-PT": "Selecione as opções de produto restantes antes de adicionar este produto ao seu carrinho.",
		'ro-RO': 'Vă rugăm să selectați opțiunile de produs rămase înainte de a adăuga acest produs în coș.',
		"ru-RU": "Пожалуйста, выберите оставшиеся варианты продукта, прежде чем добавить этот продукт в корзину.",
		"sl-SI": "Preden izdelek dodate v košarico, izberite preostale možnosti izdelka.",
		"sv-SE": "Välj de återstående produktalternativen innan du lägger till produkten i din kundvagn.",
		"th": "โปรดเลือกตัวเลือกสินค้าที่เหลือก่อนที่จะเพิ่มสินค้านี้ลงในรถเข็นของคุณ",
		"uk": "Будь ласка, виберіть інші варіанти продуктів, перш ніж додавати цей товар у кошик.",
		"zh-CN": "在将此产品添加到您的购物车之前，请选择剩余的产品选项。",
		"zh-TW": "在將此產品添加到您的購物車之前，請選擇剩餘的產品選項。",
	},
	'out-of-stock-bundle': {
		"ar": "عذرًا ، منتج واحد أو أكثر في هذه الحزمة غير متوفر. الرجاء اختيار مجموعة مختلفة.",
		"ca": "Ho sentim, un o més productes d’aquest paquet no estan disponibles. Trieu una combinació diferent.",
		"cs-CZ": "Litujeme, jeden nebo více produktů v tomto balíčku není k dispozici. Vyberte prosím jinou kombinaci.",
		"da-DK": "Et eller flere produkter i denne pakke er desværre ikke tilgængelige. Vælg en anden kombination.",
		'de-DE': 'Leider sind ein oder mehrere Produkte in diesem Bundle nicht verfügbar. Bitte wählen Sie eine andere Kombination.',
		"el": "Δυστυχώς, ένα ή περισσότερα προϊόντα σε αυτό το πακέτο δεν είναι διαθέσιμα. Επιλέξτε διαφορετικό συνδυασμό.",
		'en-US': 'Sorry, one or more products in this bundle is unavailable. Please choose a different combination.',
		'es-ES': 'Lo sentimos, uno o más productos de este paquete no están disponibles. Elija una combinación diferente.',
		'fr': 'Désolé, un ou plusieurs produits de ce lot ne sont pas disponibles. Veuillez choisir une combinaison différente.',
		"hi-IN": "क्षमा करें, इस बंडल में एक या अधिक उत्पाद अनुपलब्ध हैं। कृपया कोई भिन्न संयोजन चुनें.",
		'it': 'Spiacenti, uno o più prodotti in questo pacchetto non sono disponibili. Scegli una combinazione diversa.',
		'ja': '申し訳ありませんが、このバンドルの1つ以上の商品をご利用いただけません。別の組み合わせを選択してください。',
		"ko-KR": "죄송합니다. 이 번들에 있는 하나 이상의 제품을 사용할 수 없습니다. 다른 조합을 선택하세요.",
		"lb-LU": "Entschëllegt, een oder méi Produkter an dësem Package ass net verfügbar. Wielt w.e.g. eng aner Kombinatioun.",
		"nl-NL": "Sorry, een of meer producten in deze bundel zijn niet beschikbaar. Kies een andere combinatie.",
		"pt-PT": "Desculpe, um ou mais produtos neste pacote não estão disponíveis. Escolha uma combinação diferente.",
		'ro-RO': 'Ne pare rău, unul sau mai multe produse din acest pachet nu sunt disponibile. Vă rugăm să alegeți o altă combinație.',
		"ru-RU": "К сожалению, один или несколько продуктов из этого набора недоступны. Выберите другую комбинацию.",
		"sl-SI": "Žal en ali več izdelkov v tem svežnju ni na voljo. Izberite drugo kombinacijo.",
		"sv-SE": "Tyvärr är en eller flera produkter i detta paket inte tillgängligt. Välj en annan kombination.",
		"th": "ขออภัย ผลิตภัณฑ์อย่างน้อยหนึ่งรายการในกลุ่มนี้ไม่พร้อมใช้งาน โปรดเลือกชุดค่าผสมอื่น",
		"uk": "На жаль, один або кілька продуктів у цьому наборі недоступні. Виберіть іншу комбінацію.",
		"zh-CN": "抱歉，此捆绑包中的一种或多种产品不可用。 请选择不同的组合。",
		"zh-TW": "抱歉，此捆綁包中的一種或多種產品不可用。 請選擇不同的組合。",
	},
	'select-variation-bundle': {
		"ar": "الرجاء تحديد خيارات المنتج المتبقية قبل إضافة هذه الحزمة إلى سلة التسوق الخاصة بك.",
		"ca": "Seleccioneu les opcions de producte restants abans d'afegir aquest paquet al carretó.",
		"cs-CZ": "Před přidáním tohoto balíčku do košíku vyberte zbývající možnosti produktu.",
		"da-DK": "Vælg de resterende produktmuligheder, før du tilføjer denne pakke til din indkøbsvogn.",
		'de-DE': 'Bitte wählen Sie die verbleibenden Produktoptionen aus, bevor Sie dieses Paket in Ihren Warenkorb legen.',
		"el": "Επιλέξτε τις υπόλοιπες επιλογές προϊόντος πριν προσθέσετε αυτό το πακέτο στο καλάθι σας.",
		'en-US': 'Please select the remaining product options before adding this bundle to your cart.',
		'es-ES': 'Seleccione las opciones de producto restantes antes de agregar este paquete a su carrito.',
		'fr': 'Veuillez sélectionner les options de produit restantes avant d\'ajouter ce lot à votre panier.',
		"hi-IN": "इस बंडल को अपनी कार्ट में जोड़ने से पहले कृपया शेष उत्पाद विकल्पों का चयन करें।",
		'it': 'Seleziona le restanti opzioni di prodotto prima di aggiungere questo pacchetto al carrello.',
		'ja': 'このバンドルをカートに追加する前に、残りの商品オプションを選択してください。',
		"ko-KR": "이 번들을 장바구니에 추가하기 전에 나머지 제품 옵션을 선택하십시오.",
		"lb-LU": "Wielt w.e.g. déi verbleiwen Produktoptiounen ier Dir dëse Package an Äre Weenchen bäidréit.",
		"nl-NL": "Selecteer de resterende productopties voordat u deze bundel aan uw winkelwagentje toevoegt.",
		"pt-PT": "Selecione as opções de produto restantes antes de adicionar este pacote ao seu carrinho.",
		'ro-RO': 'Vă rugăm să selectați opțiunile de produs rămase înainte de a adăuga acest pachet în coș.',
		"ru-RU": "Пожалуйста, выберите оставшиеся варианты продукта, прежде чем добавлять этот комплект в корзину.",
		"sl-SI": "Preden dodate ta sveženj v košarico, izberite preostale možnosti izdelka.",
		"sv-SE": "Välj de återstående produktalternativen innan du lägger till detta paket i din kundvagn.",
		"th": "โปรดเลือกตัวเลือกผลิตภัณฑ์ที่เหลือก่อนที่จะเพิ่มชุดรวมนี้ลงในรถเข็นของคุณ",
		"uk": "Будь ласка, виберіть інші варіанти продуктів, перш ніж додати цей комплект до кошика.",
		"zh-CN": "在将此捆绑包添加到您的购物车之前，请选择剩余的产品选项。",
		"zh-TW": "在將此捆綁包添加到您的購物車之前，請選擇剩餘的產品選項。",
	},
	'gift-card-zero-balance': {
		"ar": "بطاقة الهدية هذه لا تحتوي على أموال متبقية.",
		"ca": "A aquesta targeta regal no li queden diners.",
		"cs-CZ": "Na této dárkové kartě nezbývají peníze.",
		"da-DK": "Dette gavekort har ingen penge tilbage.",
		'de-DE': 'Diese Geschenkkarte hat kein Geld mehr.',
		"el": "Αυτή η δωροκάρτα δεν έχει άλλα χρήματα.",
		'en-US': 'This gift card has no money left.',
		'es-ES': 'A esta tarjeta de regalo no le queda dinero.',
		'fr': 'Cette carte-cadeau n\'a plus d\'argent.',
		"hi-IN": "इस गिफ्ट कार्ड में कोई पैसा नहीं बचा है।",
		'it': 'Questa carta regalo non ha più soldi.',
		'ja': 'このギフトカードにはお金が残っていません。',
		"ko-KR": "이 기프트 카드에 잔액이 없습니다.",
		"lb-LU": "Dës Kaddokaart huet keng Suen méi.",
		"nl-NL": "Deze cadeaukaart heeft geen geld meer.",
		"pt-PT": "Este vale-presente não tem mais dinheiro.",
		'ro-RO': 'Acest card cadou nu mai are bani.',
		"ru-RU": "На этой подарочной карте не осталось денег.",
		"sl-SI": "Na tej darilni kartici ni denarja.",
		"sv-SE": "Detta presentkort har inga pengar kvar.",
		"th": "บัตรของขวัญนี้ไม่มีเงินเหลือ",
		"uk": "На цій подарунковій картці не залишилося грошей.",
		"zh-CN": "这张礼品卡已经没有钱了。",
		"zh-TW": "這張禮品卡已經沒有錢了。",
	},
	'order-failure-after-payment': {
		"ar": "حدث خطأ ما ، لكن لا تقلق. لدينا تفاصيل طلبك ، وقد تم سداد دفعتك. ليست هناك حاجة لتقديم طلب آخر.",
		"ca": "S'ha produït un error, però no us preocupeu. Tenim les dades de la vostra comanda i s’ha efectuat el pagament. No cal fer una altra comanda.",
		"cs-CZ": "Něco se pokazilo, ale nebojte se. Máme podrobnosti o vaší objednávce a vaše platba byla provedena. Není třeba zadávat další objednávku.",
		"da-DK": "Noget gik galt, men bare rolig. Vi har dine ordreoplysninger, og din betaling er foretaget. Det er ikke nødvendigt at afgive en anden ordre.",
		'de-DE': 'Etwas ist schief gelaufen, aber keine Sorge. Wir haben Ihre Bestelldaten und Ihre Zahlung ist erfolgt. Eine weitere Bestellung ist nicht erforderlich.',
		"el": "Κάτι πήγε στραβά, αλλά μην ανησυχείτε. Έχουμε τα στοιχεία της παραγγελίας σας και η πληρωμή σας έχει πραγματοποιηθεί. Δεν χρειάζεται να κάνετε άλλη παραγγελία.",
		'en-US': 'Something went wrong, but don\'t worry. We have your order details, and your payment has been made. There is no need to place another order.',
		'es-ES': 'Algo salió mal, pero no se preocupe. Tenemos los detalles de su pedido y su pago se ha realizado. No es necesario realizar otro pedido.',
		'fr': 'Quelque chose s\'est mal passé, mais ne vous inquiétez pas. Nous avons les détails de votre commande et votre paiement a été effectué. Il n\'est pas nécessaire de passer une autre commande.',
		"hi-IN": "कुछ गलत हो गया, लेकिन चिंता न करें। हमारे पास आपके आदेश का विवरण है, और आपका भुगतान कर दिया गया है। दूसरा आदेश देने की कोई आवश्यकता नहीं है।",
		'it': 'Qualcosa è andato storto, ma non preoccuparti. Abbiamo i dettagli del tuo ordine e il pagamento è stato effettuato. Non è necessario effettuare un altro ordine.',
		'ja': '何か問題が発生しましたが、心配いりません。ご注文の詳細があり、お支払いが完了しました。再度注文をする必要はありません。',
		"ko-KR": "문제가 발생했지만 걱정하지 마세요. 주문 세부정보가 있으며 결제가 완료되었습니다. 다른 주문을 할 필요가 없습니다.",
		"lb-LU": "Eppes ass falsch gaang, awer maach der keng Suergen. Mir hunn Är Bestellungsdetailer, an Är Bezuelung gouf gemaach. Et ass net néideg eng aner Bestellung ze maachen.",
		"nl-NL": "Er is iets misgegaan, maar maak je geen zorgen. We hebben uw bestelgegevens en uw betaling is gedaan. Het is niet nodig om nog een bestelling te plaatsen.",
		"pt-PT": "Algo deu errado, mas não se preocupe. Temos os detalhes do seu pedido e seu pagamento foi efetuado. Não há necessidade de fazer outro pedido.",
		'ro-RO': 'Ceva a mers prost, dar nu vă faceți griji. Avem detaliile comenzii dvs. și plata dvs. a fost efectuată. Nu este nevoie să plasați o altă comandă.',
		"ru-RU": "Что-то пошло не так, но не волнуйтесь. У нас есть данные о вашем заказе, и ваш платеж был произведен. Очередной заказ делать не нужно.",
		"sl-SI": "Nekaj je šlo narobe, vendar ne skrbite. Podatke o naročilu imamo in plačilo je bilo opravljeno. Drugega naročila ni treba oddati.",
		"sv-SE": "Något gick fel, men oroa dig inte. Vi har dina beställningsuppgifter och din betalning har gjorts. Det finns ingen anledning att göra en annan beställning.",
		"th": "มีบางอย่างผิดพลาด แต่ไม่ต้องกังวล เรามีรายละเอียดการสั่งซื้อของคุณและชำระเงินเรียบร้อยแล้ว ไม่จำเป็นต้องทำการสั่งซื้ออื่น",
		"uk": "Щось пішло не так, але не хвилюйтесь. У нас є дані вашого замовлення, і ваш платіж здійснено. Немає необхідності робити інше замовлення.",
		"zh-CN": "出了点问题，但别担心。 我们有您的订单详细信息，您的付款已完成。 无需再下订单。",
		"zh-TW": "出了點問題，但別擔心。 我們有您的訂單詳細信息，您的付款已完成。 無需再下訂單。",
	},
	'place-order-failure': {
		"ar": "عذرا، هناك خطأ ما. يرجى تحديث الصفحة وحاول مرة أخرى.",
		"ca": "Ho sentim, alguna cosa ha anat malament. Actualitzeu la pàgina i torneu-ho a provar.",
		"cs-CZ": "Promiň, něco se pokazilo. Obnovte stránku a zkuste to znovu.",
		"da-DK": "Undskyld, noget gik galt. Opdater siden, og prøv igen.",
		'de-DE': 'Entschuldigung, etwas ist schief gelaufen. Bitte aktualisieren Sie die Seite und versuchen Sie es erneut.',
		"el": "Συγνώμη, κάτι πήγε στραβά. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.",
		'en-US': 'Sorry, something went wrong. Please refresh the page and try again.',
		'es-ES': 'Perdón, algo salió mal. Actualice la página y vuelva a intentarlo.',
		'fr': 'Désolé, quelque chose s\'est mal passé. Veuillez actualiser la page et réessayer.',
		"hi-IN": "क्षमा करें, कुछ गलत हो गया। पृष्ठ को रीफ्रेश करें और पुन: प्रयास करें।",
		'it': 'Scusa, qualcosa è andato storto. Perfavore ricarica la pagina e riprova.',
		'ja': '問題が発生しました。ページを更新して、もう一度お試しください。',
		"ko-KR": "죄송합니다. 문제가 발생했습니다. 페이지를 새로고침하고 다시 시도하십시오.",
		"lb-LU": "Entschëllegt, eppes ass falsch gaang. Erfrëscht w.e.g. d'Säit a probéiert nach eng Kéier.",
		"nl-NL": "Sorry, er ging iets mis. Ververs de pagina en probeer het opnieuw.",
		"pt-PT": "Desculpe, algo deu errado. Atualize a página e tente novamente.",
		'ro-RO': 'Scuze, ceva a mers greșit. Actualizați pagina și încercați din nou.',
		"ru-RU": "Извините, что-то пошло не так. Обновите страницу и попробуйте еще раз.",
		"sl-SI": "Oprostite, nekaj je šlo narobe. Osvežite stran in poskusite znova.",
		"sv-SE": "Förlåt, något gick fel. Uppdatera sidan och försök igen.",
		"th": "ขอโทษมีบางอย่างผิดพลาด. โปรดรีเฟรชหน้าแล้วลองอีกครั้ง",
		"uk": "Вибачте, щось пішло не так. Оновіть сторінку та повторіть спробу.",
		"zh-CN": "抱歉，出了一些问题。 请刷新页面并重试。",
		"zh-TW": "抱歉，出了一些問題。 請刷新頁面並重試。",
	},
	'login-on-order-failed': {
		"ar": "حدث خطأ أثناء تسجيل الدخول لطلب الاشتراك الخاص بك. يرجى التأكد من صحة كلمة المرور الخاصة بك.",
		"ca": "S'ha produït un error en iniciar la sessió per a la vostra comanda de subscripció. Assegureu-vos que la vostra contrasenya sigui correcta.",
		"cs-CZ": "Při přihlašování k vaší objednávce předplatného došlo k chybě. Ujistěte se prosím, že je vaše heslo správné.",
		"da-DK": "Der opstod en fejl under login til din abonnementsordre. Sørg for, at din adgangskode er korrekt.",
		'de-DE': 'Beim Anmelden für Ihre Abonnementbestellung ist ein Fehler aufgetreten. Bitte stellen Sie sicher, dass Ihr Passwort korrekt ist.',
		"el": "Παρουσιάστηκε σφάλμα κατά τη σύνδεση για την παραγγελία συνδρομής σας. Βεβαιωθείτε ότι ο κωδικός πρόσβασης είναι σωστός.",
		'en-US': 'An error occurred while logging in for your subscription order. Please make sure your password is correct.',
		'es-ES': 'Ocurrió un error al iniciar sesión para su pedido de suscripción. Asegúrate de que tu contraseña sea correcta.',
		'fr': 'Une erreur s\'est produite lors de la connexion pour votre commande d\'abonnement. Veuillez vous assurer que votre mot de passe est correct.',
		"hi-IN": "आपके सब्सक्रिप्शन ऑर्डर के लिए लॉग इन करते समय एक त्रुटि हुई। कृपया सुनिश्चित करें कि आपका पासवर्ड सही है।",
		'it': 'Si è verificato un errore durante l\'accesso al tuo ordine di abbonamento. Assicurati che la tua password sia corretta.',
		'ja': '定期購入注文へのログイン中にエラーが発生しました。パスワードが正しいことを確認してください。',
		"ko-KR": "구독 주문을 위해 로그인하는 동안 오류가 발생했습니다. 비밀번호가 정확한지 확인하세요.",
		"lb-LU": "E Feeler ass geschitt beim Login fir Är Abonnementsuerdnung. Gitt sécher datt Äert Passwuert richteg ass.",
		"nl-NL": "Er is een fout opgetreden tijdens het inloggen voor uw abonnementsbestelling. Zorg ervoor dat uw wachtwoord correct is.",
		"pt-PT": "Ocorreu um erro ao fazer login para seu pedido de assinatura. Certifique-se de que sua senha está correta.",
		'ro-RO': 'A apărut o eroare la conectarea la comanda dvs. de abonament. Vă rugăm să vă asigurați că parola este corectă.',
		"ru-RU": "Произошла ошибка при входе в систему для заказа подписки. Пожалуйста, убедитесь, что ваш пароль правильный.",
		"sl-SI": "Pri prijavi v naročniško naročilo je prišlo do napake. Preverite, ali je geslo pravilno.",
		"sv-SE": "Ett fel uppstod när du loggade in för din prenumerationsorder. Se till att ditt lösenord är korrekt.",
		"th": "เกิดข้อผิดพลาดขณะเข้าสู่ระบบคำสั่งซื้อของคุณ โปรดตรวจสอบให้แน่ใจว่ารหัสผ่านของคุณถูกต้อง",
		"uk": "Під час входу до вашого замовлення на підписку сталася помилка. Переконайтеся, що ваш пароль правильний.",
		"zh-CN": "登录订阅订单时出错。 请确保您的密码正确。",
		"zh-TW": "登錄訂閱訂單時出錯。 請確保您的密碼正確。",
	},
}
