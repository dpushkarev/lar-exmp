@extends('web.layout')
@section('content')
    <div class="col-lg-12">
        @yield('alert')
        <table class="table table-striped">
            <thead>
            <tr>
                <th scope="col">Opis</th>
                <th scope="col">Detalji transakcije</th>
            </tr>
            </thead>
            <tbody>
            @yield('error')
            <tr>
                <th scope="row">Broj rezervacije</th>
                <td>{{ $payment->INVOICENUMBER }}</td>
            </tr>
            <tr>
                <th scope="row">Sifra rezervacije</th>
                <td>{{ $reservation->code }}</td>
            </tr>
            <tr>
                <th scope="row">Ime Prezime</th>
                <td>{{ $payment->BillToName }}</td>
            </tr>
            <tr>
                <th scope="row">Adresa</th>
                <td>{{ $payment->BillToStreet1 }}, {{ $payment->BillToPostalCode }}, {{ $payment->BillToCity }}
                    , {{ $payment->BillToCountry }}</td>
            </tr>
            <tr>
                <th scope="row">E-mail</th>
                <td>{{ $payment->email }}</td>
            </tr>
            <tr>
                <th scope="row">Iznos</th>
                <td>{{ $payment->amount }}</td>
            </tr>
            <tr>
                <th scope="row">Valuta</th>
                <td>{{ $payment->currency }}</td>
            </tr>
            <tr>
                <th scope="row">Datum transakcije</th>
                <td>{{ $payment->EXTRA_TRXDATE }}</td>
            </tr>
            <tr>
                <th scope="row">OID</th>
                <td>{{ $payment->oid }}</td>
            </tr>
            <tr>
                <th scope="row">Response</th>
                <td>{{ $payment->Response }}</td>
            </tr>
            <tr>
                <th scope="row">Autorizacioni kod</th>
                <td>{{ $payment->AuthCode }}</td>
            </tr>
            <tr>
                <th scope="row">Broj transakcije</th>
                <td>{{ $payment->TransId }}</td>
            </tr>
            <tr>
                <th scope="row">ProcReturnCode</th>
                <td>{{ $payment->ProcReturnCode }}</td>
            </tr>
            <tr>
                <th scope="row">mdStatus</th>
                <td>{{ $payment->mdStatus }}</td>
            </tr>
            </tbody>
        </table>
    </div>
@endsection