import * as tus from 'tus-js-client';

export class VimeoUpload{
    constructor(file){
        this.urlStorage     = tus.defaultOptions.urlStorage;
        this.getFingerprint = tus.defaultOptions.fingerprint;
        this.file           = file;
        this.storedEntry    = {};
    }

    async findInStorage(){
        this.fingerprint    = await this.getFingerprint(this.file, { endpoint: sim.base_url });
        let storedEntries   = await this.urlStorage.findUploadsByFingerprint(this.fingerprint);

        if (storedEntries.length) {
            this.storedEntry = storedEntries[0];
            if (this.storedEntry.uploadUrl) {
                console.debug('previous URL found: ' + this.storedEntry.uploadUrl);
                return true;
            }
            // cleanup
            this.urlStorage.removeUpload(this.storedEntry.urlStorageKey);
        }

        return false;
    }

    async getVimeoUploadUrl(){
        var formdata = new FormData();
        formdata.append('file_size', this.file.size);
        formdata.append('file_name', this.file.name);
        formdata.append('file_type', this.file.type);

        var response    = await formsubmit.fetchRestApi('vimeo/prepare_vimeo_upload', formdata);

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
            console.error(formdata);
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

        var upload = new tus.Upload(this.file, {
            uploadUrl: this.storedEntry.url,
            headers: {
                // https://developer.vimeo.com/api/upload/videos#resumable-approach-step-2
                Accept: 'application/vnd.vimeo.*+json;version=3.4' // required
            },
            chunkSize: 50000000, // required
        });

        return upload;
    }

    removeFromStorage(){
        this.urlStorage.removeUpload(this.storedEntry.urlStorageKey);
    }
}