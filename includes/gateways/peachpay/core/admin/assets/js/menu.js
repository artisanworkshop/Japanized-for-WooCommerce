const tabNames = {
	general: 'General',
	payment: 'Payment Methods',
	button: 'Button Preferences',
	field: 'Field Editor',
	related_products: 'Related Products',
};

function highlightCurrentSubmenu() {
	const parameters = new URLSearchParams(window.location.search);
	const currentTab = parameters.get('tab') || 'general';
	const menuItems = document.querySelectorAll('#toplevel_page_peachpay li');
	for (const item of menuItems) {
		item.classList.remove('current');
		if (item.textContent.includes(tabNames[currentTab])) {
			item.classList.add('current');
		}
	}
}

document.addEventListener('DOMContentLoaded', highlightCurrentSubmenu);
