@extends('web.sections.payment.result')
@section('alert')
    <div class="alert alert-success" role="alert">
        <h4 class="alert-heading">Uspešno ste izvršili plaćanje.</h4>
        <p>Račun vaše kartice je zadužen.</p>
        <hr>
        <p class="mb-0">Pogledajte <a href="/order/{id}/" class="alert-link">rezervaciju</a></p>
    </div>
@endsection