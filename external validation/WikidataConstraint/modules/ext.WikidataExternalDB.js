var selected_ids = [];

function removeFromArray(arr, element) {
    arr.splice($.inArray(element, arr),1);
}

function deleteFromList(evt){
    iid = evt.data;
    removeFromArray(selected_ids, iid);
    $(this).closest('li').remove();
    return false;
}

function handleInput () {
    iid =  $( '#item-input').val();
    if (iid !== ''){
        selected_ids.push(iid);
        delete_link = $('<a href="#"> x </a>').click(iid, deleteFromList);
        li_element = $('<li>' + ' (' + iid + ')' + '</input></li>');
        li_element.append(delete_link);
        $('#results-list').append(li_element);
        $( '#item-input').val('').focus();
        doQuery();
    }
}

function doQuery() {
    url = mw.util.wikiScript( 'api' ) + '?action=wbgetentities&format=json&ids=' +
    selected_ids.map(encodeURIComponent).join(','); //+ '&limit=50&language=' + wgPageContentLanguage;
    $.get(url, function( data ) {
        $('#result').html('<h3>Result:</h3>');
        $('#result').append(JSON.stringify(data) + '<br>');
    });
}

$( document ).ready(function (){
    /*
    $( '#item-input' ).entityselector({
        url: mw.util.wikiScript( 'api' ),
        selectOnAutocomplete: true,
        type: 'item'
    });
     */

    $( '#check-item-btn' ).click(function() {
        handleInput();
    });

    $( '#item-input' ).keyup(function (e) {
        if (e.keyCode === 13) {
            handleInput();
        }
    });

});
