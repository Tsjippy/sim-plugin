document.addEventListener('DOMContentLoaded', () => {   
    console.log('Auto refresh media gallery.js loaded');
    document.querySelectorAll('.media-gallery-article').forEach(gallery => {
        reloadMediaGallery(gallery, true);
    })

    
});


let reloadMediaGallery   = async function(gallery, first=false){
    let speed   = gallery.dataset.speed;

    if(typeof(speed) != 'number'){
        return;
    }

    let newGallery  = gallery

    if(!first){
        var formData = new FormData();
        formData.append('types', gallery.dataset.types);
        formData.append('amount', gallery.querySelectorAll('.media-gallery').length);
        formData.append('categories', gallery.dataset.categories);
        formData.append('speed', speed);
        formData.append('title', gallery.querySelector('.media-gallery-title').textContent);
        var response = await FormSubmit.fetchRestApi('media_gallery/show_media_gallery', formData, false);

        if(response){
            // convert the html to a node so we can pass it on to the next iteration
            let div = document.createElement('div');
            div.innerHTML   = response;
            newGallery      = div.querySelector('.media-gallery-article');

            let imgs    = newGallery.querySelectorAll('img');
            let amount  = imgs.length;
            imgs.forEach(img=>{
                img.removeAttribute('loading');
                img.addEventListener('load', () => {
                    amount--;

                    //console.log('Current amount is '+amount);

                    if(amount === 0){
                        //console.log('Updating ');
                        gallery.replaceWith(newGallery);
                    }
                });
                img.src=img.src
            });
        }
    }

    setTimeout(
        reloadMediaGallery,
        speed*1000,
        newGallery
    );
}