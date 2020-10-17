@extends('web.sections.payment.result')
@section('alert')
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading">Plaćanje neuspešno.</h4>
        <p>Račun vaše platne kartice nije zadužen.</p>
        <hr>
        <p class="mb-0">Pogledajte <a href="{{ $reservation->getUrl() }}" class="alert-link">rezervaciju</a></p>
    </div>
@endsection
@section('error')
    <tr>
        <th scope="row">Текст грешке</th>
        <td>{{ $payment->ErrMsg }}</td>
    </tr>
@endsection