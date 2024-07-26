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






    ////////////////////////////////////////////Drivers////////////////////////////////////////////////

     public function createDriver()
    {
        $vehicles        =VehicleRegister::select('vehicle_registers.driver_ac_id as driver_ac_id')->get();
        $country         = Country::select(['id','name'])->get();
        $account_title   = AccountTitle::where('status', '1')->where('name', '<>', 'M/s.')->with('gender')->get();
        $company_address = \Session::get('company_data')['companies_addresses'];

        $country_id = $company_address['country_id'] ?? '';
        $state_id   = $company_address['state_id'] ?? '';
        $city_id    = $company_address['city_id'] ?? '';

        return view('accounts.create-driver', compact(
            'country',
            'account_title',
            'country_id',
            'state_id',
            'city_id' ,
            'vehicles',
        ));
    }

    public function storeDriver(Request $request)
    {
        
        $unique_register_no = $request->unique_register_no;
        $validator = Validator::make($request->all(), [

            'name'=> [
                'required',
            ],
            'country_id' => 'required',
        ], [
            'required' => 'The :attribute field is required.',
        ]);
        if($unique_register_no!=''){
            $validator = Validator::make($request->all(), [
                'unique_register_no'=>[
                function ($attribute, $value, $fail) use ($request) {
                    $existingAbhaNo = Account::where('unique_register_no', $request->unique_register_no)
                        ->first();
                    if ($existingAbhaNo) {
                        $fail('This Ayushman Bharat Health Account No already exists.');
                    }
                }],]);
        }


        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'data'    => []
                ]);
            }
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $account_title = AccountTitle::select(['name','id'])->find($request->account_title_id);

        $type = MasterType::where('type_code', 'DRIVER')->first();
        $accounting_group = AccountingGroup::where('code','SUNDRY_DEBTORS')->first();
        $company_id             = Auth::user()->company_id ?? '';
        $accounting_category    = MasterCategory::select('id','category_name')->where('is_default',1)->first();
        $tax_register_cagtegory = TaxRegisterCategory::select('id','name')->where('is_default',1)->first();
        $sattlement_type        = AccountSettlementType::select('id','name')->where('is_default',1)->first();


        $account = Account::create([
            'name'                     => $request->name,
            'account_title_id'         => $request->account_title_id,
            'account_type_id'          => $type->id ?? '',
            'created_by'               => \Auth::user()->id ?? '',
            'accounting_group_id'      => $accounting_group->id,
            'company_id'               => $company_id,
            'account_category_id'      => $accounting_category->id,
            'tax_register_category_id' => $tax_register_cagtegory->id,
            'settlement_type'          => $sattlement_type->id,
            'unique_register_no'       => $request->unique_register_no,
        ]);

        $account_type = MasterType::find(1);
        $count  = 1;
        $prefix = "DVR";
        if($account_type) {
            $count  = $account_type->count != '' ? $account_type->count + 1 : 1;
            $prefix = $account_type->prefix != '' ? $account_type->prefix : "DVR";

            $account_type->update(['count' => $count]);
        }

        $code = $this->generateCode($count, $prefix);

        $account->update(['code' => $code]);

        $address = AccountAddress::create([
            'account_id'    => $account->id,
            'country_id'    => $request->country_id,
            'state_id'      => $request->state_id,
            'city_id'       => $request->city_id,
            'address_line1' => $request->address,
            'post_code'     => $request->postal_code,
            'is_default'    => '1'
        ]);

        $contact = AccountContact::create([
            'account_id'        => $account->id,
            'phone_no'          => $request->phone_no,
            'whatsapp_no'       => $request->whatsapp_no ?? '',
            'email'             => $request->email ?? '',
            'name'              => $request->name ?? '',
            'country_id'        => $request->country_id,
            'state_id'          => $request->state_id,
            'city_id'           => $request->city_id,
            'postal_code'       => $request->postal_code,
            'address'           => $request->address,
        ]);

        $response = [
            'id'   => $account->id,
            'name' => ($account_title->name ?? '') . ' ' . $request->name . ' - (' . $account->code . '), ' . ', ' . ', ' . $contact->phone_no
        ];

        $data = \Session::get('billing_products_cart') ?? [];
        $data['driver-id'] = $account->id;

        \Session::put('billing_products_cart', $data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Driver has been created successfully',
                'data'    => $response
            ]);
        }
        return redirect()->route('accounts.index')
            ->with('success','Driver has been created successfully.');
    }

      public function searchDrivers(Request $request)
    {
        $searchTerm = $request->search_text;
        $results    = Account::select([
            'accounts.id',
            'accounts.code',
            \DB::raw("
                CONCAT(
                    COALESCE(account_titles.name, ''),
                    CASE WHEN account_titles.name IS NOT NULL AND accounts.name IS NOT NULL THEN ' ' ELSE '' END,
                    COALESCE(accounts.name, '')
                ) AS name
                "),
            \DB::raw("account_contacts.phone_no as phone_no"),
        ])
            ->leftJoin('account_contacts', 'account_contacts.account_id', '=', 'accounts.id')
            ->leftJoin('account_titles', 'account_titles.id', '=', 'accounts.account_title_id')
            ->leftjoin('account_types', 'account_types.id', 'accounts.account_type_id')
            ->where('account_types.type_code', 'DRIVER')
            ->where(function ($query) use ($searchTerm) {
                $query->where('accounts.name', 'LIKE', $searchTerm . '%')
                    ->orWhere('account_contacts.phone_no', 'LIKE', $searchTerm . '%')
                    ->orWhere('accounts.code', 'LIKE', $searchTerm . '%');
            })
            ->limit(15)
            ->get();

        return response()->json(['result' => $results, 'status' => true]);
    }


  public function EditDriver($id)
    {
        $driver = Account::with('first_account_address', 'account_contact')->where([
            'id' => $id
        ])->first();


        $country         = Country::select(['id','name'])->get();
        $account_title   = AccountTitle::where('status', '1')->where('code', '<>', 'M/s.')->get();
        $company_address = \Session::get('company_data')['companies_addresses'];

        $country_id = $company_address['country_id'] ?? '';
        $state_id   = $company_address['state_id'] ?? '';
        $city_id    = $company_address['city_id'] ?? '';

       

        return view('accounts.edit-driver',compact(
            'driver',
            'country',
            'account_title',
            'country_id',
            'state_id',
            'city_id'
        ));

        
    }
    public function UpdateDriver(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'phone_no'   => 'required|unique:account_contacts,phone_no,'.$request->name.',name,id,'.$id,
            'name'       => 'required|unique:account_contacts,name,'.$request->phone_no.',phone_no,id,'.$id,
            'country_id' => 'required',
        ], [
            'unique' => 'This Name and Phone No are already taken.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->getMessageBag()->first(),
                    'data'    => []
                ]);
            }
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $account_title = AccountTitle::select(['name','id'])->find($request->account_title_id);

        $account = Account::where('id', $id)->first();
        $account->update([
            'name'                      => $request->name,
            'account_title_id'          => $request->account_title_id,
            'unique_register_no'        => $request->unique_register_no,
        ]);

        $address = AccountAddress::where(['account_id' => $id, 'is_default' => '1'])
            ->update([
                'country_id'    => $request->country_id,
                'state_id'      => $request->state_id,
                'city_id'       => $request->city_id,
                'address_line1' => $request->address,
                'post_code'     => $request->postal_code,
            ]);



        $contact = AccountContact::where(['account_id' => $id])->first();
        $contact->update([
                'phone_no'          => $request->phone_no,
                'whatsapp_no'       => $request->whatsapp_no ?? '',
                'email'             => $request->email ?? '',
                'name'              => $request->name ?? '',
                'country_id'        => $request->country_id,
                'state_id'          => $request->state_id,
                'city_id'           => $request->city_id,
                'postal_code'       => $request->postal_code,
                'address'           => $request->address,
            ]);

        

        $response = [
            'id'   => $id,
            'name' => ($account_title->name ?? '') . ' ' . $request->name . ' - (' . $account->code . '), '  . ', ' . ', ' . $contact->phone_no
        ];

        $data = \Session::get('billing_products_cart') ?? [];
        $data['driver_id'] = $id;

        \Session::put('billing_products_cart', $data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Driver has been updated successfully',
                'data'    => $response
            ]);
        }
        return redirect()->route('accounts.index')
            ->with('success','Driver has been updated successfully.');
    }




    ////////////////////////////////////////////Vendors////////////////////////////////////////////////
    public function createVendor()
    {
        $country         = Country::select(['id','name'])->get();
        $account_title   = AccountTitle::where('status', '1')->where('code', 'like', '%Ven%')->get();
        $company_address = \Session::get('company_data')['companies_addresses'];

        $country_id = $company_address['country_id'] ?? '';
        $state_id   = $company_address['state_id'] ?? '';
        $city_id    = $company_address['city_id'] ?? '';

        return view('accounts.create-vendor', compact('country','account_title','country_id','state_id','city_id'));
    }

    public function storeVendor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'phone_no'   => [
            //     'required',
            //     function ($attribute, $value, $fail) use ($request) {
            //         $existingContact = AccountContact::where('name', $request->name)
            //             ->where('phone_no', $value)
            //             ->first();
            //         if ($existingContact) {
            //             $fail('This Name and Phone No combination already exists.');
            //         }
            //     }
            // ],
            'name'       => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $existingContact = AccountContact::where('phone_no', $request->phone_no)
                        ->where('name', $value)
                        ->first();
                    if ($existingContact) {
                        $fail('This Name and Phone No combination already exists.');
                    }
                }
            ],
        ], [
            'required' => 'The :attribute field is required.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'data'    => []
                ]);
            }
            return redirect()->back()->with('error', $validator->errors()->first());
        }


        $type                = MasterType::where('type_code', 'VENDOR')->first();
        $accounting_group    = AccountingGroup::where('name','Sundry Creditors')->first();
        $accounting_category = MasterCategory::select('id','category_name')->where('is_default',1)->first();
        $taxRegisterCategory = TaxRegisterCategory::select('id','name')->where('is_default',1)->first();
        $sattlement          = AccountSettlementType::select('id','name')->where('is_default',1)->first();

        $account = Account::create([
            'name'                     => $request->name,
            'account_title_id'         => $request->account_title_id,
            'account_type_id'          => $type->id ?? '',
            'created_by'               => Auth::user()->id ?? '',
            'accounting_group_id'      => $accounting_group->id,
            'account_category_id'      => $accounting_category->id,
            'tax_register_category_id' => $taxRegisterCategory->id,
            'settlement_type'          => $sattlement->id,
        ]);

        $account_type = MasterType::where('type_code','VENDOR')->first();
        $count  = 1;
        $prefix = "DRS";

        if($account_type) {
            $count  = $account_type->count != '' ? $account_type->count + 1 : 1;
            $prefix = $account_type->prefix != '' ? $account_type->prefix : "VEN";

            $account_type->update(['count' => $count]);
        }

        $code = $this->generateCode($count, $prefix);

        $account->update(['code' => $code]);

        $address = AccountAddress::create([
            'account_id' => $account->id,
            'country_id' => $request->country_id,
            'state_id'   => $request->state_id,
            'is_default' => '1'
        ]);

        $contact = AccountContact::create([
            'account_id'     => $account->id,
            'phone_no'       => $request->phone_no ?? '',
            'name'           => $request->name ?? '',
            'country_id'     => $request->country_id,
            'organization'   => $request->organization,
            'qualifications' => $request->qualifications,
            'city_id'        => $request->city_id,
        ]);


        $account_title = AccountTitle::select(['name','id'])->find($request->account_title_id);

        $response = [
            'id' => $account->id,
            'name' => ($account_title->name ?? '') . ' ' . $request->name . ' - ' . $account->code . ', ' . $request->phone_no
        ];

        $data = \Session::get('billing_products_cart') ?? [];
        $data['vendor_id'] = $account->id;

        \Session::put('billing_products_cart', $data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor has been created successfully',
                'data'    => $response
            ]);
        }
        return redirect()->route('accounts.index')
            ->with('success','Vendor has been created successfully.');
    }


    public function searchVendors(Request $request)
    {
        $searchTerm = $request->search_text;
        $results    = Account::select([
            'accounts.id',
            'accounts.code',
            \DB::raw("
                CONCAT(
                    COALESCE(account_titles.name, ''),
                    CASE WHEN account_titles.name IS NOT NULL AND accounts.name IS NOT NULL THEN ' ' ELSE '' END,
                    COALESCE(accounts.name, '')
                ) AS name
                "),
            \DB::raw("account_contacts.phone_no as phone_no"),
        ])
            ->leftJoin('account_contacts', 'account_contacts.account_id', '=', 'accounts.id')
            ->leftJoin('account_titles', 'account_titles.id', '=', 'accounts.account_title_id')
            ->leftjoin('account_types', 'account_types.id', 'accounts.account_type_id')
            ->where('account_types.type_code', 'VENDOR')
            ->where(function ($query) use ($searchTerm) {
                $query->where('accounts.name', 'LIKE', $searchTerm . '%')
                    ->orWhere('account_contacts.phone_no', 'LIKE', $searchTerm . '%')
                    ->orWhere('accounts.code', 'LIKE', $searchTerm . '%');
            })
            ->limit(15)
            ->get();

        return response()->json(['result' => $results, 'status' => true]);
    }

    public function EditVendor($id)
    {
        $vendor = Account::with('first_account_address', 'account_contact')->where([
            'id' => $id
        ])->first();

        $country         = Country::select(['id','name'])->get();
        $account_title   = AccountTitle::where('status', '1')->where('code', '<>', 'M/s.')->with('gender')->get();
        $company_address = \Session::get('company_data')['companies_addresses'];

        $country_id = $company_address['country_id'] ?? '';
        $state_id   = $company_address['state_id'] ?? '';
        $city_id    = $company_address['city_id'] ?? '';

        return view('accounts.edit-vendor',compact(
            'vendor',
            'country',
            'account_title',
            'country_id',
            'state_id',
            'city_id'
        ));
    }

     public function UpdateVendor(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'phone_no'   => 'required|unique:account_contacts,phone_no,'.$request->name.',name,id,'.$id,
            'name'       => 'required|unique:account_contacts,name,'.$request->phone_no.',phone_no,id,'.$id,
            'country_id' => 'required',
        ], [
            'unique' => 'This Name and Phone No are already taken.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->getMessageBag()->first(),
                    'data'    => []
                ]);
            }
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $account_title = AccountTitle::select(['name','id'])->find($request->account_title_id);

        $account = Account::where('id', $id)->first();
        $account->update([
            'name'                      => $request->name,
            'account_title_id'          => $request->account_title_id,
            'unique_register_no'        => $request->unique_register_no,
        ]);

        $address = AccountAddress::where(['account_id' => $id, 'is_default' => '1'])
            ->update([
                'country_id'    => $request->country_id,
                'state_id'      => $request->state_id,
                'address_line1' => $request->address,
                'post_code'     => $request->postal_code,
            ]);



        $contact = AccountContact::where(['account_id' => $id])->first();
        $contact->update([
                'phone_no'          => $request->phone_no,
                'whatsapp_no'       => $request->whatsapp_no ?? '',
                'email'             => $request->email ?? '',
                'name'              => $request->name ?? '',
                'country_id'        => $request->country_id,
                'state_id'          => $request->state_id,
                'city_id'           => $request->city_id,
                'postal_code'       => $request->postal_code,
                'address'           => $request->address,
            ]);

        

        $response = [
            'id'   => $id,
            'name' => ($account_title->name ?? '') . ' ' . $request->name . ' - (' . $account->code . '), '  . ', ' . ', ' . $contact->phone_no
        ];

        $data = \Session::get('billing_products_cart') ?? [];
        $data['driver_id'] = $id;

        \Session::put('billing_products_cart', $data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor has been updated successfully',
                'data'    => $response
            ]);
        }
        return redirect()->route('accounts.index')
            ->with('success','Vendor has been updated successfully.');
    }





    public function changeStatus(Request $request)
    {
        if ($request->ajax()) {
            $data   = array('status' => $request->status );
            $update = VehicleRegister::where('id', '=', $request->id)->update($data);

            if($update){
                return response()->json([
                    'success'=>true,
                    'message'=>['Vehicle Register status successfully change'],
                    'data'=>[
                       'redirect'=>'/users/',
                       'reload'=>true,
                    ]
                ]);
            } else {
                return response()->json([
                   'success'=>false,
                   'message'=>['Error for change status'],
                   'data'=>[
                       'redirect'=>'',
                   ]
                ]);
            }
        }
    }




    public function importForm(Request $request)
    {
        return view('vehicles.import');
    }

    public function editVehicles(Request $request, $id)
    {
        $data = VehicleRegisterUpload::find($id);

        return view('vehicles.edit-modal', compact('data'));
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

       public function clearData()
    {
        VehicleRegisterUpload::truncate();

        return response()->json([
            'success' => true,
            'message' => 'Temp data cleared successfully.'
        ]);
    }


      public function importBulkVehicles(Request $request)
    {
        ini_set('max_execution_time', 500);

        $validator = \Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The file must be latest version of excel after 2003. File of type: xlsx .',
                    'data'    => []
                ]);
            }

            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }
        
        $records  = [];
        $data     = Excel::toArray(new VehicleRegisterExcelImport, $request->file('file'));

        $data     = isset($data[0]) ? $data[0] : [];
        $headings = [];

        if (isset($data[0])) {
            $headings = $data[0];
            unset($data[0]);
        }

        if (!empty($data)) {
            // Truncate table.
            \DB::table('vehicle_register_uploads')->truncate();

            foreach ($data as $key => $record) {

                if (empty($record[array_search("Vehicle Name", $headings)])) {
                    continue;
                }

                // Populate the $records array
                $records[$key] = [
                    'vehicle_no'       => $record[array_search("Vehicle No.", $headings)],
                    'vehicle_model'    => $record[array_search("Vehice Model", $headings)],
                    'manufacture_year' => $record[array_search("Manufacture Year", $headings)],
                    'ownership_type'   => $record[array_search("Ownership Type", $headings)],
                    'vehicle_type'     => $record[array_search("Vehicle Type", $headings)],
                    'vehicle_capacity' => $record[array_search("Vehicle Capacity", $headings)],
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            VehicleRegisterUpload::insert($records);

            return response()->json([
                'success' => true,
                'message' => 'Vehicles are uploaded successfully.',
                'data'    => []
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Vehicles data not found!',
                'data'    => []
            ]);
        }
    }

    public function importDestroy(Request $request)
    {
        VehicleRegisterUpload::find($request->id)->delete();

        $Redirect = 'vehicle-register-import.create';

        return response()->json([
            'success' => true,
            'message' => ['Deleted successfully'],
            'data'    => [
                'redirect' => $Redirect,
            ]
        ]);

    }

    public function updateVehicles(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'vehicle_no'       => 'required',
            'vehicle_model'    => 'required',
            'manufacture_year' => 'required',
            'ownership_type'   => 'required',
            'vehicle_type'     => 'required',
            'vehicle_capacity' => 'required',
            'vendor_ac'     => 'required',
            'driver_ac'     => 'required',
            'note'             => 'required',
            'created_by'       => 'required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->getMessageBag()->first(),
                    'data'    => []
                ]);
            }

            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $data = $request->except(['_token']);

        VehicleRegisterUpload::whereId($id)->update($data);

        return redirect()->back()
            ->with('success','vehicle updated successfully');
    }








}
