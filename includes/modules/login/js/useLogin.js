import {
    fetchEndpoint,
    preparePublicKeyCredentials,
    preparePublicKeyOptions,
} from './common.js';

const useLogin = ({actionUrl = '/login', actionHeader = {}, optionsUrl = '/login/options'}, optionsHeader = {}) => {
    return async (data) => {
        const optionsResponse = await fetchEndpoint(data, optionsUrl, optionsHeader);
        const json = await optionsResponse.json();
        if (! optionsResponse.ok) {
            throw json;
          } 
        const publicKey = preparePublicKeyOptions(json);
        const credentials = await navigator.credentials.get({publicKey});
		document.querySelector('#webauthn_wrapper .status_message').textContent	= 'Verifying...';
        const publicKeyCredential = preparePublicKeyCredentials(credentials);
        const actionResponse = await fetchEndpoint(publicKeyCredential, actionUrl, actionHeader);
        const responseBody = await actionResponse.text();
        if (! actionResponse.ok) {
            throw JSON.parse(responseBody);
        }

        return responseBody !== '' ? JSON.parse(responseBody) : responseBody;
    };
};

export default useLogin;
