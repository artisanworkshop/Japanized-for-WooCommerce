
async function connectAmazonPayAccount() {
	document.querySelector('#amazon-pay-connect-button').style.display = "none";
	document.querySelector('#amazon-pay-connect-loading').style.display = "initial";

	var onboardingComplete = false
	var onboardingStatus = undefined;
	while (!onboardingComplete) {
		await new Promise(r => setTimeout(r, 5000));
		const statusUpdate = await fetch(pp_amazonpay_post_data.status_callback);
		const body = await statusUpdate.json();

		if ('waiting' !== body.status) {
			onboardingComplete = true;
			onboardingStatus = body.status;
		}
	}

	if (onboardingStatus === 'connected') {
		document.querySelector('#amazon-pay-section-refresh').querySelector('#failed-message').style.display = 'none';
		document.querySelector('#amazon-pay-enable').checked = true;
	} else {
		document.querySelector('#amazon-pay-section-refresh').querySelector('#success-message').style.display = 'none';
	}

	document.querySelector('#amazon-pay-connect-loading').style.display = "none";
	document.querySelector('#amazon-pay-section-refresh').style.display = "initial"

	document.querySelector('#amazon-pay-refresh-button').addEventListener('click', () => {
		location.reload();
	});
}

window.addEventListener('load', ()=>{
	const button = document.querySelector('#amazon-pay-connect-button');
	if (!button) {
		return;
	}
	button.addEventListener('click', connectAmazonPayAccount);
});
