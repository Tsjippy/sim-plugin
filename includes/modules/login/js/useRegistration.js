import {
    fetchEndpoint,
    preparePublicKeyCredentials,
    preparePublicKeyOptions,
} from './common.js';

const useRegistration = ({actionUrl = '/register', actionHeader = {}, optionsUrl = '/register/options'}, optionsHeader = {}) => {
    return async (data) => {
        const optionsResponse = await fetchEndpoint(data, optionsUrl, optionsHeader);
        const json = await optionsResponse.json();
        const publicKey = preparePublicKeyOptions(json);
        const credentials = await navigator.credentials.create({publicKey});
		document.querySelector('#loader_wrapper .message').textContent  = 'Saving authenticator...';
        const publicKeyCredential = preparePublicKeyCredentials(credentials);
        const actionResponse = await fetchEndpoint(publicKeyCredential, actionUrl, actionHeader);
        if (! actionResponse.ok) {
            throw actionResponse;
        }
        const responseBody = await actionResponse.text();

        return responseBody !== '' ? JSON.parse(responseBody) : responseBody;
    };
};

export default useRegistration;
