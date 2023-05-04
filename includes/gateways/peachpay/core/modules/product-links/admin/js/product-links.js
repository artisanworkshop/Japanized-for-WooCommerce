window.addEventListener('DOMContentLoaded' , ()=> {
	const params = (new URL(document.location)).searchParams;
	if (params.get('page') !== 'peachpay' && params.get('tab') !== 'product_links') {
		return;
	}
	getActiveProducts();
})

document.querySelector('#store_slug')?.addEventListener('input', async(event)=>{
	if(event.target.validity.patternMismatch){
		event.target.setCustomValidity('Please match the format requested above');
		event.target.reportValidity();
	} else {
		event.target.setCustomValidity('');
		event.target.reportValidity();
	}
})

document.querySelector('#peachpay-register-product-links')?.addEventListener('click', async (event)=>{
	event.preventDefault();
	event.stopPropagation();
	const merchantURL = document.querySelector('#site_url').value;
	const merchantSlug = document.querySelector("#store_slug").value;
	const merchantName = document.querySelector('#store_name').value;

	const slugExp = /(^[^\sA-Z]*)([a-z0-9]+)$/;

	if( ! slugExp.test(merchantSlug) ) {
		document.querySelector("#store_slug").reportValidity();
		return false;
	}

	const response = await fetch(`${pp_product_links_data.URL}/api/register`, {
		method : "POST",
		headers:{
			'content-type': 'application/json',
		},
		body:JSON.stringify( {
			merchantName,
			merchantURL,
			merchantSlug,
		})
	});

	if(!response.ok) {
		alert("There was an error registering the store. Please try again later.");
		return;
	}

	const data = await response.json();

	if(!data.success) {
		alert(`Error : ${data.message}`)
		return
	}

	document.querySelector('#product_links_key').value = data.merchantID;

	document.querySelector('#hidden_submit').click();
})

async function getActiveProducts() {
	const merchantID = document.querySelector('#productLinksKey')?.value;
	if (!merchantID) {
		return;
	}
	const response = await fetch(`${pp_product_links_data.URL}/api/getProducts`, {
		method:"POST",
		headers: {
			"content-type" : "application/json",
		},
		body:JSON.stringify({
			merchantID:merchantID
		})
	});

	const data = await response.json();

	if(!response.ok) {
		alert("There was an error retrieving your products. Please try again later.");
		return;
	}

	buildProducts(data.products);
}

function buildProducts( products ) {
	if( ! products || products.length < 1 ) {
		document.querySelector('.pp-load')?.classList.remove('pp-load');
		return;
	}
	for( const product of products) {
		buildProduct(product);
	}
	document.querySelector('.pp-load')?.classList.remove('pp-load');
}

function buildProduct( product ) {
	if(!product) {
		return;
	}
	const loadDiv = document.createElement('div');
	loadDiv.classList.add("peachpay-product-links-product-parent");
	const productDiv = document.createElement('div');
	productDiv.classList.add('peachpay-product-links-product');

	const productData = document.createElement('div');
	productData.innerHTML = `
	<img src=${product.productDetails?.productImages[0]} width="150px" height="150px">
	<h3>
		${product.productDetails.productName}
	</h3>`;
	const removeContainer = document.createElement('div');
	removeContainer.classList.add('pp-product-links-remove-container');
	const removeButton = document.createElement('div');
	removeButton.classList.add('pp-product-links-remove');
	removeButton.innerHTML = 'X';
	removeButton.addEventListener('click', (event) =>{
		removeProductFromStore(product.ppProductID, loadDiv, productDiv);
	});

	removeContainer.append(removeButton);
	productDiv.append(removeContainer);
	productDiv.append(productData);

	loadDiv.append(productDiv);

	document.querySelector('#active_product_container').insertAdjacentElement('afterbegin', loadDiv);
}

async function removeProductFromStore( productID, parent, product ) {
	parent.classList.add('pp-load');
	product.classList.add('hide');
	const merchantID = document.querySelector('#productLinksKey')?.value;
	const response = await fetch(`${pp_product_links_data.URL}/api/deleteProduct`, {
		method:"POST",
		headers: {
			"content-type" : "application/json",
		},
		body:JSON.stringify({
			merchantID:merchantID,
			productID: productID,
		})
	});

	const data = await response.json();

	if(!response.ok) {
		alert("There was an error removing the product from your store. If this persists please contact support.");
		parent.classList.remove('pp-load');
		product.classList.remove('hide');
		return;
	}

	parent.remove();
}
