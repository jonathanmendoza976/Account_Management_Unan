<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Helpers\PdfHelper;
use App\Helpers\RedirectHelper;
use App\Helpers\ViewHelper;
use App\Models\Concept;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Payment;
use App\Models\User;
use App\Services\ExchangeRateService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;
use SoapFault;

class PaymentController extends Controller
{
    use DateHelper;

    /**
     * @var ViewHelper
     */
    private ViewHelper $viewHelper;

    private RedirectHelper $redirectHelper;

    private PdfHelper $pdfHelper;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Require auth middleware
        $this->middleware('auth');

        // Inject the view helper
        $this->viewHelper = ViewHelper::getInstance();

        // Inject the redirect helper
        $this->redirectHelper = RedirectHelper::getInstance();

        // Inject the pdf helper
        $this->pdfHelper = PdfHelper::getInstance();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return RedirectResponse|View
     */
    public function index(Request $request): View|RedirectResponse
    {
        // Check if the request search query is set
        if ($request->has('search')) {
            $payments = Payment::search($request->search)->paginate(7);
            $payments->withQueryString();
        } else {
            $payments = Payment::latest()->paginate(7);
        }

        // Return the view
        return $this->viewHelper->render(
            'payments.index',
            ['payments' => $payments],
            ['cajero']
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return RedirectResponse|View
     * @throws SoapFault
     */
    public function create(): View|RedirectResponse
    {
        $table = [
            ['id' => 'concept-table', 'name' => 'Concepto', 'hidden' => false, 'class' => 'step-one'],
            ['id' => 'amount-table', 'name' => 'Monto', 'hidden' => false, 'class' => 'step-one'],
            ['id' => 'amount-in-letters-table', 'name' => 'Monto en letras', 'hidden' => false, 'class' => 'step-one'],
            ['id' => 'currency-table', 'name' => 'Moneda', 'hidden' => false, 'class' => 'step-one'],
            ['id' => 'date-made-payment-table', 'name' => 'Fecha de realizacion', 'hidden' => true, 'class' => 'step-two'],
            ['id' => 'payment-registration-date-table', 'name' => 'Fecha de registro', 'hidden' => true, 'class' => 'step-two'],
            ['id' => 'observation-table', 'name' => 'Observación', 'hidden' => true, 'class' => 'step-two'],
            ['id' => 'payment-received-table', 'name' => 'Por quién se recibió', 'hidden' => true, 'class' => 'step-two'],
            ['id' => 'account-is-payment-table', 'name' => 'Por cuenta de quién', 'hidden' => true, 'class' => 'step-two'],
            ['id' => 'identification-table', 'name' => 'Identificación / RUC', 'hidden' => true, 'class' => 'step-three'],
            ['id' => 'receipt-number-table', 'name' => 'Número de recibo', 'hidden' => true, 'class' => 'step-three'],
            ['id' => 'pay-time-table', 'name' => 'Hora en el que se efectuó el pago', 'hidden' => true, 'class' => 'step-three'],
            ['id' => 'cashier-table', 'name' => 'Cajero que registró el pago', 'hidden' => true, 'class' => 'step-three'],
            ['id' => 'cashier-identification-table', 'name' => 'Identificador del cajero', 'hidden' => true, 'class' => 'step-three'],
        ];

        return $this->viewHelper->render(
            'payments.create',
            [
                'exchangeRate' => ExchangeRate::first()->value,
                'currencies' => Currency::where('is_active', true)->get(),
                'concepts' => Concept::all(),
                'types' => ['Estudiante', 'Trabajador', 'Otro'],
                'table' => $table,
            ],
            ['cajero']
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        return $this->redirectHelper->redirect(['cajero'], function() use($request) {
            // Get user by identification
            $user = User::where('identification', $request->identification)->first();

            // Create the payment
            Payment::create(array_merge($request->all(), [
                'user_id' => $user->id,
                'exchange_rate' => ExchangeRate::first()->value,
            ]));

            // Redirect to the payments list
            return redirect()->route('payments.index')->with('success', 'Pago registrado correctamente');
        });
    }

    /**
     * Display the specified resource.
     *
     * @param Payment $payment
     * @return RedirectResponse|View
     */
    public function show(Payment $payment): View|RedirectResponse
    {
        return $this->viewHelper->render(
            'payments.show',
            ['payment' => $payment],
            ['cajero', 'usuario']
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Payment $payment
     * @return Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Payment $payment
     * @return Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Payment $payment
     * @return Response
     */
    public function destroy(Payment $payment)
    {
        //
    }

    public function pdf(Payment $payment): Response
    {

        return $this->pdfHelper->download();
    }
}
