// Exclude videos from max size filter
plupload.addFileFilter('max_file_size', function(maxSize, file, cb) {
	// Invalid file size
    if (file.size !== undefined && maxSize && file.size > maxSize && file.type.split("/")[0] != 'video') {
        this.trigger('Error', {
            code :      plupload.FILE_SIZE_ERROR,
            message :   plupload.translate('File size error.'),
            file :      file
        });
        cb(false);
    } else {
        cb(true);
    }
});