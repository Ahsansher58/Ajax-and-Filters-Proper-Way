<?php

namespace App\Http\Controllers\VehicleRegister;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VehicleRegister;
use App\Models\IpdAdmission;
use App\Models\Country;
use App\Models\AccountTitle;
use App\Models\SymptomClassification;
use App\Models\Symptom;
use App\Models\EnquirySource;
use App\Models\BodyVital;
use App\Models\IpdBodyVitalHistory;
use App\Models\IpdSymptomHistory;
use App\Models\IpdBedHistory;
use App\Models\BedGroup;
use App\Models\Bed;
use App\Models\VehicleRegisterUpload;
use App\Models\VehicleType;
use App\Models\IpdConsultantHistory;
use App\Models\MaritalStatus;
use App\Models\BloodGroup;
use App\Models\AccountTransaction;
use App\Models\AcReceipt;
use App\Models\AcReceiptDetail;
use App\Models\VoucherType;
use App\Models\DischargeType;
use App\Models\AccountingGroup;
use App\Models\OpdBookings;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceDetail;
use App\Models\VoucherCollection;
use App\Models\VoucherCollectionDetail;
use App\Models\IpdDischarge;
use App\Models\IpdDischargeTypeSetting;
use App\Models\IpdDischargeDetail;
use App\Models\SaleInvoiceBatch;
use App\Models\DepartmentCLearanceSetting;
use App\Models\Sales\SaleReturns;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\LabBillingReport;
use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;
use App\Exports\IpdBillingStatementExport;
use App\Traits\TransactionSummeryTrait;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Validator;
use App\Models\Account;
use App\Models\AccountAddress;
use App\Models\AccountBank;
use App\Models\AccountLicense;
use App\Models\MasterType;
use App\Models\MasterCategory;
use App\Models\TaxRegisterCategory;
use App\Models\AccountPayment;
use App\Models\AccountAttachment;
use App\Models\AccountImage;
use App\Models\CompanyAddress;
use App\Models\AccountContact;
use App\Models\Gender;
use App\Models\User;
use App\Models\AccountRelativesDetail;
use App\Models\LicenseType;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\RelationType;
use App\Models\StudentHouse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AccountsExport;
use App\Models\AccountSettlementType;
use Illuminate\Validation\Rule;
use App\Imports\VehicleRegisterExcelImport;


class VehicleRegisterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $vehicles = VehicleRegister::leftJoin('vehicle_types', 'vehicle_registers.vehicle_type', '=', 'vehicle_types.id')
            ->leftJoin('accounts', 'vehicle_registers.driver_ac_id', '=', 'accounts.id')
            ->leftJoin('accounts as vendors', 'vehicle_registers.vendor_ac_id', '=', 'vendors.id')
            ->leftJoin('account_contacts', 'account_contacts.account_id', '=', 'accounts.id')
            ->leftJoin('account_contacts as vendor_contacts', 'vendor_contacts.account_id', '=', 'accounts.id')
            ->select(
                'vehicle_types.id',
                'vehicle_registers.id as vehicle_register_id',
                'accounts.id as driver_account_id',
                'vendors.id as vendor_account_id',
                'vehicle_registers.driver_ac_id',
                'vehicle_registers.vendor_ac_id',
                'account_contacts.account_id as driver_contact_account_id',
                'vehicle_types.name as vehicle_type_name',
                'vehicle_registers.vehicle_no as vehicle_no',
                'vehicle_registers.vehicle_model as vehicle_model',
                'vehicle_registers.manufacture_year as manufacture_year',
                'vehicle_registers.ownership_type as ownership_type',
                'vehicle_registers.vehicle_capacity as vehicle_capacity',
                'accounts.name as driver_account_name',
                'accounts.code as driver_account_code',
                'account_contacts.phone_no as driver_phone_no',
                'account_contacts.whatsapp_no as driver_whatsapp_no',
                'vendors.name as vendor_account_name',
                'vendors.code as vendor_account_code',
                'vendor_contacts.phone_no as vendor_phone_no',
                'vendor_contacts.whatsapp_no as vendor_whatsapp_no' ,
                'vehicle_registers.note as note' , 
                'vehicle_registers.status as status' ,

        )
        ->orderBy('accounts.name', 'ASC');

        if ($request->ajax()) {
            $sort_by      = $request->get('sortby') ?? 10;
            $sort_type    = $request->get('sorttype');
            $search_query = $request->get('query');

            $vehicles = $vehicles->when(!empty($search_query), function ($query) use ($search_query) {
                return $query->where('accounts.name', 'like', '%'.$search_query.'%')
                ->orWhere('vendors.name', 'like', '%'.$search_query.'%')
                ->orWhere('vehicle_registers.vehicle_model', 'like', '%'.$search_query.'%');
            })
            ->paginate($sort_by);

            return view('vehicles.table', compact('vehicles'));
        }
        else
        {
            $vehicles = $vehicles->paginate(10);

            return view('vehicles.index', compact('vehicles'));
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $vehicles_type = VehicleType::select(
            'vehicle_types.name as vehicle_type_name',
            'vehicle_types.id as vehicle_type_id'
        )->get();

        return view('vehicles.create', compact('vehicles_type'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       $validator = \Validator::make($request->all(), [
            'vehicle_no'        => 'required',
            'vehicle_model'     => 'nullable',
            'manufacture_year'  => 'nullable',
            'ownership_type'    => 'required',
            'vehicle_type'      => 'required',
            'vehicle_capacity'  => 'nullable',
            'note'              => 'nullable',
            'vendor_ac_id'      => 'nullable', 
            'driver_ac_id'      => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first())->withInput();
        }

        $data = $request->except([
            '_token',
            '_method',
        ]);

        $vehicle_data = [
            'vehicle_no'        => $request->vehicle_no,
            'vehicle_model'     => $request->vehicle_model,
            'manufacture_year'  => $request->manufacture_year,
            'ownership_type'    => $request->ownership_type,
            'vehicle_type'      => $request->vehicle_type,
            'vehicle_capacity'  => $request->vehicle_capacity,
            'note'              => $request->note,
            'vendor_ac_id'      => $request->vendor_ac_id,
            'driver_ac_id'      => $request->driver_ac_id,
        ];
        

        $vehicle_data = VehicleRegister::create($vehicle_data);


        return redirect()->route('vehicle-register.index')->with('success','Vehicle was created successfully');

    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
  public function edit($id)
    {
        $driver = $vendor = '';

        // Find the vehicle registration record by ID
        $vehicle_register = VehicleRegister::findOrFail($id);

        // Retrieve the vehicle types, specifically filtering by the vehicle type ID of the registration
        $vehicles_type = VehicleType::select(
            'vehicle_types.name as vehicle_type_name',
            'vehicle_types.id as vehicle_type_id'
        )
        ->get();

        // Fetch driver details using Account model based on driver_ac_id from vehicle registration
        $driver_id = $vehicle_register->driver_ac_id;
        $driver = Account::getAccount([
            'account_types.type_code' => 'DRIVER',
            'accounts.id' => $driver_id,
        ]);
        if (isset($driver)) {
            // Construct a full name with optional details if available
            $driver->full_name = $driver->name .
                ($driver->code != '' ? ', ' . $driver->code : '') .
                ($driver->phone_no != '' ? ', ' . $driver->phone_no : '');
        }

        // Fetch vendor details using Account model based on vendor_ac_id from vehicle registration
        $vendor_id = $vehicle_register->vendor_ac_id;
        $vendor = Account::getAccount([
            'account_types.type_code' => 'VENDOR',
            'accounts.id' => $vendor_id,
        ]);
        if (isset($vendor)) {
            // Construct a full name with optional details if available
            $vendor->full_name = $vendor->name .
                ($vendor->code != '' ? ', ' . $vendor->code : '') .
                ($vendor->phone_no != '' ? ', ' . $vendor->phone_no : '');
        }


        // Return the view 'vehicles.edit' with compacted variables
        return view('vehicles.edit', compact('driver', 'vendor', 'vehicle_register', 'vehicles_type'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   public function update(Request $request, $id)
{
    $validator = \Validator::make($request->all(), [
        'vehicle_no'        => 'required',
        'vehicle_model'     => 'required',
        'manufacture_year'  => 'required',
        'ownership_type'    => 'required',
        'vehicle_type'      => 'nullable',
        'vehicle_capacity'  => 'required',
        'note'              => 'nullable',
        'vendor_ac_id'      => 'required', 
        'driver_ac_id'      => 'required',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->with('error', $validator->getMessageBag()->first())->withInput();
    }

    // Fetch the record to update
    $vehicle = VehicleRegister::find($id);

    if (!$vehicle) {
        return redirect()->back()->with('error', 'Vehicle not found.');
    }

    // Update the vehicle data
    $vehicle->update([
        'vehicle_no'        => $request->vehicle_no,
        'vehicle_model'     => $request->vehicle_model,
        'manufacture_year'  => $request->manufacture_year,
        'ownership_type'    => $request->ownership_type,
        'vehicle_type'      => $request->vehicle_type,
        'vehicle_capacity'  => $request->vehicle_capacity,
        'note'              => $request->note,
        'vendor_ac_id'      => $request->vendor_ac_id,
        'driver_ac_id'      => $request->driver_ac_id,
    ]);

    return redirect()->route('vehicle-register.create')->with('success', 'Vehicle was updated successfully');
}


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   public function destroy($id)
{
    $vehicle_register = VehicleRegister::findOrFail($id);
    $vehicle_register->delete();

    return response()->json([
        'success' => true,
        'message' => 'Vehicle Register Deleted successfully',
        'data' => [
            'redirect' => '', 
        ]
    ]);
}
   
  public function importVehiclesForm(Request $request)
{
    $query = VehicleRegisterUpload::query(); // Start a new query

    if ($request->ajax()) {
        $sort_by      = $request->get('sortby') ?? 10;
        $sort_type    = $request->get('sorttype');
        $search_query = $request->get('query');
        $search_type = $request->get('search_type');

        // Apply search conditions based on search_type
        $query = $query->when(!empty($search_query) && !empty($search_type),
            function ($query) use ($search_query, $search_type) {
                if ($search_type === 'vehicle_no') {
                    $query->where('vehicle_register_uploads.vehicle_no', 'like', '%' . $search_query . '%');
                } elseif ($search_type === 'vehicle_model') {
                    $query->where('vehicle_register_uploads.vehicle_model', 'like', '%' . $search_query . '%');
                } elseif ($search_type === 'manufacture_year') {
                    $query->where('vehicle_register_uploads.manufacture_year', 'like', '%' . $search_query . '%');
                } elseif ($search_type === 'ownership_type') {
                    $query->where('vehicle_register_uploads.ownership_type', 'like', '%' . $search_query . '%');
                } elseif ($search_type === 'vehicle_type') {
                    $query->where('vehicle_register_uploads.vehicle_type', 'like', '%' . $search_query . '%');
                } elseif ($search_type === 'vehicle_capacity') {
                    $query->where('vehicle_register_uploads.vehicle_capacity', 'like', '%' . $search_query . '%');
                } elseif ($search_type === 'vendor_ac') {
                    $query->where('vehicle_register_uploads.vendor_ac', 'like', '%' . $search_query . '%');
                } elseif ($search_type === 'driver_ac') {
                    $query->where('vehicle_register_uploads.driver_ac', 'like', '%' . $search_query . '%');
                } elseif ($search_type === 'created_by') {
                    $query->where('vehicle_register_uploads.created_by', 'like', '%' . $search_query . '%');
                }
        });

        $data = $query->paginate($sort_by);

        return view('vehicles.vehicle-register-import-table', compact('data'))->render();
    } else {
        $data = $query->paginate(10);
        return view('vehicles.bulk-import', compact('data'));
    }
}

      








}
