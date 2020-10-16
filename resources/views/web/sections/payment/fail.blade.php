@extends('web.sections.payment.result')
@section('alert')
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading">Plaćanje neuspešno.</h4>
        <p>Račun vaše platne kartice nije zadužen.</p>
        <hr>
        <p class="mb-0">Pogledajte <a href="{{ route('get.reservation', ['id' => $payment->INVOICENUMBER ]) }}" class="alert-link">rezervaciju</a></p>
    </div>
@endsection