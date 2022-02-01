(function($) {
    var username = 'saiful@wppool.dev';
    var password = 'kJqM L5Vh qDtv QpYH 1Tjw JmYI';  

    $('.fs_button_groups .fs_btn').click(function(e){ 
        var status_type = e.target.innerText.toLowerCase();
        $(".fs_btn").removeClass("fs_btn_active");
        //console.log(this);
        var this_button = this;
        
        $.ajax
            ({
                type: "GET",
                url: "https://fluent.wppool.dev/wp-json/fluent-support/v2/tickets?filters[status_type]="+status_type+"",
                dataType: 'json',
                async: false,
                data: '{}',
                beforeSend: function (xhr){ 
                    xhr.setRequestHeader('Authorization', make_base_auth(username, password));
                    $(".fs_tk_body").addClass('loading');
                },
                success: function (response){
                    $(this_button).addClass("fs_btn_active");
                    var site_url = window.location.href;
                    var replace_response = [];
                    response['tickets']['data'].forEach(element => {
                        replace_response.push(`
                            <tr>
                                <td><a href="${site_url}/my-account/?tab=ticket&amp;ticket_id=63" class="fs_tk_preview"><strong>${element.title}</strong>
                                        <div class="prev_text_parent">
                                            
                                        </div>
                                    </a></td>
                                <td class="fs_thread_count"><span class="fs_thread_count">${element.response_count}</span></td>
                                <td class="fs_tk_status"><span class="el-tag el-tag--success el-tag--mini el-tag--dark">${element.status}													<!--v-if--></span></td>
                                <td class="fs_tk_date"><span class="fs_tk_date">${element.updated_at}</span></td>
                            </tr>
                        `);
                    });
                    $('#ticket_list').html(replace_response);
                },
                
            }).done(function () {
                setTimeout(function(){
                    $(".fs_tk_body").removeClass('loading');
                  },400);
            });
    })
    function make_base_auth(user, password) {
        var tok = user + ':' + password;
        var hash = btoa(tok);
        return "Basic " + hash;
    }
})( jQuery );