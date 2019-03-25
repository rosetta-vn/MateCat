APP.tryListGDriveFiles = function() {
    $.getJSON('/gdrive/list', function(listFiles){
        $('.files-gdrive').html('');

        if( listFiles && listFiles.hasOwnProperty('files') ) {
            APP.displayGDriveFiles();

            $.each( listFiles.files, function( index, file ) {
                var iconClass = '';

                if ( file.fileExtension == 'docx' ) {
                    iconClass = 'extgdoc';
                } else if ( file.fileExtension == 'pptx' ) {
                    iconClass = 'extgsli';
                } else if ( file.fileExtension == 'xlsx' ) {
                    iconClass = 'extgsheet';
                }

                $('<tr/>', {
                    'class': 'template-gdrive fade ready',
                    'style': 'display: table-row;'
                })
                .append (
                    $('<td/>', {
                        'class': 'preview'
                    })
                    .append (
                        $('<span/>', {
                            'class': iconClass
                        })
                    )
                )
                .append (
                    $('<td/>', {
                        'class': 'name',
                        text: file.fileName
                    })
                )
                .append (
                    $('<td/>', {
                        'class': 'size'
                    })
                    .append (
                        $('<span/>', {
                            text: APP.formatBytes(file.fileSize)
                        })
                    )
                )
                .append (
                    $('<td/>', {
                        'class': 'delete'
                    })
                    .append (
                        $('<button/>', {
                            'class': 'btn btn-dange ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary',
                            'data-fileid': file.fileId,
                            'role': 'button',
                            'aria-disabled': 'false',
                            click: function() {
                                APP.deleteGDriveFile( $(this).data('fileid') );
                            }
                        })
                        .append (
                            $('<span/>', {
                                'class': 'ui-button-icon-primary ui-icon ui-icon-trash'
                            })
                        )
                        .append (
                            $('<span/>', {
                                'class': 'ui-button-text'
                            })
                            .append (
                                $('<i/>', {
                                    'class': 'icon-ban-circle icon-white'
                                })
                            )
                            .append (
                                $('<span/>', {
                                    text: 'Delete'
                                })
                            )
                        )
                    )
                )
                .appendTo('.files-gdrive');
            });
        } else {
            APP.hideGDriveFiles();
        }
    });
};

APP.restartGDriveConversions = function () {
    var sourceLang = $("#source-lang").dropdown('get value');
    
    $.getJSON('/gdrive/change/' + sourceLang, function(response){
        if(response.success) {
            console.log('Source language changed.');
        }
    });
};

APP.deleteGDriveFile = function (fileId) {
    $.getJSON('/gdrive/delete/' + fileId, function(response){
        if(response.success) {
            APP.tryListGDriveFiles();
        }
    });
};

APP.formatBytes = function(bytes,decimals) {
   if(bytes === 0) return '0 Byte';
   var k = 1024;
   var dm = decimals + 1 || 2;
   var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
   var i = Math.floor(Math.log(bytes) / Math.log(k));
   return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

APP.addGDriveFile = function(exportIds) {
    var jsonDoc = {
        "exportIds": exportIds,
        "action":"open"
    };

    var encodedJson = encodeURIComponent(JSON.stringify(jsonDoc));
    var html = '<div class="modal-gdrive">'+
       ' <div class="ui active inverted dimmer">'+
            '<div class="ui massive text loader">Uploading Files</div>'+
        '</div>'+
    '</div>';
    $(html).appendTo( $( 'body' ));

    $.getJSON('/webhooks/gdrive/open?isAsync=true&state=' + encodedJson, function(response) {
        $('.modal-gdrive').remove();

        if(response.success) {
            APP.tryListGDriveFiles();
        } else {
            console.error('Error when processing request: ' + response);
        }
    });
};

APP.displayGDriveFiles = function() {
    if( !$('#gdrive-files-list').is(":visible") ) {
        $('#upload-files-list, .gdrive-addlink-container').hide();
        $('#gdrive-files-list').show();

        UI.enableAnalyze();
    }
};

APP.hideGDriveFiles = function() {
    if( $('#gdrive-files-list').is(":visible") ) {
        $('#gdrive-files-list').hide();
        $('#upload-files-list, .gdrive-addlink-container').show();
        UI.disableAnalyze();
    }
};

APP.hideGDLink = function () {
    $('.gdrive-addlink-container').hide();
};

APP.showGDLink = function () {
    $('.gdrive-addlink-container').show();
};

$(document).ready( function() {
    $('#clear-all-gdrive').click( function() {
        APP.deleteGDriveFile('all');
    })
});