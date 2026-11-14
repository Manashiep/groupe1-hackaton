@extends('layouts.app')

@section('title', 'Preuve de paiement')

@section('content')
<div class="page">
    <a href="{{ route('profile') }}" class="btn btn-ghost btn-sm mb-3"><i class="bi bi-arrow-left"></i> Moi</a>

    <div class="panel text-center p-4">
        <span class="pill st-nettoye mb-3"><i class="bi bi-check2-circle"></i> Redevance payée</span>
        <div class="text-muted small">Code à montrer au camionneur</div>
        <div class="fw-bold my-2" style="font-size:2.4rem;letter-spacing:.08em;font-variant-numeric:tabular-nums">{{ $due->receipt_code }}</div>
        <div class="row-sub">{{ $due->user->name }}</div>

        <hr>

        <div class="d-flex justify-content-between small py-1"><span class="text-muted">Montant</span><strong>{{ $due->amountLabel() }}</strong></div>
        <div class="d-flex justify-content-between small py-1"><span class="text-muted">Village</span><strong>{{ $due->passage->village->name }}</strong></div>
        <div class="d-flex justify-content-between small py-1"><span class="text-muted">Passage du</span><strong>{{ $due->passage->passed_on->translatedFormat('l j F Y') }}</strong></div>
        <div class="d-flex justify-content-between small py-1"><span class="text-muted">Camion</span><strong>{{ $due->passage->truck->name }}</strong></div>
        <div class="d-flex justify-content-between small py-1"><span class="text-muted">Payé le</span><strong>{{ $due->paid_at->translatedFormat('j F Y à H\hi') }}</strong></div>
    </div>

    <div class="callout callout-info mt-3">
        <i class="bi bi-info-circle-fill"></i>
        <div>Le camionneur tape ce code dans son application pour vérifier que vous avez bien payé.</div>
    </div>
</div>
@endsection
