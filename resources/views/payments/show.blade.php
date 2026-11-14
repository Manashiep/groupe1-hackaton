@extends('layouts.app')

@section('title', 'Paiement')

@use('App\Enums\PaymentStatus')

@push('head')
    @if ($payment->status === PaymentStatus::Pending)
        {{-- En attendant le webhook, la page se recharge toute seule toutes les 3 secondes. --}}
        <meta http-equiv="refresh" content="3">
    @endif
@endpush

@section('content')
<div class="page text-center">
    @if ($payment->status === PaymentStatus::Succeeded)
        <span class="icon-chip ico-leaf mx-auto mb-3" style="width:72px;height:72px;font-size:2rem"><i class="bi bi-check-lg"></i></span>
        <h1>Paiement réussi</h1>
        <p class="text-muted">{{ $payment->due->amountLabel() }} · {{ $payment->due->passage->village->name }}</p>
        <a href="{{ route('dues.receipt', $payment->due) }}" class="btn btn-primary btn-lg mt-2"><i class="bi bi-qr-code me-1"></i> Voir ma preuve de paiement</a>
    @elseif ($payment->status === PaymentStatus::Pending)
        <div class="spinner-border text-success mb-3" style="width:3rem;height:3rem" role="status"></div>
        <h1>Confirmation en cours…</h1>
        <p class="text-muted">On attend la confirmation de SimuPay. Cette page se met à jour toute seule.</p>
        <a href="{{ route('payments.show', $payment) }}" class="btn btn-ghost mt-2">Actualiser</a>
    @else
        <span class="icon-chip ico-red mx-auto mb-3" style="width:72px;height:72px;font-size:2rem"><i class="bi bi-x-lg"></i></span>
        <h1>Paiement non abouti</h1>
        <p class="text-muted">{{ PaymentStatus::failureLabel($payment->failure_reason) }}. Aucune somme n'a été prélevée.</p>
        <form method="POST" action="{{ route('dues.pay', $payment->due) }}" class="mt-2">
            @csrf
            <button class="btn btn-primary btn-lg">Réessayer</button>
        </form>
    @endif
    <a href="{{ route('profile') }}" class="d-block mt-4">Retour à mon espace</a>
</div>
@endsection
