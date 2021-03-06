<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\ImportCustomers;
use App\Imports\CustomerImport;
use Maatwebsite\Excel\Facades\Excel;

class DataTableController extends Controller
{
    public function importExport()
    {
       return view('import.import');
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function export()
    {
        return Excel::download(new ExportUsers, 'users.xlsx');
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function import(Request $request)
    {
      $request->validate([
        'import_file' => 'required'
      ]);

      return $request;

        //Excel::import(new ImportCustomers, request()->file('import_file'));
        /*import with collection */

        //Excel::queueImport(new CustomerImport, request()->file('import_file'));

        //return back();
    }
}
