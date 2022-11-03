import $ from "jquery";
 function addFormToCollection($collectionHolderClass) {
    var $collectionHolder = $('.' + $collectionHolderClass);
    var prototype = $collectionHolder.data('prototype');
    var index = $collectionHolder.data('index');
    var newForm = prototype;
    newForm = newForm.replace(/__name__/g, index);
    $collectionHolder.data('index', index + 1);

    let  $newFormLi = $('<li class="d-inline-block list-group-item d-flex justify-content-between"></li>').append(newForm);
    $collectionHolder.append($newFormLi);
    addTagFormDeleteLink($newFormLi);

}

function addTagFormDeleteLink($tagFormLi) {
    var $removeFormButton = $('<a href="#" class="deleteKeyCloakGroup" type="remove-group"><i class="text-danger px-1 fas fa-trash" data-mdb-toggle="tooltip" title="Delete Keycloak Group" data-original-title="Delete Keycloak Group"></i></a>');
    $tagFormLi.append($removeFormButton);
    $removeFormButton.on('click', function(e) {
        $tagFormLi.remove();
    });
}
function initKeycloakGroups(){
      $('#add_item_link').off('click')
    var $groupsCollectionHolder = $('ul.keycloakGroups');
    $groupsCollectionHolder.find('li').each(function() {
        addTagFormDeleteLink($(this));
    });

    $groupsCollectionHolder.data('index', $groupsCollectionHolder.find('input').length);
    $('#add_item_link').on('click', function(e) {

        var $collectionHolderClass = $(e.currentTarget).data('collectionHolderClass');
        addFormToCollection($collectionHolderClass);
    })
};
export {initKeycloakGroups};
