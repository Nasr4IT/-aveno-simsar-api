<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\FeaturePaymentService;
use Illuminate\Http\Request;

// Manual Sham Cash payments: check the uploaded receipt against the Sham Cash
// account, then approve (ad featured for the package's days) or reject.
class PaymentManagementController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', Payment::PENDING);

        $payments = Payment::with(['user', 'ad.images', 'adPackage', 'reviewer'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', compact('payments', 'status'));
    }

    public function receipt(Request $request, Payment $payment, FeaturePaymentService $payments)
    {
        return $payments->receiptResponse($payment, $request->user());
    }

    public function approve(Request $request, Payment $payment, FeaturePaymentService $payments)
    {
        $payments->approve($payment, $request->user());

        return back()->with('status', __('تم تأكيد الدفع وتمييز ":title".', ['title' => $payment->ad->title]));
    }

    public function reject(Request $request, Payment $payment, FeaturePaymentService $payments)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $payments->reject($payment, $request->user(), $data['reason']);

        return back()->with('status', __('تم رفض طلب التمييز.'));
    }
}
