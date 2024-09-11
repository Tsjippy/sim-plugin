import {showLoader} from './../../../js/imports.js';

console.log('Vimeo admin js loaded');

async function fileSize(url){
    let response = await fetch(url, {method: 'HEAD'});

    return response.headers.get("Content-Length");
}

let timerId;

async function updateProgress(){
    let params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    let vidmeoId    = params.vimeoid;

    let percent     = 0;

    let formData    = new FormData();
    formData.append('vimeoid', vidmeoId);
    let curSize    = await FormSubmit.fetchRestApi('vimeo/get_download_progress', formData);

    // calculate the percentage
    if(curSize){
        percent     = ((curSize/document.getElementById('progressbar').dataset.totalsize)*100).toFixed(1);
        document.querySelector('#progressbar div').style.width  = `${percent}%`;
        document.querySelector('#information div').textContent  = `${percent}% downloaded`;
    }

    if(percent < 100){
        timerId = setTimeout(updateProgress, 5000);
    }else{
        clearTimeout(timerId);
        Main.displayMessage('Download finished ', 'info', true);

        //hide loader
        document.querySelectorAll('.submit_wrapper .loadergif:not(.hidden)').classList.add('hidden');
    }
}

Number.prototype.formatBytes = function() {
    var units = ['B', 'KB', 'MB', 'GB', 'TB'],
        bytes = this,
        i;
 
    for (i = 0; bytes >= 1024 && i < 4; i++) {
        bytes /= 1024;
    }
 
    return bytes.toFixed(2) + units[i];
}

/**
 * Downloads a vimeo video to the server
 *
 * @param {*} ev 
 * @returns 
 */
async function downloadVimeoVideo(ev){
    const vimeoUrl   = ev.target.closest('form').querySelector('[name="download_url"]').value;

    if(vimeoUrl == ''){
        Main.displayMessage('Please give an url to download from', 'error');
        return;
    }

    //show loader
    ev.target.closest('.submit_wrapper').querySelector('.loadergif').classList.remove('hidden');

    // initiate the download
    let params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    let vidmeoId    = params.vimeoid

    let formData    = new FormData();
    formData.append('vimeoid', vidmeoId);
    formData.append('download_url', vimeoUrl);

    // when download is done
    FormSubmit.fetchRestApi('vimeo/download_to_server', formData).then(response=>{
        clearTimeout(timerId);

        Main.displayMessage(response, 'info', true);

        //hide loader and progressbar
        document.querySelectorAll('.submit_wrapper .loadergif:not(.hidden), #progressbar, #information').forEach(el=>el.classList.add('hidden'));
    });

    // display the progress
    let downloadSize    = await fileSize(vimeoUrl);

    document.getElementById('progressbar').dataset.totalsize    = downloadSize;

    document.getElementById('progressbar').innerHTML = `<div style="width:0%;background:linear-gradient(to bottom, #8a1a0e 0%,#b22222 100%);height:35px;">&nbsp;</div>`;

    document.getElementById('information').innerHTML = `<div style="margin-top: -28px;text-align:center; color: white;font-weight:bold;text-shadow: 1px 0 0 #000, 0 -1px 0 #000, 0 1px 0 #000, -1px 0 0 #000;">0% downloaded</div>`;

    Main.displayMessage('Download started, '+parseInt(downloadSize).formatBytes()+' to go', 'info', true);

    updateProgress();
}

/**
 * Stores the url given as vimeo post meta data
 * @param {*} ev 
 */
async function storeVimeoUrlLocation(ev){
    const vimeoUrl   = ev.target.closest('form').querySelector('[name="external_url"]').value;

    if(vimeoUrl == ''){
        Main.displayMessage('Please provide an external url', 'error');
        return;
    }

    //show loader
    ev.target.closest('.submit_wrapper').querySelector('.loadergif').classList.remove('hidden');

    // initiate the download
    let params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    let vidmeoPostId    = params.vimeopostid

    let formData    = new FormData();
    formData.append('post_id', vidmeoPostId);
    formData.append('external_url', vimeoUrl);

    // when download is done
    FormSubmit.fetchRestApi('vimeo/store_external_url', formData).then(response=>{
        Main.displayMessage(response, 'info', true);

        //hide loader and progressbar
        document.querySelectorAll('.submit_wrapper .loadergif:not(.hidden)').forEach(el=>el.classList.add('hidden'));

        ev.target.closest('form').querySelector('[name="external_url"]').value  = '';
    });
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

    document.querySelectorAll('[name="save_vimeo_url"]').forEach(el=>el.addEventListener('click', storeVimeoUrlLocation));

    document.getElementById('cleanup-archive').addEventListener('click', cleanUpBackup);
});