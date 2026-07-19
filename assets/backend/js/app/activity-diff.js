function showDiff(btn) {
    var oldData = $(btn).data('old');
    var newData = $(btn).data('new');

    if (typeof oldData === 'string') { try { oldData = JSON.parse(oldData); } catch (e) {} }
    if (typeof newData === 'string') { try { newData = JSON.parse(newData); } catch (e) {} }

    $('#old-data').text(oldData ? JSON.stringify(oldData, null, 4) : 'N/A');
    $('#new-data').text(newData ? JSON.stringify(newData, null, 4) : 'N/A');
    $('#diffModal').modal('show');
}
