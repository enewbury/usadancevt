module.exports = {
    uploadImage: function(before, progressCallback, successCallback){

        //input that opens file browser
        var fileInput = $('#ajax-file-input');

        //open file browser window
        fileInput.show().click().hide();

        //remove leftover event handler
        fileInput.unbind('change');

        //wait for event that user has chosen something
        fileInput.change(function(){

            //get file from input
            var file=fileInput[0].files[0];

            //generate form data
            var formData=new FormData();
            formData.append('file', file, file.name);

            //send ajax with form data
            var xhr=new XMLHttpRequest();
            if(progressCallback !== null){
                xhr.upload.addEventListener("progress", progressCallback, false);
            }
            xhr.open('POST', '/ajax/upload');
            xhr.onload = successCallback;
            if(before !==null ){
                before();
            }
            xhr.send(formData);

            fileInput.wrap('<form>').closest('form').get(0).reset();
            fileInput.unwrap();

        });
    }
};