async function getNonce(){
    let formData	= new FormData();
    formData.append('_wpnonce', sim.restNonce);

    let result;
    try{
        result = await fetch(
            `${sim.baseUrl}/wp-json${sim.restApiPrefix}/fetch_nonce`,
            {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }
        );
    }catch(error){
        console.error(error);
    }

    let response	        = await result.text();

    let json		        = JSON.parse(response);

    window.sim.restNonce    = json;
}

getNonce();