window.copy_to_clip_board = function(text_to_copy)
{
    
    let textArea = document.createElement("textarea");
    textArea.value = text_to_copy;

    document.body.appendChild(textArea);

    textArea.select();

    document.execCommand('copy');

    document.body.removeChild(textArea);

    Swal.fire({
        icon: 'success',
        title: 'Copiado al portapapeles',
        text: 'El texto ha sido copiado al portapapeles.'
    });

}