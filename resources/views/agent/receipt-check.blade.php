@extends('layouts.app')

@section('title', 'Vérifier un paiement')

@section('content')
<div class="hero">
    <div class="inner">
        <a href="{{ route('agent.route') }}" class="btn btn-ghost btn-sm mb-3"><i class="bi bi-arrow-left"></i> Tournée</a>
        <h1>Vérifier une preuve de paiement</h1>
        <p>Tapez le code que l'habitant vous montre (ex. CC-7K4Q9P).</p>
    </div>
</div>

<div class="sheet">
    <div class="inner">
        <form method="GET" action="{{ route('agent.receipts.check') }}" class="d-flex gap-2 mb-3">
            <input name="code" value="{{ $code }}" class="form-control form-control-lg text-uppercase" placeholder="CC-XXXXXX" autocomplete="off" required aria-label="Code de paiement">
            <button class="btn btn-primary btn-lg">Vérifier</button>
        </form>

        @if ($code !== '')
            @if ($due)
                <div class="panel">
                    <div class="d-flex gap-3 align-items-center">
                        <span class="icon-chip ico-leaf" style="width:52px;height:52px;font-size:1.5rem"><i class="bi bi-check-lg"></i></span>
                        <div>
                            <div class="fw-bold fs-5">Paiement valide</div>
                            <div class="row-sub">{{ $due->user->name }} · {{ $due->passage->village->name }}</div>
                            <div class="row-sub">Passage du {{ $due->passage->passed_on->translatedFormat('j F') }} · {{ $due->amountLabel() }} payés le {{ $due->paid_at->translatedFormat('j F à H\hi') }}</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="panel">
                    <div class="d-flex gap-3 align-items-center">
                        <span class="icon-chip ico-red" style="width:52px;height:52px;font-size:1.5rem"><i class="bi bi-x-lg"></i></span>
                        <div>
                            <div class="fw-bold fs-5">Code inconnu</div>
                            <div class="row-sub">Aucun paiement ne correspond à « {{ $code }} ». Vérifiez l'orthographe avec l'habitant.</div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
