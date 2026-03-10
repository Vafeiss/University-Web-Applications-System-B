$(document).ready(function() {

    $('#searchBtn').click(function() {

        let keyword = $('#keyword').val();
        let category = $('#category').val();
        let sort = $('#sort').val();
        let followedOnly = $('#followedOnly').is(':checked');
        let followedByUserId = $('#followedByUserId').val();
        let from = $('#from').val();
        let to = $('#to').val();

        if (followedOnly && !followedByUserId) {
            $('#results').text('Enter a current user ID to use followed-user filtering.');
            return;
        }

        $.ajax({
            url: '../backend/controllers/search_controllers.php',
            method: 'GET',
            dataType: 'json',
            data: {
                keyword: keyword,
                category: category,
                sort: sort,
                followed_by_user_id: followedOnly ? followedByUserId : '',
                from: from,
                to: to
            },
            success: function(response) {

                $('#results').html('');

                if(!response.ok) {
                    $('#results').text('Search failed.');
                    return;
                }

                if(response.data.length === 0) {
                    $('#results').html('<p>No results found</p>');
                    return;
                }

                response.data.forEach(function(post) {
                    const card = $('<div>');
                    const title = $('<h3>').text(post.title);
                    const meta = $('<p>').text(
                        'Category: ' + (post.category_name || 'Uncategorized') +
                        ' | Author: ' + post.username +
                        ' | Date: ' + post.timestamp
                    );
                    const content = $('<p>').text(post.content);

                    card.append(title);
                    card.append(meta);
                    card.append(content);
                    card.append($('<hr>'));

                    $('#results').append(card);
                });

            },
            error: function(xhr) {
                let message = 'Search failed.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }
                $('#results').text(message);
            }
        });

    });

});
