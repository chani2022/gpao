/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
Message displayer
*/
function getMessageDisplayer(title, content, msgType) {
    if (title === undefined) {
        title = "";
    }
    if (content === undefined) {
        content = "";
    }
    var mModal = '';
    mModal += '<div class="modal mfade" id="_gModal" tabindex="-1" role="dialog" aria-labelledby="_gModal" aria-hidden="true">';
    mModal += '<div class="modal-dialog" role="document">';
    mModal += '<div class="modal-content">';
    mModal += '<div class="modal-header ' + msgType + '">';
    mModal += '<h5 class="text-white modal-title" id="">' + title + '</h5>';
    mModal += '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
    mModal += '<span aria-hidden="true">&times;</span>';
    mModal += '</button>';
    mModal += '</div>';
    mModal += '<div class="modal-body" style="max-height:500px;overflow:scroll">' + content + '</div>';
    mModal += '<div class="modal-footer">';
    mModal += '<button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>';
    mModal += '</div>';
    mModal += '</div>';
    mModal += '</div></div>';

    //$("body").find("#_gModal").remove();
    if ($("body").find("#_gModal").length === 0) {
        $("body").append(mModal);
    } else {
        $("#_gModal .modal-header").removeClass("bg-danger");
        $("#_gModal .modal-header").removeClass("bg-success");
        $("#_gModal .modal-header").removeClass("bg-secondary");
        
        $("#_gModal .modal-header").addClass(msgType);
        
        
        $("#_gModal .modal-title").html(title);
        $("#_gModal .modal-body").html(content);    
    }
}

/**
 * test modal
 */
function displayDanger(title, content) {
    getMessageDisplayer(title, content, "bg-danger");
    $("#_gModal").modal("show");
}

function displaySuccess(title, content) {
    getMessageDisplayer(title, content, "bg-success");
    $("#_gModal").modal("show");
}

function displayDefault(title, content) {
    getMessageDisplayer(title, content, "bg-secondary");
    $("#_gModal").modal("show");
}