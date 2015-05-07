/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

jQuery(document).ready(function ( ) {

    var table_id = 1;

    function rtp_calculate_points(userslist) {

        jQuery.each(userslist, function (key, userdata) {
            // calculate points for each user
            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajaxurl,
                data: {
                    'action': 'calculate_points',
                    'user_id': userdata.ID,
                    'rtp_mycred_settings': rtp_hook_prefs
                },
                success: function (data) {
                    if (data.updated) {
                        jQuery('.rtp-mycred-table').show();
                        var table_data = '<tr><td width="20">' + table_id + '</td><td>' + data.username + '</td><td>' + data.currentpoints + '</td><td>' + data.calculatedpoints + '</td><td><span class="' + ((data.updated_old_user === true) ? "updated" : "default") + '">' + data.finalpoints + '</span></td></tr>';
                        jQuery('#rtp-mycred-user-list').append(table_data);
                        jQuery('.messages span.totalcount').html(data.total_offset);
                        table_id++;
                    } else {
                        jQuery('.mycred_error').text(data.message);
                    }
                }
            });
        });
    }

    jQuery('#rtp-calculate-points').on('click', function (e) {
        e.preventDefault();
        // get the user limit value to set as offset
        var user_limit = jQuery('#rtp_user_limit').val();
        // get the list of all users
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajaxurl,
            async: false,
            data: {
                'action': 'rtp_get_users_list',
                'user_limit': user_limit
            },
            success: function (data) {

                if (data.all_done) { // if ALL Users are updated
                    jQuery('.messages p').html(data.all_done).delay( 10000, function(){
                        jQuery('#rtp-calculate-points').remove();
                                jQuery('#rtp-mycred-calculate-form').html("<button class='button-primary' onClick='location.reload();'>Reload Page</button>");
                        e.stopPropagation();
                    });
                } else {
                    if (data.fetched) {
                        var users = data.userlist;
                        rtp_calculate_points(users);
                    } else {
                        // all users are completed so reload the page
                        location.reload();
                    }
                }
            }
        });
    }
    );
});


