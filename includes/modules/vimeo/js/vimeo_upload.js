import * as tus from 'tus-js-client';
import {fetchRestApi} from '../../../js/form_submit.js';

console.log('vimeo upload loaded');

export class VimeoUpload{
    constructor(file){
        this.urlStorage     = tus.defaultOptions.urlStorage;
        this.getFingerprint = tus.defaultOptions.fingerprint;
        this.file           = file;
        this.storedEntry    = {};
    }

    async findInStorage(){
        this.fingerprint    = await this.getFingerprint(this.file, { endpoint: sim.baseUrl });
        let storedEntries   = await this.urlStorage.findUploadsByFingerprint(this.fingerprint);

        if (storedEntries.length) {
            this.storedEntry = storedEntries[0];
            if (this.storedEntry.uploadUrl) {
                return true;
            }
            // cleanup
            this.urlStorage.removeUpload(this.storedEntry.urlStorageKey);
        }

        return false;
    }

    async getVimeoUploadUrl(){
        var formData = new FormData();
        formData.append('file_size', this.file.size);
        formData.append('file_name', this.file.name);
        formData.append('file_type', this.file.type);

        var response    = await fetchRestApi('vimeo/prepare_vimeo_upload', formData);

        //Failed
        if(response){
            var uploadUrl		= response.upload_link;
            var postId		    = response.post_id;
            var vimeoId		    = response.vimeo_id;

            this.storedEntry = {
                size: this.file.size,
                metadata: {
                    filename: this.file.name,
                    filetype: this.file.type,
                },
                creationTime: new Date().toString(),
                url: uploadUrl,
                postId: postId,
                vimeoId: vimeoId
            };

            this.storedEntry.urlStorageKey = await this.urlStorage.addUpload(this.fingerprint, this.storedEntry);

            return true;
        }else{
            console.error('Failed');
            console.error(formData);
            console.log(this.file);

            // reset
            return false;
        }
    }

    async tusUploader(){
        /* 
        ** Get the upload url
        */
        // Check if already an url in memory
        var existing    = await this.findInStorage();
        if(!existing){
            // Nothing found, get a new upload url
            var result = await this.getVimeoUploadUrl();

            if(!result){
                return false;
            }
        }

        return new tus.Upload(this.file, {
            uploadUrl: this.storedEntry.url,
            headers: {
                // https://developer.vimeo.com/api/upload/videos#resumable-approach-step-2
                Accept: 'application/vnd.vimeo.*+json;version=3.4' // required
            },
            chunkSize: 50000000, // required
        });
    }

    removeFromStorage(){
        this.urlStorage.removeUpload(this.storedEntry.urlStorageKey);
    }
}