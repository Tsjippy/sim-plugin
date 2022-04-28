document.addEventListener("DOMContentLoaded", function() {
    document.querySelector('[name="download_video"]').addEventListener('click', async ev=> {
        const vimeoUrl   = ev.target.closest('form').querySelector('[name="download_url"]').value;

        if(vimeoUrl==''){
            display_message('Please give an url to download from', 'error');
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

        display_message('Download started please wait till it finishes');

        var response    = await fetchRestApi('vimeo/download_to_server', formData);

        //hide loader
        ev.target.closest('.submit_wrapper').querySelector('.loadergif').classList.add('hidden');

        if(response){
            display_message(response, 'success');
        }else{
            ev.target.closest('form').querySelector('[name="download_url"]').value = '';
        }
    });
});