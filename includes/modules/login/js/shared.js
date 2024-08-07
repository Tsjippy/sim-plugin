export function closeMobileMenu(){
	//close mobile menu
	document.querySelectorAll('#site-navigation, #mobile-menu-control-wrapper').forEach(el=>el.classList.remove('toggled'));
	document.querySelector('body').classList.remove('mobile-menu-open');
	document.querySelectorAll("#mobile-menu-control-wrapper > button").forEach(el=>el.ariaExpanded = 'false');
}

// get response from rest api server
export async function fetchRestApi(url, formData, showErrors=true){
	let response;
	formData.append('_wpnonce', sim.restNonce);

	let result = await fetch(
		`${sim.baseUrl}/wp-json${sim.restApiPrefix}/login/${url}`,
		{
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		}
	);

	try{
		response	= await result.json();
	}catch (error){
		console.error(result);
		console.error(error);
		return false;
	}

	if(result.ok){
		return response;
	}else if(response.code == 'rest_cookie_invalid_nonce'){
		Main.displayMessage('Please refresh the page and try again!', 'error');
		console.error(response);
		console.error(`/login/${url}`);
		return false;
	}else{
		console.error(response);
		if(showErrors){
			Main.displayMessage(response.message, 'error');
		}
		console.error(`/login/${url}`);
		return false;
	}
}

// Decodes a Base64Url string
const base64UrlDecode = (input) => {
input = input
	.replace(/-/g, '+')
	.replace(/_/g, '/');

const pad = input.length % 4;
if (pad) {
	if (pad === 1) {
	throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
	}
	input += new Array(5-pad).join('=');
}

return window.atob(input);
};

// Converts an array of bytes into a Base64Url string
const arrayToBase64String = (a) => btoa(String.fromCharCode(...a));

// Prepares the public key options object returned by the Webauthn Framework
export const preparePublicKeyOptions = publicKey => {
	//Convert challenge from Base64Url string to Uint8Array
	publicKey.challenge = Uint8Array.from(
		base64UrlDecode(publicKey.challenge),
		c => c.charCodeAt(0)
	);

	//Convert the user ID from Base64 string to Uint8Array
	if (publicKey.user !== undefined) {
		publicKey.user = {
		...publicKey.user,
		id: Uint8Array.from(
			window.atob(publicKey.user.id),
			c => c.charCodeAt(0)
		),
		};
	}

	//If excludeCredentials is defined, we convert all IDs to Uint8Array
	if (publicKey.excludeCredentials !== undefined) {
		publicKey.excludeCredentials = publicKey.excludeCredentials.map(
			data => {
			return {
				...data,
				id: Uint8Array.from(
					base64UrlDecode(data.id),
					c => c.charCodeAt(0)
				),
			};
			}
		);
	}

	if (publicKey.allowCredentials !== undefined) {
		publicKey.allowCredentials = publicKey.allowCredentials.map(
			data => {
			return {
				...data,
				id: Uint8Array.from(
					base64UrlDecode(data.id),
					c => c.charCodeAt(0)
				),
			};
			}
		);
	}

	return publicKey;
};

// Prepares the public key credentials object returned by the authenticator
export const preparePublicKeyCredentials = data => {
	const publicKeyCredential = {
		id: data.id,
		type: data.type,
		rawId: arrayToBase64String(new Uint8Array(data.rawId)),
		response: {
		clientDataJSON: arrayToBase64String(
			new Uint8Array(data.response.clientDataJSON)
		),
		},
	};

	if (data.response.attestationObject !== undefined) {
		publicKeyCredential.response.attestationObject = arrayToBase64String(
			new Uint8Array(data.response.attestationObject)
		);
	}

	if (data.response.authenticatorData !== undefined) {
		publicKeyCredential.response.authenticatorData = arrayToBase64String(
			new Uint8Array(data.response.authenticatorData)
		);
	}

	if (data.response.signature !== undefined) {
		publicKeyCredential.response.signature = arrayToBase64String(
			new Uint8Array(data.response.signature)
		);
	}

	if (data.response.userHandle !== undefined) {
		publicKeyCredential.response.userHandle = arrayToBase64String(
			new Uint8Array(data.response.userHandle)
		);
	}

	return publicKeyCredential;
};