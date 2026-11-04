<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Support\SimpleXlsx;
use App\Support\VendorProductImport;
use Illuminate\Http\Request;

/** Sellers add many products at once from an Excel / CSV sheet. Good rows wait for admin approval. */
class ProductImportController extends Controller
{
    public function create()
    {
        return view('vendor.products.import', ['maxRows' => VendorProductImport::MAX_ROWS]);
    }

    public function template()
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl');
        VendorProductImport::writeTemplate($path);

        return response()->download($path, 'product-import-template.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:5120', 'mimes:xlsx,csv,txt']], [
            'file.required' => 'Choose the filled sheet to upload.',
            'file.max' => 'The file can be up to 5 MB.',
            'file.mimes' => 'Upload the .xlsx template (or a .csv file).',
        ]);
        $file = $request->file('file');

        try {
            $rows = SimpleXlsx::read($file->getRealPath(), $file->getClientOriginalExtension() ?: 'xlsx');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        @set_time_limit(120);
        $result = VendorProductImport::run(auth()->user()->vendorAccount(), $rows);
        if ($result['fatal']) {
            return back()->withErrors(['file' => $result['fatal']]);
        }

        return back()->with('import_result', $result);
    }
}
