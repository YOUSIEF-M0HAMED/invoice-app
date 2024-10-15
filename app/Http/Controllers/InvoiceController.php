<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function get_all_invoices()
    {
        $invoices = Invoice::with('customer')->orderBy('id','desc')->get();

        return response()->json(['invoices' => $invoices], 200);
    }
     public function search_invoice(Request $request)
    {
        $search = $request->get('s');

        if($search !== null){
        $invoices = Invoice::with('customer')
            ->where('id','LIKE','%'.$search.'%')
            ->orWhere('number','LIKE','%'.$search.'%')
            ->orWhere('date','LIKE','%'.$search.'%')
            ->orWhere('due_date','LIKE','%'.$search.'%')
            ->orderBy('id','desc')->get();
        return response()->json(['invoices' => $invoices], 200);
        }else{
            return $this->get_all_invoices();
        }

    }
    public function create_invoice(Request $request)
{
    $counter = Counter::where('key', 'invoice')->first();

    if (!$counter) {
        return response()->json(['error' => 'Counter not found'], 404);
    }

    $invoice = Invoice::orderBy('id', 'DESC')->first();

    if ($invoice) {
        $invoiceId = (int)$invoice->id;
        $counters = (int)$counter->value + $invoiceId;
    } else {
        $counters = (int)$counter->value;
    }

    $formData = [
        'numbers' => $counter->prefix . (string)$counters,
        'customer_id' => null,
        'date' => date('Y-m-d'),
        'due_date' => null,
        'reference' => null,
        'discount' => 0,
        'term_and_conditions' => 'Default Term and Conditions',
        'items' => [
            'product_id' => null,
            'product' => null,
            'unit_price' => 0,
            'quantity' => 1,
        ],
    ];

    return response()->json($formData);
}



public function add_invoice(Request $request)
{
    $validated = $request->validate([
            'sub_total' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'customer_id' => 'required|exists:customers,id',
            'number' => 'required|string|max:255',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'discount' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:255',
            'terms_and_conditions' => 'nullable|string',
            'invoice_items' => 'required|json',
    ]);

    DB::beginTransaction();

    try {
        $invoiceItems = json_decode($validated['invoice_items'], associative: true);

        $invoiceData = [
            'sub_total' => $validated['sub_total'],
            'total' => $validated['total'],
            'customer_id' => $validated['customer_id'],
            'number' => $validated['number'],
            'date' => $validated['date'],
            'due_date' => $validated['due_date'],
            'discount' => $validated['discount'],
            'reference' => $validated['reference'],
            'terms_and_conditions' => $validated['terms_and_conditions'],
        ];

        $invoice = Invoice::create($invoiceData);

        foreach ($invoiceItems as $item) {
            $itemData = [
                'product_id' => $item['id'],
                'invoice_id' => $invoice->id,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ];

            InvoiceItem::create($itemData);
        }

        DB::commit();

        return response()->json(['message' => 'Invoice added successfully'], 201);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json(['error' => 'Failed to add invoice', 'message' => $e->getMessage()], 500);
    }
}


public function show_invoice($id) {
    $invoice = Invoice::with(['customer','invoice_items.product'])->find($id);
    if (!$invoice) {
        return response()->json(['error' => 'Invoice not found'], 404);
    }
    return response()->json(['invoice' => $invoice], 200);
}
public function edit_invoice($id) {
    $invoice = Invoice::with(['customer','invoice_items.product'])->find($id);
    if (!$invoice) {
        return response()->json(['error' => 'Invoice not found'], 404);
    }
    return response()->json(['invoice' => $invoice], status: 200);
}



public function update_invoice(Request $request, $id) {
    DB::beginTransaction();

    try {
        $validatedData = $request->validate([
            'sub_total' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'customer_id' => 'required|exists:customers,id',
            'number' => 'required|string|max:255',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'discount' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:255',
            'terms_and_conditions' => 'nullable|string',
            'invoice_items' => 'required|json',
        ]);

        $invoice = Invoice::findOrFail($id);

        $invoice->sub_total = $validatedData['sub_total'];
        $invoice->total = $validatedData['total'];
        $invoice->customer_id = $validatedData['customer_id'];
        $invoice->number = $validatedData['number'];
        $invoice->date = $validatedData['date'];
        $invoice->due_date = $validatedData['due_date'];
        $invoice->discount = $validatedData['discount'] ?? 0;
        $invoice->reference = $validatedData['reference'];
        $invoice->terms_and_conditions = $validatedData['terms_and_conditions'];

        $invoice->save();

        $invoiceItems = json_decode($validatedData['invoice_items'], true);

        $invoice->invoice_items()->delete();

        foreach ($invoiceItems as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        DB::commit();

        return response()->json(['success' => "Invoice updated successfully"]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json(['error' => 'Failed to update invoice', 'message' => $e->getMessage()], 500);
    }
}


public function delete_invoice($id) {
    $invoice = Invoice::find($id);

    if ($invoice) {
        $invoice->delete();
        return response()->json(['success' => "Invoice  deleted successfully"]);
    }

    return response()->json(['error' => "Invoice  deletion failed"], 404);
}



}
