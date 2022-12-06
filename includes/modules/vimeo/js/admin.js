document.addEventListener("DOMContentLoaded", function() {
    document.querySelector('[name="download_video"]').addEventListener('click', async ev=> {
        const vimeoUrl   = ev.target.closest('form').querySelector('[name="download_url"]').value;

        if(vimeoUrl==''){
            Main.displayMessage('Please give an url to download from', 'error');
            return;
        }

        //show loader
        ev.target.closest('.submit_wrapper').querySelector('.loadergif').classList.remove('hidden');

        const params = new Proxy(new URLSearchParams(window.location.search), {
            get: (searchParams, prop) => searchParams.get(prop),
        });
        let vidmeoId    = params.vimeoid
        let formData    = new FormData();
        formData.append('vimeoid', vidmeoId);
        formData.append('download_url', vimeoUrl);

        Main.displayMessage('Download started please wait till it finishes');

        let response    = await FormSubmit.fetchRestApi('vimeo/download_to_server', formData);

        //hide loader
        ev.target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

        if(response){
            Main.displayMessage(response, 'success');
            ev.target.closest('form').remove();
        }else{
            ev.target.closest('form').querySelector('[name="download_url"]').value = '';
        }
    });

    document.getElementById('cleanup-archive').addEventListener('click', async ev => {
        let response    = await FormSubmit.fetchRestApi('vimeo/cleanup_backup');

        //hide loader
        ev.target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

        if(response){
            Main.displayMessage(response, 'success');
        }
    });
});