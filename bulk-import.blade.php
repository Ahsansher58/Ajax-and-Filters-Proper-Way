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

});
</script>
@endsection
