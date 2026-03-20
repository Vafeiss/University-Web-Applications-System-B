$(document).ready(function() {

    $('#searchBtn').click(function() {

        let keyword = $('#keyword').val();
        let category = $('#category').val();
        let from = $('#from').val();
        let to = $('#to').val();

        $.ajax({
            url: '../backend/controllers/search_controller.php',
            method: 'GET',
            data: {
                keyword: keyword,
                category: category,
                from: from,
                to: to
            },
            success: function(response) {

                $('#results').html('');

                if(response.data.length === 0) {
                    $('#results').html('<p>No results found</p>');
                    return;
                }

                response.data.forEach(function(post) {
                    $('#results').append(`
                        <div>
                            <h3>${post.title}</h3>
                            <p>${post.content}</p>
                            <hr>
                        </div>
                    `);
                });

            }
        });

    });

});