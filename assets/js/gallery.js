jQuery(document).ready(function($) {

    $('#gallery_images').sortable({
        update: function() {
            updateGalleryInput();
        }
    });

    // Obsługa dodawania zdjęć
    $('#add_gallery_images').on('click', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: 'Wybierz zdjęcia',
            multiple: true,
            library: {
                type: 'image'
            },
            button: {
                text: 'Użyj tych zdjęć'
            }
        });

        frame.on('select', function() {
            var attachments = frame.state().get('selection').map(function(attachment) {
                attachment = attachment.toJSON();
                // Upewnij się, że używamy dokładnie tego samego HTML co w PHP
                return '<li data-id="' + attachment.id + '">' +
                       '<img src="' + (attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url) + '" />' +
                       '<a href="#" class="remove_image">Usuń</a>' +
                       '</li>';
            });

            $('#gallery_images').append(attachments.join(''));
            updateGalleryInput();
        });

        frame.open();
    });

    // Obsługa usuwania zdjęć
    $(document).on('click', '.remove_image', function(e) {
        e.preventDefault();
        $(this).parent().remove();
        updateGalleryInput();
    });

    function updateGalleryInput() {
        var imageIds = $('#gallery_images li').map(function() {
            return $(this).data('id');
        }).get();
        $('#gallery_input').val(imageIds.join(','));
    }
});
