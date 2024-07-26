@extends('layouts.app')
@section('title','Vehicles Register')
@section('pages')
<style type="text/css">
    .add_btn {
        height: 30px; width: 30px; border-radius: 100%; padding: 0
    }
    .breadcrumb-title {
      border-right: none;
    }
    .exl_file {
        height: 32px !important;
    }
    .dataTables_filter{
        float: right;
        margin-right: 5%
    }
    #example_length{
        margin-left: 1%;
        width:30px;
    }
    .custom-select{
        width: 100px;
    }
    #loader {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999;
    }

    #overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(167, 156, 156, 0);
        z-index: 9998;
        display: none;
    }

</style>

  <!--breadcrumb-->
  <div class="page-breadcrumb row align-items-center justify-content-between mb-3 px-3 px-md-0">
    <div class="breadcrumb-title col-md-6"> Vehicles Register</div>
    <div class="col-md-6">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb justify-content-md-end mb-0">
          <li class="breadcrumb-item"><i class="bi bi-info-circle-fill text-info show-help-support" data-id="46" data-bs-toggle="modal" data-bs-target="#commonModal"></i></li>
          <li class="breadcrumb-item" aria-current="page">Vehicles Register</li>
          <li class="breadcrumb-item active" aria-current="page"><i class="bi bi-dot"></i> Bulk Import </li>
        </ol>
      </nav>
    </div>
  </div>
  <!--end breadcrumb-->

  <div class="card style_table shadow-none radius-5 min-height">
    {{-- <div class="card-header py-3">
      @include('layouts.partials.nav-menu')
      <div class="row gx-2">
        <div class="col-lg-3 col-md-3 col-4 dataTables_length" id="example_length">
            <i class="bi bi-funnel"></i>
            <select class="form-select sorting sorting-box" id="" name="example_length" aria-controls="example">
                <option value="10">Show: 10</option>
                <option value="30">Show: 30</option>
                <option value="50">Show: 50</option>
            </select>
        </div>
        <div class="col-lg-9 col-md-9">
          <div class="ms-auto float-md-end action_buttons">


            <div class="btn-group">
                <a href="javascript:history.back()" class="btn btn-outline-secondary me-1"><i class="bx bx-arrow-back ms-0 me-1"></i>Back</a>
            </div>

            <div class="btn-group">
                <a href="{{route('vehicle-register-import.modal')}}" class="btn btn-primary"><i class="bx bx-arrow-back ms-0 me-1"></i>Import Vehicles</a>
            </div>

            <div class="btn-group">
                <button type="button" class="btn btn-success" id="import">Vehicles Import</button>
            </div>
          </div>
        </div>
      </div>
    </div> --}}
    <div class="card-header py-3">
        @include('layouts.partials.nav-menu')
        <div class="row gx-2">

            <div class="col-lg-2 col-md-2 col-4 dataTables_length" >
                  <i class="bi bi-funnel"></i>
                  <select class="form-select" id="" name="search_type" aria-controls="example">
                      <option value="vehicle_no">Vehicle No.</option>
                      <option value="vehicle_model">Vehicle Model</option>
                      <option value="manufacture_year">Manufacture Year</option>
                      <option value="ownership_type">Ownership Type</option>
                      <option value="vehicle_type">Vehicle Type</option>
                      <option value="vehicle_capacity">Vehicle Capacity</option>
                      <option value="vendor_ac">Vender Account</option>
                      <option value="driver_ac">Driver Account</option>
                      <option value="created_by">Created By</option>
                  </select>
            </div>
            
            <div class="col-lg-2 col-md-2 col-8">
                <div class="position-relative">
                    <div class="position-absolute top-50 translate-middle-y search-icon px-3"><i class="bi bi-search"></i></div>
                    <input class="form-control ps-5" type="search" id="search" placeholder="Search">
                </div>
            </div>

            <div class="col-lg-1 col-md-1 col-4 dataTables_length" id="example_length">
                <i class="bi bi-funnel"></i>
                <select class="form-select sorting sorting-box" id="" style="width: 140px" name="example_length" aria-controls="example">
                    <option value="10">Show: 10</option>
                    <option value="30">Show: 30</option>
                    <option value="50">Show: 50</option>
                </select>
              </div>
            <div class="col-lg-7 col-md-7 col-12" style="margin-left: 38px">
                <div class="ms-auto float-md-end action_buttons">
                    <div class="btn-group">
                        <a href="{{route('vehicle-register.index')}}" class="btn btn-outline-secondary me-1"><i class="bx bx-arrow-back ms-0 me-1"></i>Back</a>
                    </div>
                    @if(count($data)>0)
                    <div class="btn-group">
                        <button type="button" class="btn btn-danger" id="clear_temp"><i class="bi bi-floppy-fill"></i> Clear Temp Data</button>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" id="import">Proceed Import</button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>


    <div class="card-body" id="tables">
        @include('vehicles.vehicle-register-import-table')

        <input type="hidden" name="hidden_page" id="hidden_page" value="1" />
        <input type="hidden" name="hidden_column_name" id="hidden_column_name" value="name" />
        <input type="hidden" name="hidden_sort_type" id="hidden_sort_type" value="asc" />
    </div>
    <div id="loader" style="display:none;"></div>
<div id="overlay" style="display:none;"></div>
<!--end row-->
@endsection

@section('js')
<script>


/***************** Global Delay Function *****************/
function delay(callback, ms) {
    var timer = 0;
    return function() {
        var context = this,
            args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function() {
            callback.apply(context, args);
        }, ms || 0);
    };
}

function fetch_data(page, sort_type, sort_by, query, search_type) {
    $.ajax({
        url: "?page=" + page + "&sortby=" + sort_by + "&sorttype=" + sort_type + "&query=" + query + "&search_type=" + search_type,
        success: function(data) {
            $('#tables').html(data);
            $(document).ready(function() {
                $.switcher();
            });
        },
        error: function(xhr, status, error) {
            console.error(xhr.responseText);
        }
    });
}

/************ Searching Functionality ************/
$(document).ready(function() {
    $('body').on('keyup change input', '#search', delay(function() {
        var query = $('#search').val().trim();
        var search_type = $('[name="search_type"]').val();
        var sort_by = $('.sorting').val();
        var sort_type = $('#hidden_sort_type').val();
        var page = 1; 

        if (query.length >= 2 || query.length === 0) {
            fetch_data(page, sort_type, sort_by, query, search_type);
        }
    }, 700));
});




    $('body').on('change', '.sorting', function() {
        var column_name = $(this).val();
        var order_type = $(this).data('sorting_type');
        var reverse_order = '';
        var page = 1 ? 1 : (this).attr('href').split('page=')[1];
        var query = $('#search').val() ? $('#search').val() : '';
        fetch_data(page, reverse_order, column_name, query);
    });

    $('body').on('click', '.pager a', function(event) {
        event.preventDefault();
        $('#hidden_page').val(page);
        var page = $(this).attr('href').split('page=')[1];
        var column_name = $('.sorting').val() ? $('.sorting').val() : '';
        var sort_type = $('#hidden_sort_type').val();
        var query = $('#search').val() ? $('#search').val() : '';
        $('li').removeClass('active');
        $(this).parent().addClass('active');
        fetch_data(page, sort_type, column_name, query);
    });

    $(document).on('click', '#clear_temp', function() {
            var url = `{!! route('vehicle-register-import.clear-data') !!}`;

            Swal.fire({
                title: 'Are you sure you want to delete this?',
                icon: 'warning',
                showDenyButton: true,
                showCancelButton: false,
                confirmButtonText: 'Yes',
                denyButtonText: 'No',
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    $.ajax({
                        url:url,
                        success:function(response) {
                            if (response.success === true) {
                                toastr.success(response.message, 'Success.');

                                var query = $('#search').val();

                                var sort_type   = $('#hidden_sort_type').val();

                                fetch_data(1, sort_type, 10, query);

                                // Reload the page after successful operation
                                location.reload();
                            } else {
                                toastr.error(response.message, 'Error!');
                            }
                        }
                    });
                }
            });
        });

    $(document).ready(function() {
        $(document).on('click', '.importVehicle', function (e) {
            e.preventDefault();

            // Show loader, overlay, and blur background
            $('#upload-loader').show();

            if ($('.exl_file').val() == '') {
                toastr.error("Excel file field is required.");

                // Hide loader, overlay, and remove blur in case of an error
                $('#loader').hide();
                $('#overlay').hide();
                $('body').removeClass('blur-background');

                return;
            }

            var file = $('.exl_file').val();
            var error = 0;
            var ext = file.match(/\.(.+)$/)[1];

            if (ext != 'xls' && ext != 'xlsx') {
                toastr.error('File must be type of xls & xlsx.');
                $('.exl_file').val('');
                error = 1;
            }

            const size = $(".exl_file")[0].files[0].size;

            if (size > 5e+6) {
                toastr.error('File must be less than 5 MB');
                $('.exl_file').val('');
                error = 1;
            }

            // Show loading icon or change button state
            updateButtonState('Please Wait We Are Uploading Your Data...', 'fa fa-spinner fa-spin');

            var formData = new FormData();
            formData.append('file', $('.exl_file')[0].files[0]);

            if (error == 0) {
                $.ajax({
                    url: "{{ route('vehicle-register-import.store')}}",
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (data) {
                        // Update button state back to normal
                        updateButtonState('Upload Vehicles', 'your-icon-class');

                        if (data.success === true) {
                            $('[name="excel"]').val('');
                            $('.btn-close').click();
                            toastr.success(data.message);
                            oTable.api().ajax.reload();
                        } else {
                            $('[name="excel"]').val('');
                            toastr.error(data.message);
                        }
                    },
                    error: function () {
                        // Update button state back to normal in case of an error
                        updateButtonState('Upload Vehicles', 'your-icon-class');
                    },
                    complete: function () {
                        // Hide loader, overlay, and remove blur after processing is complete
                        $('#loader').hide();
                        $('#overlay').hide();
                        $('body').removeClass('blur-background');
                    }
                });
            }
            error = 1;
        });
        function updateButtonState(text, iconClass) {
            $('.save_button_span').text(text);
            // Replace 'your-icon-class' with the class for your loading icon or processing icon
            $('.save_button i').attr('class', iconClass);
        }

        $(document).on('click', '.edit', function() {
            $('#commonModalLabel').text('Edit Vehicle');
            $('.save_button_span').text('Update Vehicle');
            $('.save_button').removeClass('importVehicle');
            $('.save_button').addClass('editVehicle');
            var id = $(this).data('id');
            var url = `{!! route('vehicle-register-import.edit', ':id') !!}`.replace(':id', id);

            $.ajax({
                url:url,
                success:function(data) {
                    $('#commonModal').find('.modal-body').html('');
                    $('#commonModal').find('.modal-body').html(data);
                }
            });
        });

        $(document).on('click','.editVehicle', function(e) {
            $('.edit_product').submit();
        });



        $("body").on("click", '.deleteDataCustom', function(event) {
            dataString = {
                "id": $(this).data('id')
            };
            var UrlValue = $(this).data('url');
            var btn = $(this);

            Swal.fire({
                title: 'Are you sure you want to delete this?',
                icon: 'warning',
                showDenyButton: true,
                showCancelButton: false,
                confirmButtonText: 'Yes',
                denyButtonText: 'No',
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    $.ajax({
                        url: UrlValue,
                        method: 'post',
                        data: {
                            "_token": $('meta[name="csrf-token"]').attr('content'),
                            "id": $(this).data('id')
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function(xhr) {
                            // xhr.overrideMimeType( "text/plain; charset=x-user-defined" );
                        },
                        success: function(response) {
                            if (response.success) {
                                var ErroMsg = $(this).printErrorMsg(response
                                    .message);

                                $(this).Toastshow('success', ErroMsg);

                                var page        = $('.pager').find('.active a').attr('href') == undefined ? 1 : $('.pager').find('.active a').attr('href').split('page=')[1];
                                var column_name = $('.sorting').val()?$('.sorting').val():'';
                                var sort_type   = $('#hidden_sort_type').val();
                                var query       = $('#search').val()?$('#search').val():'';

                                fetch_data(page, sort_type, column_name, query);

                                location.reload();
                            }else {
                                var ErroMsg = $(this).printErrorMsg(response
                                    .message);
                                $(this).Toastshow('error', ErroMsg);
                            }
                        },
                        error: function(data) {
                            console.log("error ", data);
                        }
                    });
                }
            });
        });

        // $(document).on('click', '#import', function() {
        //     $.ajax({
        //         url:"{{ route('vehicle-register-import.proceed')}}",
        //         type:'POST',
        //         data: {},
        //         contentType: false,
        //         processData: false,
        //         success:function(data){
        //             if (data.success === true) {
        //                 toastr.success(data.message);
        //                 oTable.api().ajax.reload();
        //             } else {
        //                 toastr.error(data.message);
        //                 oTable.api().ajax.reload();
        //             }
        //         }
        //     });
        // });
        $(document).on('click', '#import', function() {
        // Reference to the button
        var importButton = $(this);

        // Change the text of the button to "Uploading Data..."
        importButton.text('Uploading Data...');

        $.ajax({
            url: "{{ route('vehicle-register-import.proceed')}}",
            type: 'POST',
            data: {},
            contentType: false,
            processData: false,
            success: function(data) {
                if (data.success === true) {
                    toastr.success(data.message);
                    location.reload();
                } else {
                    toastr.error(data.message);
                    oTable.api().ajax.reload();
                }
            },
            error: function() {
                // In case of an error, change the text to "Proceed Data"
                importButton.text('Proceed Data');
            }
        });
    });

    });
</script>
@endsection
