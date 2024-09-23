document.addEventListener('click', function (event) {
    if (event.target.classList.contains('escsrv_remove_item_button')) {
        var itemToRemove = event.target.closest('.escsrv_item');
        if (itemToRemove) {
            itemToRemove.remove();
        }
    }
});

document.getElementById('escsrv_add_item_button').addEventListener('click', function () {
    var item = newItemHtml.replaceAll('[__index__]', generateUUID());
    var wrapper = document.getElementById('escsrv_itens_wrapper');
    wrapper.insertAdjacentHTML('beforeend', item);
});

function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        var r = Math.random() * 16 | 0,
            v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}