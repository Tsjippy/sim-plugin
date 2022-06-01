document.addEventListener("DOMContentLoaded", function() {
    document.querySelector('[name="download_video"]').addEventListener('click', async ev=> {
        const vimeoUrl   = ev.target.closest('form').querySelector('[name="download_url"]').value;

        if(vimeoUrl==''){
            main.displayMessage('Please give an url to download from', 'error');
            return;
        }

        //show loader
        ev.target.closest('.submit_wrapper').querySelector('.loadergif').classList.remove('hidden');

        const params = new Proxy(new URLSearchParams(window.location.search), {
            get: (searchParams, prop) => searchParams.get(prop),
        });
        var vidmeoId    = params.vimeoid
        var formData    = new FormData();
        formData.append('vimeoid', vidmeoId);
        formData.append('download_url', vimeoUrl);

        main.displayMessage('Download started please wait till it finishes');

        var response    = await FormSubmit.fetchRestApi('vimeo/download_to_server', formData);

        //hide loader
        ev.target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

        if(response){
            main.displayMessage(response, 'success');
            ev.target.closest('form').remove();
        }else{
            ev.target.closest('form').querySelector('[name="download_url"]').value = '';
        }
    });
});