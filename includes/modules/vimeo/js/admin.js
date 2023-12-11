import {showLoader} from './../../../js/imports.js';

console.log('Vimeo admin js loaded');

async function downloadVimeoVideo(ev){
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

    let url = `${sim.baseUrl}/wp-json/sim/v2/vimeo/download_to_server?vimeoid=${vidmeoId}&download_url=${btoa(vimeoUrl)}`

    document.getElementById('loadarea').src=url;

    Main.displayMessage('Download started', 'info', true);
}

async function cleanUpBackup(ev){
    showLoader(ev.target);

    let response    = await FormSubmit.fetchRestApi('vimeo/cleanup_backup');

    //hide loader
    document.querySelectorAll('.loadergif:not(.hidden)').forEach(el=>el.classList.add('hidden'));

    if(response){
        Main.displayMessage(response, 'success');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('[name="download_video"]').forEach(el=>el.addEventListener('click', downloadVimeoVideo));

    document.getElementById('cleanup-archive').addEventListener('click', cleanUpBackup);
});