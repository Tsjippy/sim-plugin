document.addEventListener('DOMContentLoaded', () => {   
    console.log('Page gallery.js loaded');
    document.querySelectorAll('.page-gallery-article').forEach(gallery => {
        reloadPageGallery(gallery, true);
    })

    
});


let reloadPageGallery   = async function(gallery, first=false){
    let speed   = gallery.dataset.speed;

    if(typeof(speed) != 'number'){
        return;
    }

    let newGallery  = gallery

    if(!first){
        var formData = new FormData();
        formData.append('postTypes', gallery.dataset.posttypes);
        formData.append('amount', gallery.querySelectorAll('.page-gallery').length);
        formData.append('categories', gallery.dataset.categories);
        formData.append('speed', speed);
        formData.append('title', gallery.querySelector('.page-gallery-title').textContent);
        var response = await FormSubmit.fetchRestApi('pagegallery/show_page_gallery', formData, false);

        if(response){
            // convert the html to a node so we can pass it on to the next iteration
            let div = document.createElement('div');
            div.innerHTML   = response;
            newGallery      = div.querySelector('.page-gallery-article');

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
        reloadPageGallery,
        speed*1000,
        newGallery
    );
}