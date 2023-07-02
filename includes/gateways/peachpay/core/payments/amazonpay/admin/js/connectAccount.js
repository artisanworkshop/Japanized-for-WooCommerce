
window.addEventListener('DOMContentLoaded', () => {
	document.querySelector('#amazon-pay-connect-button')?.addEventListener('click', () => {
		const refreshButton = document.querySelector('#amazon-pay-refresh-button');
		if (!refreshButton) {
			return;
		}

		refreshButton.style.display = 'initial';
	});

	document.querySelector('#amazon-pay-refresh-button')?.addEventListener('click', () => {
		location.reload();
	});
});
