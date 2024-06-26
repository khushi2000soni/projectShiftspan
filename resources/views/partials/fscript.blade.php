<div class="sidebar_overlay d-lg-none"></div>
<!-- Jquery Library -->
<script src="{{ asset('js/jquery.min.js') }}"></script>
<!-- Bootstrap Js -->
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
<!-- Custom Js -->
<script src="{{ asset('js/custom.js') }}"></script>


@include('partials.alert')

<!-- datatable -->
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>

@php
$arr = [
    "sZeroRecords" => __('cruds.datatable.data_not_found'),
    "sProcessing" => '<img src="'.(asset(config('constant.default.datatable_loader'))).'" width="100"/>',
    "sLengthMenu" => __('cruds.datatable.show') . " _MENU_ " . __('cruds.datatable.entries'),
    "sInfo" => config('app.locale') == 'en' ?
        __('cruds.datatable.showing') . " _START_ " . __('cruds.datatable.to') . " _END_ " . __('cruds.datatable.of') . " _TOTAL_ " . __('cruds.datatable.entries') :
        __('cruds.datatable.showing') . "_TOTAL_" . __('cruds.datatable.to') . __('cruds.datatable.of') . "_START_-_END_" . __('cruds.datatable.entries'),
    "sInfoEmpty" => __('cruds.datatable.showing') . " 0 " . __('cruds.datatable.to') . " 0 " . __('cruds.datatable.of') . " 0 " . __('cruds.datatable.entries'),
    "search" => __('cruds.datatable.search'),
    "paginate" => [
        "first" => __('cruds.datatable.first'),
        "last" => __('cruds.datatable.last'),
        "next" => __('cruds.datatable.next'),
        "previous" => __('cruds.datatable.previous'),
    ],
    "autoFill" => [
        "cancel" => __('message.cancel'),
    ],
];

$jsonArr = json_encode($arr);
@endphp

<script>
    getNotifications();
    
    // Custom select box
    $(document).on('click', '.select-styled', function() {
		$('.select-options').not($(this).next('.select-options')).slideUp();
		$(this).next('.select-options').slideToggle();
	});
    
    document.addEventListener('shown.bs.modal', function(event) {
        const modal = bootstrap.Modal.getInstance(event.target);
        // Update the backdrop option to "static"
        modal._config.backdrop = 'static';
    });

    // Password field hide/show functiolity
    $(document).on('click', '.toggle-password',function () {        
        var passwordInput = $(this).closest('.password-area').find('input');        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            $(this).removeClass('close-eye').addClass('open-eye');
        } else {
            passwordInput.attr('type', 'password');
            $(this).removeClass('open-eye').addClass('close-eye');
        }
    });
    
    $( document ).ajaxError(function( event, response, settings ) {
        if(response.status == 401){
            window.location.href = "{{ route('login') }}";
        }
    });

    // Datatable global default configuration
    $(document).ready(function(e){
        (function ($, DataTable) {
            $.extend(true, DataTable.defaults, {
                'responsive': true,
                "scrollCollapse" : true,
                'autoWidth' : true,
                language: {!! $jsonArr !!}
            });
        })(jQuery, jQuery.fn.dataTable);
    });

    
    
    $(document).on('change', '#dt_cb_all', function(e){
        var t = $(this);
        if(t.prop('checked') === true){
            $('.dt_cb').prop('checked', true);
        } else {
            $('.dt_cb').prop('checked', false);
        }
    });

    $(document).on('change', '.dt_cb', function(e){    
        if ($('.dt_cb:checked').length == $('.dt_cb').length) {
            $('#dt_cb_all').prop('checked', true);
        } else {
            $('#dt_cb_all').prop('checked', false);
        }
    });

    function updateHeaderProfile(profile_image, user_name){
        if(profile_image != ''){
            $('#header_profile_image').removeClass('default-image');
            $('#header_profile_image').attr('src', profile_image);
        }
        $('#header_auth_name').text(user_name);
    }
    
    $(document).on('click', '.notificationsBtn', function () {

        markAsReadAll();
    
    });

    $(document).on('click','.clear-notify-btn',function(){
        clearNotifications();
    });

    $(document).on('click','.deleteNotfiyBtn',function(e){
        e.preventDefault();

        var uuid = $(this).attr('data-uuid');
        deleteNotification(uuid);

    });

    $(document).on('click','.notificationlist .dropdown',function(e){
        e.preventDefault();
        var element = $('.notificationlist');

        if (element.hasClass('active')) {
            element.removeClass('active');
        } else {
            element.addClass('active');
        }
    });

    function getNotifications() {
       
        // $('.loader-div').show();
        $.ajax({
            type: 'get',
            url: "{{ route('getNotification') }}",
            dataType: 'json',
            success: function (response) {
                 // $('.loader-div').hide();
                if(response.success) {
                    if(response.allNotificationReadStatus){
                        $('.notificationsBtn').addClass('notify-read');
                    }else{
                        $('.notificationsBtn').removeClass('notify-read')
                    }
                    
                    if(response.total == 0){
                        $('.clear-notify-btn').parent().css('display','none');
                    }else{
                        $('.clear-notify-btn').parent().css('display','block');
                    }

                    $('.notifications_area').html(response.htmlView);
                   
                }
            },
            error: function (response) {
                if(response.responseJSON.error_type == 'something_error'){
                    toasterAlert('error',response.responseJSON.error);
                    //$('.loader-div').hide();
                } 
            },
        });
    }

    function markAsRead(notify_id){
        // $('.loader-div').show();
       $.ajax({
            type: 'get',
            url: "{{route('read.notification')}}",
            dataType: 'json',
            data:{
                _token: "{{ csrf_token() }}",
                notification: notify_id,   
            },
            success: function (response) {
                // console.log(response);
                if(response.success == true){
                   
                    getNotifications();

                    // $('.loader-div').hide();
                }
            },
            error: function (response) {
                // $('.loader-div').hide();
                if(response.responseJSON.error_type == 'something_error'){
                    toasterAlert('error',response.responseJSON.error);
                } 
            },
        });
    }

    function markAsReadAll(){
        // $('.loader-div').show();
       $.ajax({
            type: 'get',
            url: "{{route('readall.notification')}}",
            dataType: 'json',
            success: function (response) {
                // console.log(response);
                if(response.success == true){ 
                    getNotifications();
                }
            },
            error: function (response) {
                // $('.loader-div').hide();
                if(response.responseJSON.error_type == 'something_error'){
                    toasterAlert('error',response.responseJSON.error);
                } 
            },
        });
    }

    function clearNotifications() {

       $('.loader-div').show();     

        $.ajax({
            type: 'get',
            url: "{{ route('clear.notification') }}",
            dataType: 'json',
            success: function (response) {
                $('.loader-div').hide();
                if(response.success) {
                    toasterAlert('success',response.message);
                    getNotifications();
                }
            },
            error: function (response) {
                $('.loader-div').hide();
                if(response.responseJSON.error_type == 'something_error'){
                    toasterAlert('error',response.responseJSON.error);
                }else if(response.responseJSON.error_type == 'warning'){ 
                    toasterAlert('warning',response.responseJSON.message);
                }
            },
        });

    }

    function deleteNotification(uuid) {

        $('.loader-div').show();     

        $.ajax({
            type: 'post',
            url: "{{ route('delete.notification') }}",
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            data:{'uuid':uuid},
            dataType: 'json',
            success: function (response) {
                $('.loader-div').hide();
                if(response.success) {
                    getNotifications();
                    toasterAlert('success',response.message);
                }
            },
            error: function (response) {
                $('.loader-div').hide();
                if(response.responseJSON.error_type == 'something_error'){
                    toasterAlert('error',response.responseJSON.error);
                }else if(response.responseJSON.error_type == 'warning'){ 
                    toasterAlert('warning',response.responseJSON.message);
                }
            },
        });
    }

    @can('staff_view')
        $(document).on("click",".viewStaffBtn", function($type) {
            event.preventDefault();
            $('.loader-div').show();

            var url = $(this).data('href');
            var type = $(this).data('type');
            $.ajax({
                type: 'get',
                url: url,
                data: {
                    'type' : type
                },
                dataType: 'json',
                success: function (response) {

                    if(response.success) {
                        $('.popup_render_div').html(response.htmlView);
                        $('#staffDetails').modal('show');
                        $('.loader-div').hide();
                    }
                },
                error: function (response) {
                    if(response.responseJSON.error_type == 'something_error'){
                        toasterAlert('error',response.responseJSON.error);
                    } 
                }
            });
        });
    @endcan

</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function showPdfLoader() {
        const loader = document.getElementById('pdf-loader');
        if (loader) {
            loader.style.display = 'block';
        }
    }

    function hidePdfLoader() {
        const loader = document.getElementById('pdf-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }

    function renderPDF(url) {
        showPdfLoader();

        const container = document.getElementById('pdf-canvas-container');
        container.innerHTML = '';  // Clear the container

        pdfjsLib.getDocument(url).promise.then(function(pdf) {
            const pageCount = pdf.numPages;

            for (let pageNumber = 1; pageNumber <= pageCount; pageNumber++) {
                // Create a canvas for each page
                const canvas = document.createElement('canvas');
                canvas.id = 'pdf-canvas-' + pageNumber;
                container.appendChild(canvas);

                // Render each page
                pdf.getPage(pageNumber).then(function(page) {
                    const viewport = page.getViewport({ scale: 1.5 });
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    const ctx = canvas.getContext('2d');
                    const renderContext = {
                        canvasContext: ctx,
                        viewport: viewport
                    };

                    page.render(renderContext).promise.then(function() {
                        if (pageNumber === pageCount) {
                            hidePdfLoader(); // Hide loader when last page is rendered
                        }
                    });
                });
            }
        });
    }

    // Event listener to open the modal and render PDF
    $('#HelpPdf').on('shown.bs.modal', function () {
        const pdfUrl = "{{ getSetting('help_pdf') ? getSetting('help_pdf') : asset(config('constant.default.help_pdf')) }}";
        renderPDF(pdfUrl);

        // Ensure the download link is set after the modal is fully shown
        const downloadLink = document.getElementById('pdf-download-link');
        if (downloadLink) {
            downloadLink.href = pdfUrl;
        }
    });

    // Clear container when modal is closed
    $('#HelpPdf').on('hidden.bs.modal', function () {
        const container = document.getElementById('pdf-canvas-container');
        if (container) {
            container.innerHTML = '';  // Clear the container
        }
    });
});
</script>


