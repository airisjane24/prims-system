<?php

namespace App\Http\Controllers;

use App\Constant\MyConstant;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\Transaction;
use App\Services\useValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected $useValidator;

    public function __construct(useValidator $useValidator)
    {
        $this->useValidator = $useValidator;
    }

    /**
     * Admin payment index with search & filter
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $payments = Payment::when($search, function ($query, $search) {
                return $query->where('full_name', 'like', "%{$search}%")
                             ->orWhere('name', 'like', "%{$search}%")
                             ->orWhere('transaction_id', 'like', "%{$search}%")
                             ->orWhere('payment_date', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $transactions = $payments->map(function ($p) {
            $fullName = $p->full_name ?? $p->name ?? ($p->firstname ?? null) ?? ($p->first_name ?? null);
            if (empty($fullName) && isset($p->user) && isset($p->user->name)) {
                $fullName = $p->user->name;
            }

            $date = $p->payment_date ?? $p->created_at ?? now();
            $txType = $p->transaction_type ?? $p->type ?? $p->payment_type ?? 'Payment';

            return (object) [
                'full_name'        => $fullName ?? '—',
                'amount'           => $p->amount ?? 0,
                'date_time'        => Carbon::parse($date)->format('Y-m-d H:i:s'),
                'transaction_type' => $txType,
                'transaction_id'   => $p->transaction_id ?? '—',
                'proof_image'      => $p->proof_image ?? null,
            ];
        })->values();

        return view('admin.payment', compact('transactions'));
    }

    /**
     * Store new payment
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string|max:255|unique:tpayments,transaction_id',
            'amount'         => 'required|numeric|min:0',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string|max:50',
            'payment_status' => 'required|string|max:50',
            'proof_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $filename = null;

            // Handle file upload
            if ($request->hasFile('proof_image')) {
                $file = $request->file('proof_image');
                $extension = $file->getClientOriginalExtension();
                $filename = 'payment_' . uniqid() . '.' . $extension;

                $file->move(public_path('assets/transaction_report'), $filename);
            }

            $payment = Payment::create([
                'request_id'      => $request->request_id,
                'name'            => $request->name,
                'amount'          => $request->amount,
                'payment_date'    => $request->payment_date,
                'payment_method'  => $request->payment_method,
                'payment_status'  => $request->payment_status,
                'transaction_id'  => $request->transaction_id,
                'proof_image'     => $filename,
            ]);

            // Insert into transactions table
            Transaction::create([
                'transaction_id'   => $payment->transaction_id,
                'user_id'          => Auth::id(),
                'amount'           => $payment->amount,
                'status'           => 'completed',
                'transaction_type' => 'payment',
            ]);

            Notification::create([
                'type'    => 'Payment',
                'message' => 'A new payment was recorded by ' . Auth::user()->name,
                'is_read' => '0',
            ]);

            return redirect()->back()->with('success', 'Payment added successfully.');
        } catch (\Exception $e) {
            Log::error('Payment Store Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to add payment.');
        }
    }

    public function resubmit($requestId)
{
    $request = RequestModel::findOrFail($requestId);

    // Ensure the request was declined
    if ($request->status !== 'Decline') {
        return redirect()->back()->with('error', 'Payment can only be resubmitted for declined requests.');
    }

    // Optionally, reset payment info so parishioner can pay again
    $request->is_paid = 0;
    $request->status = 'Pending';
    $request->save();

    return view('parishioner.payment_resubmit', compact('request'));
}


    /**
     * Update payment
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'amount'        => 'nullable|numeric|min:0',
            'to_pay'        => 'nullable|numeric|min:0',
            'number_copies' => 'nullable|integer|min:1',
            'payment_date'  => 'nullable|date',
            'proof_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $payment = Payment::findOrFail($id);

            $payment->transaction_id = $request->transaction_id ?? $payment->transaction_id;
            $payment->amount         = $request->to_pay ?? $request->amount ?? 0;
            $payment->number_copies  = $request->number_copies ?? $payment->number_copies;
            $payment->payment_date   = $request->payment_date ?? now();
            $payment->payment_status = $request->payment_status ?? $payment->payment_status;

            // Handle proof image upload
            if ($request->hasFile('proof_image')) {
                $file = $request->file('proof_image');
                $extension = $file->getClientOriginalExtension();
                $filename = 'payment_' . uniqid() . '.' . $extension;

                $file->move(public_path('assets/transaction_report'), $filename);

                $payment->proof_image = $filename;
            }

            $payment->save();

            // ✅ Update transactions table
            Transaction::where('transaction_id', $payment->transaction_id)
                ->update([
                    'status'           => 'completed',
                    'transaction_type' => 'payment',
                    'amount'           => $payment->amount,
                ]);

            return redirect()->back()->with('success', 'Payment updated successfully.');
        } catch (\Exception $e) {
            Log::error('Payment Update Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update payment.');
        }
    }
}
